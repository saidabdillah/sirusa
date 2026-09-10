---
paths:
  - 'resources/views/user/pendaftaran/**'
---

# Pendaftaran

## Grouping berkas: Diri / Kampus / Orang Tua
Berkas di form Ajukan (buat.blade.php) & Lengkapi (lengkapi.blade.php) dikelompokkan dalam 3 section: "Dokumen Diri Sendiri" (KTP, KK, Akta, Pas Foto, SKTM, Prestasi), "Dokumen untuk Kampus" (Surat Permohonan, Transkrip, Surat Aktif, Surat Pernyataan, Bukti UKT), dan "Dokumen Orang Tua / Wali" (KTP Ayah/Ibu/Wali + KK Wali). Surat Permohonan selalu membawa link Unduh Template (download.application-letter). Hint surat aktif bersifat adaptif berdasar semester ($profile->semester >= 2 → "Wajib", else "Tidak wajib"). Jaga pemetaan ini saat menambah/memindahkan field berkas.
