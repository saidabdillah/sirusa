---
paths:
  - 'resources/views/admin/**'
---

# Views Admin

## Verification index columns follow the stage
`admin/verifikasi/index.blade.php` drives `<th>`/`<td>` from a `$stageColumns` map (label + closure over the profile), and the empty-state `colspan` is `count($stageColumns) + 3`: capil = NIK/No. Kartu Keluarga/Desil, kampus = NIM/Program Studi/IPK, kesra = NIK/Desil. The campus stage reads `profile.prodi`, so `VerifikasiController@index` eager-loads it there (`with(['profile' => ...->when($stage === 'kampus', ...->with('prodi'))])`) to avoid N+1. The decision page sidebar also renders `catatan_{capil,kampus,kesra}` under each status badge so the next verifier reads the previous stage's note, and the `catatan` textarea pre-fills `old('catatan', $profile->{'catatan_'.$stage})` so re-opening a revisi/tolak stage cannot silently wipe its note.

## Pengguna views read peran options from the controller, not a hardcoded list
`admin/pengguna/index` receives `$roleLabels` (= `User::ROLE_LABELS`) and `admin/pengguna/{buat,ubah}` receive `$peranOptions` (= `User::assignableRoleOptions()`, already excluding `super_admin` for non-super_admin actors) — so none of the three views may hardcode the 5 options again, or kesra would be offered a role the server rejects. The index row computes `$rowIsSuperAdmin`/`$canEdit`/`$canUseSuperActions` per row: the `super_admin` row has NO action buttons at all, and a kesra row only gets the Ubah link.

## Role-conditional form blocks must disable their inputs, not just hide them
`d-none` is CSS-only: inputs inside a hidden block stay enabled and the browser STILL submits them, so their stale values get validated against a role that does not own them (symptom: a mahasiswa create fails with "Kata sandi minimal 8 karakter" / "Konfirmasi kata sandi tidak cocok" / "Username sudah digunakan" after switching peran away from a staff role). `admin/pengguna/buat` and `ubah` therefore toggle `disabled` in `toggleAkun()` alongside `d-none` — `syncBlock(block, isVisible)` in buat, `akunKampus.find('select').prop('disabled', ...)` in ubah. Any new conditional block must do the same, and a test asserts the rendered form still contains `prop('disabled'`.
