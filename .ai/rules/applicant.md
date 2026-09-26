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
`canVerifStage()` must NOT short-circuit on `verifStatus() === 'terverifikasi'` or on the stage itself being `setuju` — that made decided rows vanish from the queue and the admin could not revise a decision. A stage is reachable when all PREVIOUS stages are `setuju`; if the stage is already decided it stays reachable only while every DOWNSTREAM stage is still `menunggu`, since revising past a decided stage would silently discard another office's work.
