---
paths:
  - app/Models/Applicant.php
  - app/Http/Controllers/Admin/PendaftarController.php
  - app/Models/UserProfile.php
---

# Applicant (pendaftar)

## The pendaftar LIST is read-only, the DECISION lives on the verification detail page
`admin.pendaftar.*` stays list-only: just `index` plus the DataTables JSON, no per-row detail, edit or delete surface. The scholarship decision is made from the Kesra verification page, not from this list: `admin.kesra.pendaftaran.keputusan` (`KeputusanPendaftaranController@update`) writes `pendaftar.status` + `pendaftar.catatan` per row. The removed routes/requests stay removed (`admin.pendaftar.lihat/perbarui/info/hapus`, `PendaftarController::show/update/deleteInfo/destroy`, views `admin/pendaftar/{lihat,_aksi}`, `Applicant/UpdateApplicantRequest.php`, `ApplicantStatusChanged.php`). `Applicant` still maps to table `pendaftar` with relations `user`/`beasiswa`, plus `STATUS_LABELS`, `STATUS_BLOCKING`, `statusLabel()`, `statusBadge()`, `isDecided()`, `isActive()`, `isCancelled()`, `canBeCancelled()`, `blocksNewApplication()` and `snapshotDrifts()`.

## Verification decisions stay editable, hidden only past a decided stage
`canVerifStage()` must NOT short-circuit on `verifStatus() === 'terverifikasi'` or on the stage itself being `setuju` — that made decided rows vanish from the queue and the admin could not revise a decision.

**Catpil dan Kampus berjalan PARALEL** (keputusan alur baru): keduanya tidak saling menunggu — profil dengan `verif_catpil` selain `setuju` tetap masuk antrean Kampus, asalkan mahasiswanya sudah mendaftar (`user->applicants()->exists()`, satu-satunya syarat khusus Kampus). Hanya Kesra — tahap keputusan akhir — yang menunggu semua tahap sebelumnya `setuju`. Tahap yang sudah diputuskan tetap bisa dikoreksi selama satu-satunya tahap hilir (Kesra, via `verifDownstreamStages()`) masih `menunggu`; begitu Kesra memutuskan, tahap sebelumnya terkunci (403).

Konsekuensi kaskade: `resetDownstreamStages($stage)` hanya me-reset KESRA (bukan Kampus) ketika Catpil revisi/tolak/tarik kembali — keputusan Kampus tidak boleh terhapus oleh keputusan Catpil. Jangan mengembalikan aturan sekuensial lama: test `VerifikasiProfilTest` ('kampus queue does not wait for the catpil approval', 'catpil decision does not wait for kampus and does not reset it') menjaganya.

## Verification status defaults to menunggu and is data, not UI
New profiles start with `verif_catpil/kampus/kesra = 'menunggu'` from both the migration `default()` and the model `$attributes`. The progress/stepper UI was removed everywhere, but the columns stay: they drive `canVerifStage()`, the queue filters, the audit trail, `verifStageDecision()`, and the student dashboard status card. Per-stage `catatan_*` notes live in the admin decision card and on the student dashboard for revisi/tolak.

Status `diterima` di Kelola Kesra → modul Penerima Beasiswa (`admin.penerima`): daftar + Cetak/Export, dan aksi "Hapus" (`tarik`) yang MENGEMBALIKAN status ke `verifikasi` (catatan dikosongkan) supaya masuk lagi ke antrean keputusan — baris pendaftar tidak pernah dihapus karena index unik `(user_id, beasiswa_id)`. Kolom `verif_kesra` harus `setuju` dulu sebelum keputusan pendaftaran boleh dibuat.

## Blok orang tua/wali dibaca lewat orangTuaDipilih()
Semua tempat yang perlu data orang tua/wali (ekspor Excel Catpil/Kampus/Kesra) harus memakai `$profile->orangTuaDipilih()` yang mengembalikan `{label, nama, nik, pekerjaan}` sekaligus, bukan `match ($profile->ikut_kk)` sendiri-sendiri per kolom. Menulis tiap kolom terpisah mudah membuat baris menampilkan nama ayah dengan NIK ibu. `default` memakai ayah supaya profil lama dengan `ikut_kk` kosong tetap punya data.
