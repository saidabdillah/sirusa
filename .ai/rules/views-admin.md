---
paths:
  - 'resources/views/admin/**'
---

# Views Admin

## Verification index columns follow the stage
`admin/verifikasi/index.blade.php` drives `<th>`/`<td>` from a `$stageColumns` map (label + closure over the profile), and the empty-state `colspan` is `count($stageColumns) + 3`: catpil = NIK/No. Kartu Keluarga/Desil, kampus = NIM/Program Studi/IPK, kesra = NIK/Desil. The campus stage reads `profile.prodi`, so `VerifikasiController@index` eager-loads it there (`with(['profile' => ...->when($stage === 'kampus', ...->with('prodi'))])`) to avoid N+1. The decision page sidebar also renders `catatan_{catpil,kampus,kesra}` under each status badge so the next verifier reads the previous stage's note, and the `catatan` textarea pre-fills `old('catatan', $profile->{'catatan_'.$stage})` so re-opening a revisi/tolak stage cannot silently wipe its note.

## Pengguna views read peran options from the controller, not a hardcoded list
`admin/pengguna/index` receives `$roleLabels` (= `User::ROLE_LABELS`) and `admin/pengguna/{buat,ubah}` receive `$peranOptions` (= `User::assignableRoleOptions()`, already excluding `super_admin` for non-super_admin actors) — so none of the three views may hardcode the 5 options again, or kesra would be offered a role the server rejects. The index row computes `$rowIsSuperAdmin`/`$canEdit`/`$canUseSuperActions` per row: the `super_admin` row has NO action buttons at all, and a kesra row only gets the Ubah link.

## Role-conditional form blocks must disable their inputs, not just hide them
`d-none` is CSS-only: inputs inside a hidden block stay enabled and the browser STILL submits them, so their stale values get validated against a role that does not own them (symptom: a mahasiswa create fails with "Kata sandi minimal 8 karakter" / "Konfirmasi kata sandi tidak cocok" / "Username sudah digunakan" after switching peran away from a staff role). `admin/pengguna/buat` and `ubah` therefore toggle `disabled` in `toggleAkun()` alongside `d-none` — `syncBlock(block, isVisible)` in buat, `akunKampus.find('select').prop('disabled', ...)` in ubah. Any new conditional block must do the same, and a test asserts the rendered form still contains `prop('disabled'`. Switching peran (`peran.on('change', toggleAkun)`) must ALSO clear stale validation state: `toggleAkun()` in buat calls `clearErrors()` (removes `.is-invalid`/`aria-invalid` and deletes every `.invalid-feedback` — covers both `@error`-rendered and AJAX-painted `[data-ajax]` blocks). A test asserts the rendered buat form still contains `.find('.invalid-feedback').remove()`.

## Tombol Reset filter admin selalu tampil, gaya seragam
Tombol Reset pada form filter admin (daftar pendaftar, antrean verifikasi Catpil/Kampus/Kesra) selalu tampil, tanpa `@if` pada filter aktif, dan memakai `btn btn-secondary btn-block` + ikon `fas fa-redo`. Jangan menyembunyikannya berdasarkan filter: default Kesra sudah `verifikasi` sehingga tiap halaman akan menampilkan bentuk tombol yang berbeda. `VerifikasiProfilTest` mengunci bentuk ini lewat regex jangkar Reset.

## Halaman arsip Kesra: tanpa baris empty-state di dalam tbody
`admin/verifikasi/kesra-disetujui.blade.php` murni daftar hasil (`status = 'diterima'`) dengan aksi Hapus per baris: form `data-ajax-form` DELETE ke `admin.kesra.pendaftaran.hapus` dengan tombol `.btn-delete` + `data-confirm-*` (handler global di `custom.js`, jangan tulis handler lokal). Seperti aturan di `.ai/rules/verifikasi.md`, tbody-nya memakai `@foreach` polos BUKAN `@forelse` + `@empty`: DataTables memetakan sel berdasarkan posisi dan mengabaikan `colspan`, sehingga satu baris kosong membuat inisialisasinya melempar error — pesan kosongnya ditaruh di opsi `language.emptyTable`. Header kedua halaman Kesra minim (permintaan pengguna): `kesra.blade.php` hanya memuat tombol **Unduh Excel** (`route($routePrefix.'.export', request()->only('filter'))`, guard `hasMenuAccess($routePrefix.'.export')` — pola Catpil/Kampus; filter default antrean `verifikasi`), dan header `kesra-disetujui.blade.php` TANPA tombol apa pun (tombol "Unduh Excel" pindah ke antrean; tombol "Antrean Putusan" dihapus — navigasi cukup breadcrumb + sidebar; tombol "Disetujui"/`fa-check-double` dihapus — akses arsip lewat dropdown sidebar). Jangan ditambahkan kembali.

## Label tampil 'Program Studi', param tetap jurusan_id
Semua teks tampil memakai 'Program Studi' (bukan 'Jurusan'). Nama field/param query TETAP jurusan_id/id='filter-jurusan' supaya aturan filter lokasi yang sudah ada tidak putus — hanya label display yang berubah. Komentar boleh menyebut alasannya.
