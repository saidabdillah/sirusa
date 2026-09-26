---
paths:
  - 'app/Models/User.php, app/Http/Controllers/Profile/ProfileController.php, app/Http/Requests/Profil/UpdateProfilRequest.php, resources/views/profil/**'
---

# Views Profil

## Only role `user` (mahasiswa) has a profile; staff accounts get account info only
`PenggunaController::store()` only creates a `profil_pengguna` row for `peran = user`, so super_admin/kesra/kampus/capil have NO profile at all. The profil page therefore gates the entire form, all completeness/verification alerts, the document upload groups and the whole `@push('script')` (Cleave, flatpickr, campus cascade, wali toggle) behind `User::isMahasiswa()` (`hasRole('user')`), leaving only the "Informasi Akun" card, widened to `col-lg-6 offset-lg-3` and pointing at `settings` for email/password. `ProfileController@index` also skips `WilayahService::getDistricts()` (an outbound HTTP call) and the `Kampus::with('fakultas.prodi')` query for staff. The guard is server-side too: `UpdateProfilRequest::authorize()` returns `$this->user()->isMahasiswa()` so a hand-crafted PUT gets 403 — hiding the form in the UI is not a boundary (see views.md). Covered by tests/Feature/ProfilStafTest.php.
