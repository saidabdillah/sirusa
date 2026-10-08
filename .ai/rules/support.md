---
paths:
  - 'app/Support/**'
  - app/Support/VerifikasiAntrean.php
---

# Support

## VerifikasiAntrean is the single source of the verification queue
Filters and ordering live only in `VerifikasiAntrean::antrean()`, and the Excel exports read from it too -- never re-implement the stage rules in a query or an export class, or the list and the download will drift. Careful: `Collection::sortBy()` takes ONE key. Passing an array of closures silently sorts descending on the first and drops the rest; chain `->sortBy(nama)->sortBy(rank)` instead, `sortBy` being stable.

## LABEL kesra di tampilan diturunkan dari `pendaftar.status`, bukan `verif_kesra`
Bukan cuma statistik: badge/label tahap Kesra juga harus diturunkan dari baris `pendaftar`. `verif_kesra` ditulis `setuju` untuk SEMUA keputusan (`KeputusanPendaftaranController::update()`), jadi membacanya sebagai tampilan membuat pendaftaran yang DITOLAK tampil "Disetujui" di halaman Status Verifikasi, dasbor mahasiswa, dan export. Sumbernya `User::kesraDecision()` (agregat `pendaftar.status`: ada `diterima` => Disetujui, selain itu ada `ditolak` => Ditolak, sisanya Menunggu), dengan label/warna dari `UserProfile::decisionForStatus()` (satu pemetaan status->label/badge, dipakai `verifStageDecision()` juga). Tiga pemakainya: `admin/verifikasi/status.blade.php`, `dasbor/mahasiswa.blade.php` (via `$kesraDecision`), `KesraVerifikasiExport` (kolom "Status Kesra"). `verif_kesra` TIDAK diubah: ia tetap penanda "sudah diputuskan" untuk kunci tahap Kampus.

## Tahap Kesra dihitung dari pendaftar, bukan profil
Tahap `kesra` adalah satu-satunya pengecualian: pekerjaannya memutuskan baris `pendaftar`, bukan memverifikasi profil. Statistik, tabel antrean, dan funnel Kesra harus dihitung dari `pendaftar.status`, bukan `verif_kesra` — `verif_kesra` kini hanya penanda bahwa suatu pendaftaran sudah diputuskan. `pendaftarQuery()` hanya membatasi audiens (user aktif + `verif_catpil = 'setuju'` DAN `verif_kampus = 'setuju'` — dua-duanya wajib, bukan cuma Kampus, karena `canVerifStage('kesra')` yang dipegang `show()` menyyaratkan keduanya; tanpa syarat Catpil, antrean bisa menampilkan baris yang membalas 403 saat diklik) dan sengaja TIDAK memfilter status, karena `ringkasan()` butuh semua status; filter status hanya boleh ada di `pendaftaran(?string $status = Applicant::PENDING_STATUS)` (tabel "menunggu putusan"). Kalau filter itu naik ke `pendaftarQuery()`, kartu Disetujui/Ditolak jadi nol dan tabel menampilkan keputusan lama. Kesra juga tidak punya status `revisi`; `ringkasanKeys()`/labels-nya diturunkan dari `Applicant::STATUS_LABELS` supaya tidak ada string status Kesra yang ditulis ulang di view. `pendaftarUsers()` mengembalikan **Eloquent** Collection (query `User::whereKey()` lalu `sortBy` untuk memulihkan urutan antrean) dengan `applicants.beasiswa` ikut eager-load, karena hanya export Kesra yang memakainya — kalau dikembalikan sebagai `Collection` biasa dari `pluck()`, `->load()` dan eager load tidak tersedia.

## Halaman Disetujui memakai `pendaftaran('diterima')`, recompute Hapus membaca baris sisa
Arsip Kesra (`KeputusanPendaftaranController::disetujui()`) memanggil `pendaftaran('diterima')` — jangan tambah query sendiri atau angka arsip dan antrean bisa berbeda. Aksi Hapus (`batalkan()`) menurunkan ulang `verif_kesra` dari baris pendaftar lain milik user yang sama (`whereIn('status', ['diterima','ditolak'])` pada baris selain yang dihapus): masih ada => tetap `setuju`, tidak ada => `menunggu`. Reset buta `verif_kesra = 'menunggu'` akan membuka kembali tahap Kesra padahal pendaftaran lain masih menyimpan keputusan.

## VerifikasiAntrean: profil terverifikasi cukup setuju kampus
Profil "terverifikasi" hanya mensyaratkan `verif_kampus='setuju'` (tidak lagi `verif_catpil`, permintaan pengguna). `pendaftaran()` tanpa argumen tetap default `Applicant::PENDING_STATUS` -- kartu dasbor "Menunggu" mengandalkannya; panggilan TANPA argumen itu (mis. DashboardController) harus tetap mengecualikan yang sudah diputuskan, sedangkan halaman indeks Kesra memanggil dengan `$filter` eksplisit (null = semua status).
