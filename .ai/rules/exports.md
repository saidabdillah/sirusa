---
paths:
  - 'app/Exports/**'
  - app/Exports/KesraVerifikasiExport.php
---

# Exports

## Exports use phpspreadsheet directly and are named after their stage
Only `phpoffice/phpspreadsheet` is installed, not the `maatwebsite/excel` wrapper, so `App\Support\ExcelDownload` builds the streamed XLSX response. Export routes are named `admin.<stage>.export` (capil / kampusverif / kesra) on purpose: `EnsureMenuAccess` matches a route by `scope.'.'` prefix, so the existing menu grants authorize exports with no new scope. Write numeric columns (UKT, IPK) as numbers, never as `Rp 1.500.000` strings.

## Kolom ekspor hanya untuk field yang benar-benar ada di database
Setiap kolom Excel harus punya sumber data nyata di `profil_pengguna`/`pendaftar`/`users`. Field yang diminta spesifikasi tapi tidak punya kolom (RT, RW, status perkawinan, tempat/tanggal lahir & penghasilan orang tua, desil per orang tua, tanggal verifikasi, jenjang, status mahasiswa) DILEWATI, bukan dikarang dengan kolom kosong. Kolom kosong yang selalu berisi "-" lebih merusak kepercayaan pada laporan daripada kolom yang tidak ada. Tiap tahap punya cakupannya sendiri: Capil tidak membawa NIM/IPK/UKT, Campus dan Kesra membawa dokumen + blok orang tua, Kesra juga membawa keputusan dua tahap sebelumnya.

## Nomor identitas tetap teks, angka yang dihitung tetap angka
NIK/NIM/No. KK lewat `teks()` (string) dan TIDAK boleh diberi `number_format` atau dipaksa jadi angka "agar bisa dijumlahkan" -- `DefaultValueBinder` PhpSpreadsheet sudah otomatis mempertahankan string berawalan 0 dan nilai di atas 999999999999999 sebagai teks, jadi nol di depan dan digit ke-16 NIK selamat. Sebaliknya IPK/semester/UKT/desil ditulis sebagai angka mentah, bukan string berisi pemisah ribuan, supaya masih bisa dijumlahkan dan disaring di Excel.

## Jangan pakai range() untuk iterasi huruf kolom
`VerifikasiExport::gayaHeader()` sebelumnya memakai `range('A', $lastColumn)`, yang hanya bisa menghitung huruf tunggal. Begitu kolom melewati `Z` (26 kolom) fungsi itu melempar ErrorException dan unduhan jadi 500. Jebakan ini tidak terlihat sampai ada subclass dengan >26 kolom (ekspor Kesra sekarang 35 kolom). Iterasi lewat indeks dengan `Coordinate::stringFromColumnIndex()`.

## Unduhan Kesra harus berisi persis isi antrean yang sedang dibuka
`KesraVerifikasiExport::users()` wajib baca dari `VerifikasiAntrean::pendaftarUsers($this->filter())`, bukan `parent::users()` yang memfilter profil lewat `canVerifStage()` — yang mengembalikan semua yang lolos Capil+Kampus termasuk yang keputusannya lama, sehingga unduhan tidak sama dengan tabel yang dibaca admin. `VerifikasiExport` menyediakan accessor `filter()` dan `antrean()` protected untuk itu. Filter yang sama harus diteruskan ke kolom pendaftaran di dalam sel (`pendaftaran($user)`), supaya "Status Pendaftaran" di Excel juga jujur.
