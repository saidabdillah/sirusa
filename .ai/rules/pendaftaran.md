---
paths:
  - 'resources/views/user/pendaftaran/**'
---

# Pendaftaran

## Grouping berkas: Diri / Kampus / Orang Tua
Berkas di form Ajukan (buat.blade.php) & Lengkapi (lengkapi.blade.php) dikelompokkan dalam 3 section: "Dokumen Diri Sendiri" (KTP, KK, Akta, Pas Foto, SKTM, Prestasi), "Dokumen untuk Kampus" (Surat Permohonan, Transkrip, Surat Aktif, Surat Pernyataan, Bukti UKT), dan "Dokumen Orang Tua / Wali" (KTP Ayah/Ibu/Wali + KK Wali). Surat Permohonan selalu membawa link Unduh Template (download.application-letter). Hint surat aktif bersifat adaptif berdasar semester ($profile->semester >= 2 → "Wajib", else "Tidak wajib"). Jaga pemetaan ini saat menambah/memindahkan field berkas.

Gui yang sama (3 section + heading persis seperti di atas + "Sertifikat Prestasi") dipakai di kartu "Dokumen Pendukung" pada halaman detail user (`user/pendaftaran/lihat`) dan admin (`admin/pendaftar/lihat`) — user WAJIB melihat semua berkas yang diunggahnya termasuk KTP Ayah/Ibu/Wali + KK Wali, dan list admin harus mencerminkan field upload. PendaftaranController menyimpan file ke `Storage::disk('public')` (folder `pendaftaran/{user}/{beasiswa}`) agar `asset('storage/...')` tersaji lewat symlink `public/storage` — jangan pindah ke disk `local` (root-nya di-override ke `/home/sirusa/storage` dan tidak bisa diakses lewat URL storage).
