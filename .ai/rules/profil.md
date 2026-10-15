---
paths:
  - 'app/Http/Requests/Profil/**'
---

# Profil

## Nomor HP Indonesia dibatasi 08 + 10–13 digit; NIK/no HP pakai Cleave angka
`UpdateProfilRequest` memvalidasi `telepon` dengan `regex:/^08[0-9]{8,11}$/` (diawali `08`, total 10–13 digit) — nomor asing sengaja TIDAK diterima karena kolom ini khusus nomor HP Indonesia (keputusan pengguna). Form `profil/index` memasang Cleave `numericOnly: true` dengan `blocks: [16]` untuk `#nik` dan `blocks: [13]` untuk `#telepon` (di dalam blok `if (typeof Cleave !== 'undefined' && document.getElementById('nik'))`, sebelum blok UKT), sehingga huruf tertahan sejak ketikan pertama dan digit tidak bisa melewati batas. Aturan server tetap penutup jalur tanpa JavaScript — jangan hapus salah satunya. Tiap input berkas juga WAJIB punya `<small class="text-muted">Format: PDF, JPG, JPEG, PNG, atau WEBP. Ukuran maksimal 2 MB.</small>` (dokumen prestasi menambah "per file"); teksnya sengaja identik supaya satu tes bisa menghitung kemunculannya. Covered by tests/Feature/ProfilTeleponDanAlertTest.php.

## Every * field in the profile form is required; files are exempt only when already stored
The `*` in resources/views/profil/index.blade.php is a real contract: a field marked `*` MUST have a `required` rule, an Indonesian `@error` message below the input, and `is-invalid` on the control (radios need it on the input itself). Rules come from `UpdateProfilRequest::rules()`. Two traps: (1) file fields use the private `dokumen()` helper, which returns `nullable` instead of `required` when `$this->user()?->profile?->{$field}` is already filled, so editing a name does not force re-uploading 11 documents; (2) wali fields are gated on `$this->input('ikut_kk') === 'wali'`, NOT `required_if` — `required_if` validates hidden fields and the user cannot see or fix the error. Never add an HTML `required` attribute (asserted in ProfilTest). `nama_kampus`/`fakultas` are validated but not fillable, so they are dropped by `fill()` by design.

## Normalize rupiah input in prepareForValidation, never in the controller
`UpdateProfilRequest::normalisasiRupiah()` strips every non-digit from `ukt` before validation. Doing it in the controller or the view left the raw string reaching `numeric`, which silently stored 0. The `decimal:0` cast on `UserProfile::$casts` is the other half: without it the `decimal(10,2)` column hands Blade `1500000.00` and Cleave then ships `150000000`.
