---
paths:
  - resources/views/profil/index.blade.php
---

# Resources Views Profil

## Profile labels spell out their abbreviation: `ABBR (Expansion)`
Every abbreviation on the profil form carries its full meaning, abbreviation first: `NIK (Nomor Induk Kependudukan)`, `IPK (Indeks Prestasi Akademik)`, `NIM (Nomor Induk Mahasiswa)`, `UKT (Uang Kuliah Tunggal)` (SPP deliberately dropped — its expansion is not uniform across Indonesian universities), `Ikut KK (Kartu Keluarga)`, `KTP (Kartu Tanda Penduduk) Ayah/Ibu/Wali`, `KK (Kartu Keluarga) Wali`, `SKTM (Surat Keterangan Tidak Mampu)`, `Bukti UKT (Uang Kuliah Tunggal)`. `RT/RW` in the Alamat Detail label stays as-is. The long labels are allowed to wrap inside `col-md-4`/`col-md-6`; do not reflow the grid to accommodate them. `partials/profil-detail.blade.php` uses the REVERSE order (`Kartu Tanda Penduduk (KTP)`) and is intentionally left alone. `lang/id/validation.php` still maps `ukt` to `UKT/SPP`, so the error message is shorter than the form label on purpose.
