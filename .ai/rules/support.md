---
paths:
  - 'app/Support/**'
  - app/Support/VerifikasiAntrean.php
---

# Support

## VerifikasiAntrean is the single source of the verification queue
Filters and ordering live only in `VerifikasiAntrean::antrean()`, and the Excel exports read from it too -- never re-implement the stage rules in a query or an export class, or the list and the download will drift. Careful: `Collection::sortBy()` takes ONE key. Passing an array of closures silently sorts descending on the first and drops the rest; chain `->sortBy(nama)->sortBy(rank)` instead, `sortBy` being stable.

## Tahap Kesra dihitung dari pendaftar, bukan profil
Tahap `kesra` adalah satu-satunya pengecualian: pekerjaannya memutuskan baris `pendaftar`, bukan memverifikasi profil. Statistik, tabel antrean, dan funnel Kesra harus dihitung dari `pendaftar.status`, bukan `verif_kesra` — `verif_kesra` kini hanya penanda bahwa suatu pendaftaran sudah diputuskan. `pendaftarQuery()` hanya membatasi audiens (user aktif + `verif_capil = 'setuju'` DAN `verif_kampus = 'setuju'` — dua-duanya wajib, bukan cuma Kampus, karena `canVerifStage('kesra')` yang dipegang `show()` menyyaratkan keduanya; tanpa syarat Capil, antrean bisa menampilkan baris yang membalas 403 saat diklik) dan sengaja TIDAK memfilter status, karena `ringkasan()` butuh semua status; filter status hanya boleh ada di `pendaftaran(?string $status = Applicant::PENDING_STATUS)` (tabel "menunggu putusan"). Kalau filter itu naik ke `pendaftarQuery()`, kartu Disetujui/Ditolak jadi nol dan tabel menampilkan keputusan lama. Kesra juga tidak punya status `revisi`; `ringkasanKeys()`/labels-nya diturunkan dari `Applicant::STATUS_LABELS` supaya tidak ada string status Kesra yang ditulis ulang di view. `pendaftarUsers()` mengembalikan **Eloquent** Collection (query `User::whereKey()` lalu `sortBy` untuk memulihkan urutan antrean) dengan `applicants.beasiswa` ikut eager-load, karena hanya export Kesra yang memakainya — kalau dikembalikan sebagai `Collection` biasa dari `pluck()`, `->load()` dan eager load tidak tersedia.
