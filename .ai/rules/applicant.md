---
paths:
  - app/Models/Applicant.php
  - app/Http/Controllers/Admin/PendaftarController.php
  - app/Models/UserProfile.php
---

# Applicant (pendaftar)

## The pendaftar module is fully READ-ONLY
Admins can only list `pendaftar` (`admin.pendaftar.index`) and consume its DataTables JSON (`admin.pendaftar.data`). The scholarship decision is NOT managed here — the verifikasi profil module (`admin.verifikasi.*`) is the only approval surface. All admin mutation flows were REMOVED: `admin.pendaftar.lihat/perbarui/info/hapus` routes, `PendaftarController::show/update/deleteInfo/destroy`, views `admin/pendaftar/{lihat,_aksi}`, `app/Http/Requests/Applicant/UpdateApplicantRequest.php`, and `app/Notifications/ApplicantStatusChanged.php`. `Applicant::getStatusLabelAttribute()` is gone (it existed only for those flows). `Applicant` still maps to table `pendaftar` with relations `user`/`beasiswa`.

## Verification decisions stay editable, hidden only past a decided stage
`canVerifStage()` must NOT short-circuit on `verifStatus() === 'terverifikasi'` or on the stage itself being `setuju` — that made decided rows vanish from the queue and the admin could not revise a decision. A stage is reachable when all PREVIOUS stages are `setuju`; if the stage is already decided it stays reachable only while every DOWNSTREAM stage is still `menunggu`, since revising past a decided stage would silently discard another office's work.
