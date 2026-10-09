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
`PendaftaranController::beasiswaError()` menolak pendaftaran baru bila sudah ada baris `verifikasi` atau `diterima` (`User::blockingApplicant()`). Status `ditolak` dan `dibatalkan` justru MEMBUKA jalan mendaftar lagi, jadi mahasiswa tidak pernah terkunci selamanya. Mendaftar lagi ke beasiswa yang sama menghidupkan ulang baris yang dibatalkan, bukan membuat baris baru, karena ada index unik `(user_id, beasiswa_id)`. Daftar beasiswa mahasiswa dibatasi kampus profilnya lewat `Scholarship::scopeUntukKampus()`, dan `null` berarti hasil kosong, bukan semua.

## Pembatalan hanya di Halaman Detail, dan hanya sampai masa pendaftaran ditutup
`user.pendaftaran.batal` (`PendaftaranController::destroy`) tidak lagi punya tombol di `user/pendaftaran/index`. Tabel memuat banyak baris sekaligus, jadi dari sana satu klik "Batalkan" berarti membatalkan pendaftaran yang judulnya tidak terlihat user. Satu-satunya tempatnya adalah `user/pendaftaran/lihat`, di dalam `@if($applicant->canBeCancelled())`, memakai `.btn-confirm-toggle` (konfirmasi global) + `data-ajax-form`.

Aturan pembatalan TIDAK di controller, tapi di `Applicant::cancellationBlockedReason()` -- satu sumber kebenaran untuk tombol di view dan penolakan di endpoint. `canBeCancelled()` hanya `cancellationBlockedReason() === null`. Syaratnya dua, dan tidak ada status/kolom/migration baru:

1. masih `verifikasi` (`dibatalkan` = sudah pernah dibatalkan; `diterima`/`ditolak` = keputusan Kesra, `isDecided()`);
2. masa pendaftaran beasiswa belum ditutup: `now() <= beasiswa.tanggal_selesai`.

Batas waktu memakai `beasiswa.tanggal_selesai` dengan sengaja, karena itu batas yang sama dengan `scopeTersedia()`/`eligibilityIssueFor()` -- "boleh batal" dan "beasiswa masih buka" tidak mungkin berbeda, dan tidak ada angka baru yang harus dis-tuned. `pendaftar.created_at` TIDAK boleh dipakai: `store()` menghidupkan ulang baris yang dibatalkan tanpa me-reset `created_at`, jadi nilainya berasal dari pendaftaran pertama. `updated_at` juga tidak cocok karena keputusan Kesra ikut memutusnya. Beasiswa null atau `tanggal_selesai` null dianggap sudah lewat batas (fail-safe).

`destroy()` tetap memanggil `cancellationBlockedReason()` sendiri dan membandingkan `user_id` dengan `auth()->id()` (`abort(403)`) -- menyembunyikan tombol tidak pernah menggantikan validasi, karena endpointnya bisa dipanggil langsung. Ownership dicek inline; tidak ada policy untuk `Applicant`.

Modal pembatalan yang pernah ada sudah dihapus. Kalau modal serupa ditambahkan lagi, taruh di `@push('modal')`, bukan `@section('content')`: layout mencetak stack itu di level `<body>`, sedangkan modal di dalam `.main-content` punya stacking context sendiri sehingga `position: fixed`-nya membuat modal turun di belakang `.modal-backdrop` miliknya sendiri -- jendela tak terlihat dan tak bisa diklik.

## Dokumen & data di kartu detail selalu dari profil
`user/pendaftaran/lihat` menampilkan data & berkas dari `$profile` (BUKAN dari tabel applicant): dokumen via `route('dokumen.show', path)` dengan heading "Dokumen Pendukung"/"Sertifikat Prestasi" (`Prestasi 1`, `Prestasi 2`). Kartu "Data Orang Tua & Wali": ayah & ibu SELALU tampil, wali hanya jika `$profile->kk_ikut_wali`.

## Empat status pendaftar, tanpa `revisi`
`pendaftar.status` adalah `verifikasi` / `diterima` / `ditolak` / `dibatalkan`. Tidak ada `revisi` di tabel ini — `revisi` itu status `verif_*` pada profil. Badge dan label selalu lewat `Applicant::statusBadge()` / `statusLabel()`; jangan tulis `@if` rantai per status di view, karena tiap tambahan enum akan ketinggal satu view.
