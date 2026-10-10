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

## Detail beasiswa admin menampilkan fakultas & prodi
Blok 'Program Studi yang Bisa Mendaftar' menampilkan fakultas + prodi cakupan (relasi $scholarship->fakultas yang di-eager-load BeasiswaController::show()), diletakkan setelah Periode dan sebelum Deskripsi. Blok yang sama ada di detail user.
