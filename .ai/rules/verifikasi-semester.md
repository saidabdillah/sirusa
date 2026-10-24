---
paths:
  - 'app/Models/VerifikasiSemester.php, app/Support/PaketSemester.php, app/Http/Controllers/Admin/VerifikasiSemesterController.php'
---

# Verifikasi per Semester (fase pembinaan penerima beasiswa)

## Dua fase yang terpisah tegas, jangan digabung mekanismenya
Aplikasi punya DUA fase verifikasi, dan keduanya memakai tempat penyimpanan yang berbeda:

1. **Fase seleksi** — Catpil dan Kampus berjalan PARALEL (wilayah pemeriksaan berbeda: kependudukan vs akademik), lalu Kesra memutuskan setelah keduanya `setuju`. Hidup di kolom `profil_pengguna.verif_catpil/kampus/kesra` dan `VerifikasiController`. **Tidak berubah** oleh fitur semester.
2. **Fase semester** — hanya untuk pendaftar yang SUDAH `diterima` (penerima beasiswa). Kampus dan Kesra memeriksa ulang KRS/KHS TIAP SEMESTER. Hidup di tabel `verifikasi_semester`, menempel ke `pendaftar` (bukan `profil_pengguna`).

**Jangan sekali-kali memakai ulang kolom `verif_*` profil untuk siklus semester.** Kolom itu dasar keputusan Kesra yang menjadikan seseorang penerima; kalau di-reset tiap semester, mahasiswa yang sudah sah menerima beasiswa terbaca sebagai belum terverifikasi. Pernah jadi pertimbangan dan ditolak.

## Urutan fase semester BERURUTAN: Kampus dulu, baru Kesra
Beda dengan fase seleksi yang paralel. Di satu semester, keduanya memeriksa dokumen yang SAMA (KRS/KHS semester itu), dan hanya Kampus yang benar-benar memeriksa keaslian berkas. Kalau paralel, Kampus bisa bilang "perlu perbaikan" sementara Kesra sudah bilang "lanjut" — dua pesan bertentangan untuk satu semester.

Di penegakan: `scopeAntreanKesra()` hanya mengambil `status_kampus = setuju`, dan `pastikanBoleh()` menolak Kesra (`403`) untuk paket yang belum disetujui Kampus.

## Catpil tidak pernah muncul di fase semester
Data kependudukan tidak berubah hanya karena mahasiswa naik semester (permintaan pengguna: "catpil tetap 1 kali"). Jangan menambahkan tahap catpil ke tabel/antrean semester.

## Verifikasi Semester adalah ANAK DROPDOWN Kampus dan Kesra, bukan menu sendiri
Menu "Verifikasi Semester" hidup sebagai anak dropdown di section "Verifikasi": satu di bawah induk "Kampus", satu di bawah induk "Kesra" (permintaan pengguna). **Bukan** leaf mandiri (percobaan kedua) dan **bukan** tombol di dalam halaman antrean profil (percobaan pertama, ditolak).

Konsekuensinya: "Kampus" berubah dari leaf menjadi dropdown (anak: "Verifikasi Awal" + "Verifikasi Semester"), dan anak "Verifikasi" di dropdown Kesra berganti label "Verifikasi Awal" supaya tidak tertukar dengan "Verifikasi Semester".

Tahap ditentukan dari PERAN: `VerifikasiSemesterController::tahap()` membaca `route('tahap')` dulu (anak dropdown mengarah ke `admin.semester.kampus` / `admin.semester.kesra`, dua URL untuk satu aksi), lalu `?tahap=`, lalu bawaan kampus. Peran tunggal (kampus/kesra) tetap dipatok dari perannya sendiri supaya tautan yang salah pun tidak membuka tahap orang lain.

## Scope sama boleh dipakai dua menu — kunci grant harus eksplisit
`MenuSeeder` memetakan `scope` menjadi kunci grant di `$keyToId`. Kalau dua menu memakai `scope` yang sama (`admin.semester` di dropdown Kampus DAN Kesra), kunci `scope` saling menimpa dan menu berikutnya menimpa grant menu sebelumnya. Karena itu entri menu boleh membawa `key` eksplisit yang ditulis TERAKHIR (menang atas `scope` dan `Str::kebab($label)`); dipakai untuk `semester-kampus`, `semester-kesra`, `kampus-dropdown`, `kampusverif-awal`, `kesra-dropdown`, `kesraverif-awal`. Alasannya sama untuk induk dropdown: `scope` induk "Kampus" dipakai juga oleh anak "Verifikasi Awal", jadi tanpa `key` terpisah kunci induknya hilang dan dropdown-nya tidak pernah muncul di sidebar.

## Status paket: belum_lengkap bukan keputusan petugas
`status_kampus = belum_lengkap` berarti DOKUMENNYA belum lengkap (KRS, KHS, atau nilai KHS belum ada), bukan hasil pemeriksaan. Paket `belum_lengkap` TIDAK masuk antrean Kampus, supaya petugas tidak melihat pekerjaan setengah jadi.

Sumber kebenaran kelengkapan tetap `dokumen_akademik` (diunggah di halaman profil); `verifikasi_semester` hanya menyimpan keputusan. Jangan menyalin berkas ke tabel baru.

Pemicu masuk antrean: `VerifikasiSemester::sinkron($user)` dipanggil dari `ProfileController::update()` setelah berkas akademik disimpan. Idempoten (`firstOrCreate`), jadi keputusan petugas tidak pernah tertimpa saat mahasiswa menyimpan profil lagi. Jangan menggantinya dengan tombol "ajukan" manual.

## Kesra: selesai / tahan / dicabut — pencabutan harus tindakan sadar
`dicabut` adalah satu-satunya jalur pencabutan beasiswa, dicatat pada paket semester tempat pencabutan diputuskan beserta alasannya (jejak siapa dan kapan). Paket yang sudah `dicabut` menolak semua keputusan berikutnya (`403`).

"Revisi" (Kampus), "tahan", dan "dicabut" (Kesra) WAJIB bercatatan: `ajaxFail()` + `errors.catatan` supaya pesannya muncul di bawah textarea, bukan hanya SweetAlert umum.

## Kelengkapan paket ikut menghitung NILAI KHS
`PaketSemester::kekurangan()` menuntut KRS berberkas, KHS berberkas, DAN KHS bernilai. Nilai KHS dipakai memeriksa IPK masih memenuhi syarat, jadi berkas tanpa nilai belum dianggap lengkap.

## Pengingat di dasbor itu pemberitahuan, bukan andalan kesadaran user
`User::paketSemesterUntukDasbor()` mengembalikan paket yang `belum_lengkap` atau `revisi` milik pendaftaran `diterima`. Alert di `dasbor/mahasiswa.blade.php` MENETAP selama paket belum beres (bukan sekali lewat), dan menyebut semester + dokumen yang kurang + catatan Kampus.

Sistem tidak tahu kapan semester berganti: satu-satunya sumbernya kolom `semester` di profil yang diisi mahasiswa sendiri, dan itulah pemicunya.

## Status per semester tampil di halaman pendaftaran mahasiswa
`user/pendaftaran/lihat` menampilkan kartu "Verifikasi per Semester" (hanya bila `diterima`) dengan label & badge dari `PaketSemester::labelRingkas()/badgeRingkas()`. Jangan menulis ulang `@switch` per status di view: itu tempat kedua label status pernah berbeda. Locked by `VerifikasiSemesterTest` 'halaman pendaftaran mahasiswa menampilkan status tiap semester'.

## Menambah satu menu baru = sentuh seeder + 5 berkas tes
Sama seperti aturan menu lain, tetapi jumlah total menu kini **24** (9 top-level + 15 anak). Yang wajib ikut diperbarui: daftar grant `super_admin` DAN grant role terkait, jumlah total di `SeederTest` (idempotensi), array scope anak di `MenuManagementTest`, `toHaveCount(N)` di `DataTableLoadingTest` bila halaman barunya memakai DataTable, dan daftar isi section di `MenuSidebarTest`.

`MenuSeeder::buangMenuLama()` memangkas menu yang tidak lagi ada di daftar seed, jadi menambah entri di `$menus` + `$grants` sudah cukup; tidak perlu menghapus baris lama manual.