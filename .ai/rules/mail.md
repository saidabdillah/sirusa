---
paths:
  - 'resources/views/vendor/mail/**'
---

# Mail

## Email header memakai logo SIRUSA dari template publish
Template email telah dipublish (resources/views/vendor/mail). html/header.blade.php selalu render asset('assets/img/stisla-fill.svg') dan MENGABAIKAN slot nama aplikasi — jangan dikembalikan ke teks {!! $slot !!}/logo laravel.com. Tombol/link email pakai primary Stisla #6777ef (html/themes/default.css). Setiap notifikasi (App\Notifications\*) otomatis ikut template ini.

## Header email = wordmark teks SIRUSA, bukan gambar/SVG
Template email telah dipublish (resources/views/vendor/mail). html/header.blade.php menampilkan wordmark teks "SIRUSA" (#6777ef) — JANGAN dipakai kembali SVG/logo gambar (SVG tidak dirender Gmail/Outlook, logo stisla-fill adalah lingkaran putih untuk bg gelap). Tombol/link email tetap primary Stisla #6777ef (html/themes/default.css). Setiap notifikasi App\Notifications\* otomatis ikut template ini.
