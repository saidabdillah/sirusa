---
paths:
  - public/assets/js/custom.js
---

# Js

## Select2 init di kelola menu: `tags` + `dropdownParent` modal
Select2 4.0.13 (bundled di `layouts/app.blade.php`, CSS L21 / JS L74) dipakai PERTAMA KALI di `resources/views/admin/menu/kelola.blade.php` — dua select `.select2-baru` (`parent_id` + `section`) di dalam kedua modal. Init ada di `@push('script')` existing (atas blok, `.each()`), bentuknya terkunci test: `dropdownParent: $select.closest('.modal')` (WAJIB — tanpa ini dropdown terpotong `overflow` modal dan tidak bisa diklik), `placeholder: $select.data('placeholder')`, `tags: true` (admin bisa mengetik menu induk/section BARU langsung), `allowClear: true` (butuh `<option value="">` yang tetap ada di markup), `width: '100%'`. Jangan pindahkan init ke `resources/js/app.js` (import select2 di sana tidak dipakai layout mana pun) dan jangan init global di `custom.js` — halaman lain belum tentu memuat modalnya. Komentar JS di blok itu tetap wajib bebas direktif Blade (rule di bawah).

**Pesan error AJAX wajib DI BAWAH kotak select2:** Select2 menyisipkan kotak yang terlihat tepat setelah `<select>` aslinya (`$container.insertAfter(this.$element)`). Jadi `$anchor.after($feedback)` di `paintFieldError()` — yang berarti "setelah `<select>`" — menaruh `invalid-feedback[data-ajax]` DI ATAS kotak select2 (kotak menyusul di belakangnya). `paintFieldError()` kini mendeteksi field select2 (`hasClass("select2-hidden-accessible")`) dan menyisipkan feedback setelah `$el.next(".select2-container")`. Kalau menambah field select2 baru, jalur ini wajib tetap ada; render-server (reload) sudah benar karena pesan dirender sebelum select2 init.

## Success WAIT timer 1500 tanpa tombol OK, dan refresh HANYA di dalam `.then()`
`submitAjax()` punya satu `Swal.fire` success untuk seluruh aplikasi (35 form di 34 view), jadi bentuknya dipin di sini: `timer: 1500` + `showConfirmButton: false` + `timerProgressBar: true`, `text` dari `payload.message`, dan `refreshAfterSave($form, payload)` DI DALAM `.then()`.

Dua trap yang menjadi penyebab bug di balik rule ini:

- **`showConfirmButton: false` tanpa `timer` tidak menutup apa pun.** Alertnya menggantung dan user harus menekan dua kali untuk menyelesaikan satu aksi. Keduanya harus berpasangan; menambah `showConfirmButton: false` saja adalah regresi.
- **Refresh harus di dalam `.then()`.** Dipindah ke luar, navigasi berjalan bersamaan dengan animasi alert dan success-nya tidak pernah terlihat -- persis gejala "pesan langsung hilang saat reload".

Jalur `payload.success === false` (backend membalas HTTP 200 dengan `success:false`) TIDAK boleh mewarisi timer itu: pesan error butuh tombol OK karena user harus membacanya dan memperbaikinya. `.fail()` juga tidak boleh pernah memanggil `refreshAfterSave()` atau navigasi -- error (422/419/403/500/status 0) selalu meninggalkan user di form yang sama supaya isinya tidak hilang. `window.location.assign` hidup hanya di dalam `refreshAfterSave()`, dan fungsi itu hanya dipanggil dari `.then()` success. Jaminan bentuknya diuji di `tests/Feature/LoadingStandarTest.php` (bagian "Success: alert dulu, refresh belakangan") -- termasuk bahwa blok `.fail()` tidak mengandung navigasi setelah komentar satu-baris penuh dibuang.

## submitWithSpinner() dijaga idempoten karena dua handler submit
Form `data-ajax-form` melewati `submitWithSpinner()` DUA kali per request: handler umum `on("submit", "form")` lalu handler delegasi `on("submit", "form[data-ajax-form]")` yang memanggil `submitAjax()`. Keduanya terikat di `document` secara berurutan dan `preventDefault()` tidak menghentikan handler pertama. Karena itu stash `data-loading-html` hanya ditulis kalau belum ada, dan `releaseSubmit()` membuangnya setelah memulihkan. Tanpa penjaga, pemanggilan kedua menimpa stash dengan HTML spinner sehingga tombolnya `[⟳ Menyimpan...]` selamanya setelah request selesai. Tombol `type="button"` yang submit-nya dipicu handler (mis. `Simpan Keputusan` yang dikunci konfirmasi SweetAlert) harus ditandai `data-loading-button`, karena `tombolSubmit()` mencari `button[type="submit"]` lebih dulu dan tanpa penanda itu formnya jalan tanpa spinner sama sekali.

## findField wajib empat tahap, bukan tiga
Error validasi per-index dari Laravel sampai sebagai `nama_kampus.0` (dari aturan `nama_kampus.*`), sedangkan Blade merender repeater-nya sebagai `nama_kampus[]`. Tiga tahap awal findField() hanya menangani bentuk `field`, `field[]`, dan `field[` sehingga error per-index tidak pernah ketemu field-nya: paintFieldError() mengembalikan false, `painted` tetap 0, dan errornya jatuh ke SweetAlert umum -- persis pelanggaran 'error wajib inline di bawah input'. Tahap keempat mem-parse indeks `.(\d+)$` lalu memilih baris repeater yang tepat dengan `.eq()`. Kalau menambah aturan validasi `field.*`, pastikan view-nya tetap memakai `field[]` dan tahap ini masih ada.

## Flatpickr v4 hanya menyalin class SEKALI, jadi `is-invalid` hasil AJAX tidak akan terlihat
Flatpickr menyembunyikan input aslinya dengan `w.input.setAttribute("type","hidden")` lalu menyisipkan `.flatpickr-alt` sebagai saudara langsungnya dengan `parentNode.insertBefore(altInput, w.input.nextSibling)`. `altInputClass` dihitung SEKALI saat inisialisasi (`altInputClass = el.className + " " + config.altInputClass`), sehingga:

- error yang sudah ada saat halaman dirender ikut tersalin ke alt-input -> tampil benar;
- error 422 dari `paintFieldError()` datang SETELAH inisialisasi -> `is-invalid` di input asli tidak akan pernah terlihat, karena yang diklik user adalah `.flatpickr-alt`. Yang tampil cuma pesan errornya, tanpa border merah. Ini persis pelanggaran "error wajib inline di bawah input".

Perbaikannya ada di `custom.js`: `cerminIsInvalid($el)` mencerminkan `is-invalid` ke `$el.siblings(".flatpickr-alt")`, dipanggil dari `paintFieldError()` (saat menandai) dan dibersihkan lagi di `clearFormErrors()` bersama `$form.find(".is-invalid")`. Kalau menambah field flatpickr baru, dua hal yang wajib dijaga: (1) `cerminIsInvalid()` harus tetap dipanggil di `paintFieldError()`, jangan menggantinya dengan penambahan class langsung; (2) jangan pernah memanggil `initDatePicker()` ulang atau me-`flatpickr()`-kan input yang sudah ada, karena itu membuat alt-input kedua dan mengembalikan masalah class-copy-once ini.

Perilaku ini bergantung pada detail internal Flatpickr v4, jadi rule ini harus dicek ulang kalau versinya dinaikkan.

Semua field flatpickr di app: `tanggal_mulai`/`tanggal_selesai` di `admin/beasiswa/{buat,ubah}.blade.php` dan `tanggal_lahir` di `profil/index.blade.php` (yang memanggil `flatpickr()` langsung, bukan lewat `initDatePicker`, tapi punya masalah yang sama). Semuanya wajib punya `@error` + `invalid-feedback` sendiri per field.

## Komentar Blade di dalam blok `<script>`/@push diam-diam ikut terkompilasi
Blade mengompilasi seluruh file, dan `@include` di-inline ke file induk -- jadi isi `@push('script')` di `kelola.blade.php` beserta partial yang di-`@include`-nya ikut dikompilasi sebagai PHP. Menulis `@if(...)` di dalam komentar JS akan membuat Blade mencoba mengompilnya dan seluruh halaman gagal dengan "syntax error, unexpected end of file, expecting elseif/else/endif". Komentar di dalam `@push('script')` harus bebas dari direktif Blade (`@if`, `@foreach`, `@php`, `@error`, ...), termasuk di dalam teks kutipan. Selain itu komentar itu ikut dikirim ke browser, jadi jangan menulis hal yang tidak pantas dibaca user.
