---
paths:
  - 'resources/views/user/pendaftaran/**'
---

# Pendaftaran

## Alur konfirmasi, bukan upload
`user/pendaftaran/buat.blade.php` adalah HALAMAN KONFIRMASI. Gate pada `PendaftaranController::create`/`store`: profil harus `isProfileComplete()` DAN `profile->isVerified()`; jika tidak, redirect ke `profile` dengan flash `error` (pesan "Profil belum lengkap..." / "Profil belum terverifikasi..."). Ketidaklayakan beasiswa (IPK/semester/batas waktu/prodi/sudah mendaftar) redirect ke `user.beasiswa.lihat` dengan flash `error`. POST menyimpan hanya `beasiswa_id`, lalu `NewApplication` dikirim ke user yang punya grant `admin.pendaftar` dan redirect ke `user.pendaftaran.index`.

## Dokumen & data di kartu detail selalu dari profil
`user/pendaftaran/lihat` dan `admin/pendaftar/lihat` menampilkan data & berkas dari `$profile` (BUKAN dari tabel applicant): dokumen via `route('dokumen.show', path)` dengan heading user "Dokumen Pendukung"/"Sertifikat Prestasi" (`Prestasi 1`, `Prestasi 2`) dan heading admin "Dokumen Diri Sendiri", "Dokumen untuk Kampus", "Dokumen Orang Tua / Wali", "KTP Ayah/Ibu/Wali", "Kartu Keluarga Wali". Kartu "Data Orang Tua &amp; Wali": ayah & ibu SELALU tampil, wali hanya jika `$profile->kk_ikut_wali`. Status pendaftar hanya `verifikasi`/`diterima`/`ditolak` (tidak ada `revisi`).