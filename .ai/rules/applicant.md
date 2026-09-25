---
paths:
  - 'app/Models/Applicant.php'
  - 'app/Http/Controllers/Admin/PendaftarController.php'
---
# Applicant (pendaftar)

## The pendaftar module is fully READ-ONLY
Admins can only list `pendaftar` (`admin.pendaftar.index`) and consume its DataTables JSON (`admin.pendaftar.data`). The scholarship decision is NOT managed here — the verifikasi profil module (`admin.verifikasi.*`) is the only approval surface. All admin mutation flows were REMOVED: `admin.pendaftar.lihat/perbarui/info/hapus` routes, `PendaftarController::show/update/deleteInfo/destroy`, views `admin/pendaftar/{lihat,_aksi}`, `app/Http/Requests/Applicant/UpdateApplicantRequest.php`, and `app/Notifications/ApplicantStatusChanged.php`. `Applicant::getStatusLabelAttribute()` is gone (it existed only for those flows). `Applicant` still maps to table `pendaftar` with relations `user`/`beasiswa`.