---
paths:
  - app/Models/Applicant.php
  - app/Http/Controllers/Admin/PendaftarController.php
  - app/Models/UserProfile.php
---

# Applicant (pendaftar)

## The pendaftar LIST is read-only, the DECISION lives on the verification detail page
`admin.pendaftar.*` stays list-only: just `index` plus the DataTables JSON, no per-row detail, edit or delete surface. The scholarship decision is made from the Kesra verification page, not from this list: `admin.kesra.pendaftaran.keputusan` (`KeputusanPendaftaranController@update`) writes `pendaftar.status` + `pendaftar.catatan` per row. The removed routes/requests stay removed (`admin.pendaftar.lihat/perbarui/info/hapus`, `PendaftarController::show/update/deleteInfo/destroy`, views `admin/pendaftar/{lihat,_aksi}`, `Applicant/UpdateApplicantRequest.php`, `ApplicantStatusChanged.php`). `Applicant` still maps to table `pendaftar` with relations `user`/`beasiswa`, plus `STATUS_LABELS`, `STATUS_BLOCKING`, `statusLabel()`, `statusBadge()`, `isDecided()`, `isActive()`, `isCancelled()`, `cancellationBlockedReason()`, `canBeCancelled()`, `blocksNewApplication()` and `snapshotDrifts()`. `canBeCancelled()` is a thin wrapper over `cancellationBlockedReason()` (which also carries the user-facing reason) and it reads `beasiswa.tanggal_selesai` -- see `.ai/rules/pendaftaran.md`; changing one without the other means the detail button and the endpoint will disagree.

## Verification decisions stay editable, hidden only past a decided stage
`canVerifStage()` must NOT short-circuit on `verifStatus() === 'terverifikasi'` or on the stage itself being `setuju` — that made decided rows vanish from the queue and the admin could not revise a decision. A stage is reachable when all PREVIOUS stages are `setuju`; if the stage is already decided it stays reachable only while every DOWNSTREAM stage is still `menunggu`, since revising past a decided stage would silently discard another office's work.

## Verification status defaults to menunggu and is data, not UI
New profiles start with `verif_capil/kampus/kesra = 'menunggu'` from both the migration `default()` and the model `$attributes`. The progress/stepper/status-card UI was removed everywhere, but the columns stay: they drive `canVerifStage()`, the queue filters, the audit trail, and `verifStageDecision()`. Per-stage `catatan_*` notes now live in the admin decision card, not in a status card.

## Blok orang tua/wali dibaca lewat orangTuaDipilih()
Semua tempat yang perlu data orang tua/wali (ekspor Excel Capil/Kampus/Kesra) harus memakai `$profile->orangTuaDipilih()` yang mengembalikan `{label, nama, nik, pekerjaan}` sekaligus, bukan `match ($profile->ikut_kk)` sendiri-sendiri per kolom. Menulis tiap kolom terpisah mudah membuat baris menampilkan nama ayah dengan NIK ibu. `default` memakai ayah supaya profil lama dengan `ikut_kk` kosong tetap punya data.

## Status labels, badge colours and icons live only in the model
`STATUS_LABELS` (verifikasi=Menunggu, diterima=Disetujui, ditolak=Ditolak, dibatalkan=Dibatalkan), `STATUS_BADGES` and `STATUS_ICONS` are the only place these labels exist. Enum values in the database are unchanged. Views and `PendaftarController::renderStatus()` must read `statusLabel()/statusBadge()/statusIcon()` — never hand-write a status label or colour, and never `@switch` over status in a view.
