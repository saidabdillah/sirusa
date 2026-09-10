---
paths:
  - 'app/Http/Requests/Applicant/**'
---

# Applicant

## KTM (surat aktif) wajib berbasis semester_minimal beasiswa
dokumen_surat_aktif wajib (Rule::requiredIf) bila scholarship->semester_minimal >= 2, BUKAN dari profil user. View buat.blade.php memakai kondisi yang sama untuk tanda * dan hint ("Wajib/Tidak wajib untuk beasiswa ini."). Alur revisi (update) tetap nullable. Tambahkan rute input beasiswa_id lewat $this->input() saat resolve Scholarship.
