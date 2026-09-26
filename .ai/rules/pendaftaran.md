---
paths:
  - 'resources/views/user/pendaftaran/**'
---

# Pendaftaran

## Alur konfirmasi, bukan upload
`user/pendaftaran/buat.blade.php` adalah HALAMAN KONFIRMASI. Gate pada `PendaftaranController`, dipisah dua agar `create` dan `store` tidak bisa berbeda: `profilError()` (profil harus `isProfileComplete()` DAN `profile->isVerified()`; kalau tidak, redirect ke `profile` dengan flash `error` "Profil belum lengkap..." / "Profil belum terverifikasi...") lalu `beasiswaError()` (sudah mendaftar beasiswa ini / sudah ada pendaftaran yang memblokir / `eligibilityIssueFor()`; redirect ke `user.beasiswa.lihat` dengan flash `error`). POST menyimpan hanya `beasiswa_id`, lalu `NewApplication` dikirim ke user yang punya grant `admin.pendaftar` dan redirect ke `user.pendaftaran.index`.

## Satu mahasiswa, satu pendaftaran yang memblokir
`PendaftaranController::beasiswaError()` menolak pendaftaran baru bila sudah ada baris `verifikasi` atau `diterima` (`User::blockingApplicant()`). Status `ditolak` dan `dibatalkan` justru MEMBUKA jalan mendaftar lagi, jadi mahasiswa tidak pernah terkunci selamanya. Pembatalan ada di `PendaftaranController::destroy` (`user.pendaftaran.batal`) dan hanya boleh dari status `verifikasi`; mendaftar lagi ke beasiswa yang sama menghidupkan ulang baris yang dibatalkan, bukan membuat baris baru, karena ada index unik `(user_id, beasiswa_id)`. Daftar beasiswa mahasiswa dibatasi kampus profilnya lewat `Scholarship::scopeUntukKampus()`, dan `null` berarti hasil kosong, bukan semua.

## Dokumen & data di kartu detail selalu dari profil
`user/pendaftaran/lihat` menampilkan data & berkas dari `$profile` (BUKAN dari tabel applicant): dokumen via `route('dokumen.show', path)` dengan heading "Dokumen Pendukung"/"Sertifikat Prestasi" (`Prestasi 1`, `Prestasi 2`). Kartu "Data Orang Tua & Wali": ayah & ibu SELALU tampil, wali hanya jika `$profile->kk_ikut_wali`.

## Empat status pendaftar, tanpa `revisi`
`pendaftar.status` adalah `verifikasi` / `diterima` / `ditolak` / `dibatalkan`. Tidak ada `revisi` di tabel ini — `revisi` itu status `verif_*` pada profil. Badge dan label selalu lewat `Applicant::statusBadge()` / `statusLabel()`; jangan tulis `@if` rantai per status di view, karena tiap tambahan enum akan ketinggal satu view.
