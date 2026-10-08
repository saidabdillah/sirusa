---
paths:
  - 'resources/views/user/pendaftaran/**'
---

# Pendaftaran

## Alur konfirmasi, bukan upload
`user/pendaftaran/buat.blade.php` adalah HALAMAN KONFIRMASI. Gate pada `PendaftaranController`, dipisah dua agar `create` dan `store` tidak bisa berbeda: `profilError()` (profil hanya harus `isProfileComplete()`; kalau tidak, redirect ke `profile` dengan flash `error` "Profil belum lengkap...") lalu `beasiswaError()` (sudah mendaftar beasiswa ini / sudah ada pendaftaran yang memblokir / `eligibilityIssueFor()`; redirect ke `user.beasiswa.lihat` dengan flash `error`). POST menyimpan hanya `beasiswa_id` dan redirect ke `user.pendaftaran.index`.

## Verifikasi bukan prasyarat mendaftar (ALUR BARU)
Mahasiswa TIDAK perlu menunggu verifikasi Catpil/Kampus/Kesra untuk mendaftar beasiswa: profil lengkap sudah cukup. Verifikasi tiga tahap jalan SETELAH pendaftaran dikirim (dasbor mahasiswa menampilkan status per tahap lewat `verifStageDecision()`/`verifStatus()`). Gate `isCatpilVerified()`/`profile->isVerified()` SENGAJA DIHAPUS dari `profilError()`, `BeasiswaController::show`, dan view `user/beasiswa/lihat` + `user/pendaftaran/buat` (alert-nya kini menyebut "akan diverifikasi oleh Catpil, Kampus, dan Kesra setelah pendaftaran ini dikirim"). Jangan menghidupkan kembali gate itu — test `ApplicantFlowTest` ('application accepted without waiting for catpil verification', 'confirmation page shows even when profile not yet verified') menjaganya.

## Satu mahasiswa, satu pendaftaran yang memblokir
`PendaftaranController::beasiswaError()` menolak pendaftaran baru bila sudah ada baris `verifikasi` atau `diterima` (`User::blockingApplicant()`). Status `ditolak` dan `dibatalkan` justru MEMBUKA jalan mendaftar lagi, jadi mahasiswa tidak pernah terkunci selamanya. Pembatalan ada di `PendaftaranController::destroy` (`user.pendaftaran.batal`) dan hanya boleh dari status `verifikasi`; mendaftar lagi ke beasiswa yang sama menghidupkan ulang baris yang dibatalkan, bukan membuat baris baru, karena ada index unik `(user_id, beasiswa_id)`. Daftar beasiswa mahasiswa dibatasi kampus profilnya lewat `Scholarship::scopeUntukKampus()`, dan `null` berarti hasil kosong, bukan semua.

## Dokumen & data di kartu detail selalu dari profil
`user/pendaftaran/lihat` menampilkan data & berkas dari `$profile` (BUKAN dari tabel applicant): dokumen via `route('dokumen.show', path)` dengan heading "Dokumen Pendukung"/"Sertifikat Prestasi" (`Prestasi 1`, `Prestasi 2`). Kartu "Data Orang Tua & Wali": ayah & ibu SELALU tampil, wali hanya jika `$profile->kk_ikut_wali`.

## Empat status pendaftar, tanpa `revisi`
`pendaftar.status` adalah `verifikasi` / `diterima` / `ditolak` / `dibatalkan`. Tidak ada `revisi` di tabel ini — `revisi` itu status `verif_*` pada profil. Badge dan label selalu lewat `Applicant::statusBadge()` / `statusLabel()`; jangan tulis `@if` rantai per status di view, karena tiap tambahan enum akan ketinggal satu view.
