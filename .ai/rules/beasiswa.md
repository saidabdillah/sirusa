---
paths:
  - 'resources/views/admin/beasiswa/**'
  - resources/views/admin/beasiswa/lihat.blade.php
---

# Beasiswa

## Both beasiswa date forms go through window.initDatePicker
`buat.blade.php` and `ubah.blade.php` must both call `initDatePicker('.flatpickr')` from `custom.js` and both inputs must be `type="text"` with the `flatpickr` class. The helper keeps the submitted value as `Y-m-d` while showing `d/m/Y` via `altInput`, and `disableMobile: true` so the native picker opens on Android/iOS. Do not re-declare a local `flatpickr(...)` call in a view -- that is how the two forms drifted apart.

## The prodi_ids group is starred on its card heading
`prodi_ids` is `required|array|min:1` but the checkbox tree has no single `<label>`; the visual star therefore lives on the card heading: `<h4 class="mb-0"><i ...></i>Fakultas &amp; Program Studi <span class="text-danger">*</span></h4>` in BOTH `buat.blade.php` and `ubah.blade.php` (the `&` is escaped as `&amp;`). The alert below it ("minimal 1") is a hint, not the label. `BeasiswaAccessTest` locks the star on both forms.

## Cakupan = daftar ID PRODI (`beasiswa_cakupan`), bukan salinan nama
Satu beasiswa boleh mencakup BANYAK kampus, banyak fakultas, dan banyak prodi (permintaan pengguna). Cakupan disimpan di tabel `beasiswa_cakupan` sebagai `prodi_id` ASLI dari Master Data; kampus dan fakultas DITURUNKAN lewat `cakupan.prodi.fakultas.kampus`. Jangan pernah kembali menyimpan nama fakultas/prodi sebagai salinan (`beasiswa_fakultas`/`beasiswa_prodi` sudah DIHAPUS): nama salinan bisa melenceng dari Master Data dan aturan lama "semua prodi harus sekampus" ikut hidup lagi.

Kolom teks `beasiswa.kampus` DIPERTAHANKAN karena daftar & pencarian memakainya, tetapi isinya ringkasan yang diturunkan dari prodi terpilih (`BeasiswaController::ringkasanKampus()`), bukan pilihan admin. `kampus_id` di tabel `beasiswa` sudah DIHAPUS.

Semantik cakupan kosong: beasiswa tanpa baris cakupan TERBUKA untuk semua prodi. `Scholarship::untukMahasiswa()` (scope) dan `allowsProdi()` harus tetap identik.

## Form beasiswa: pohon centang kampus -> fakultas -> prodi
`buat.blade.php` dan `ubah.blade.php` sama-sama punya `#prodi-tree` berisi `.kampus-check`, `.fakultas-check`, dan `.prodi-check` (name `prodi_ids[]`) plus tombol `#btn-centang-semua`/`#btn-lepas-semua`. Hanya `prodi_ids[]` yang dikirim ke server; centang kampus/fakultas adalah TURUNAN (checked = semua prodinya tercentang, `indeterminate` bila sebagian). Jangan kembali ke pola lama `showKampusTree()` yang menyembunyikan kampus lain dan `disabled` prodinya.

## Detail beasiswa menampilkan cakupan dari prodi
Blok 'Program Studi yang Bisa Mendaftar' mengelompokkan `$scholarship->cakupan` per kampus lalu per fakultas (`BeasiswaController::show()` meng-eager-load `cakupan.prodi.fakultas.kampus`), diletakkan setelah Periode dan sebelum Deskripsi. Bila cakupan kosong, kalimatnya "Semua Program Studi. Tidak ada pembatasan program studi untuk beasiswa ini" -- BUKAN "semua prodi di kampus X", karena beasiswa tidak lagi terikat satu kampus. Blok yang sama ada di detail user.

## Daftar prodi pakai pl-4, bukan ml-4
Daftar prodi di blok 'Program Studi yang Bisa Mendaftar' memakai `<ul class="mb-0 pl-4">` (indentasi 24px) agar sejajar dengan catatan 'Seluruh program studi pada fakultas ini' (ml-4). Jangan pakai ml-4 pada <ul> - dobel dengan padding-left default ul (~40px) sehingga prodi menjorok ~64px.
