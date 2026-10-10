---
paths:
  - resources/views/user/beasiswa/index.blade.php
  - resources/views/user/beasiswa/lihat.blade.php
---

# User Beasiswa

## Tombol Lihat Detail kartu beasiswa dikunci di dasar
.card-body memakai d-flex flex-column dan wrapper tombol mt-auto pt-3 supaya tombol 'Lihat Detail' sejajar di dasar semua kartu meski panjang teks berbeda.

## Detail beasiswa user: cakupan prodi di atas Deskripsi
Blok 'Program Studi yang Bisa Mendaftar' (fakultas + prodi) dipromosikan ke dekat info Kampus - setelah Periode Pendaftaran dan sebelum <hr>/Deskripsi, bukan di bawah deskripsi.

## Daftar prodi pakai pl-4, bukan ml-4
Daftar prodi di blok 'Program Studi yang Bisa Mendaftar' memakai `<ul class="mb-0 pl-4">` (indentasi 24px) agar sejajar dengan catatan 'Seluruh program studi pada fakultas ini' (ml-4). Jangan pakai ml-4 pada <ul> - dobel dengan padding-left default ul (~40px) sehingga prodi menjorok ~64px.
