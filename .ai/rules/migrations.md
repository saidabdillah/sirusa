---
paths:
  - 'database/migrations/**'
---

# Migrations

## Edit the existing migration, never add a compensating one
Schema changes go straight into the migration that owns the column (e.g. adding a `dibatalkan` value to the `pendaftar.status` enum means editing `create_pendaftar_table.php`). Do not add a follow-up `alter` migration. The one thing you MUST tell the user: their local database has already run the old migration, so the edit will not apply until they run `migrate:fresh`.

## Status "sudah dipakai" menentukan: sunting migrasi create atau bikin migrasi baru
Aturan di atas berlaku SELAMA Said belum menyatakan SIRUSA dipakai. Begitu Said bilang "sirusa sudah dipakai" (ada data produksi yang tidak boleh hilang), aturannya BERBALIK: perubahan skema wajib lewat migrasi `add_*` / `alter` baru, dan `migrate:fresh` TIDAK boleh dijalankan di produksi. Jangan menyimpulkan sendiri dari isi database — database bisa berisi nol baris (hanya akun superadmin + menu) sementara Said tetap menganggapnya "sudah dipakai". Tunggu pernyataan eksplisit, lalu ubah catatan ini.

Konsolidasi yang sudah dilakukan (Oct-2026, sirusa belum dipakai): `2026_10_16_000001_add_pendidikan_dan_domisili_to_profil_pengguna_table`, `2026_10_16_000002_rename_wali_menjadi_famili_lain`, dan `2026_10_16_000004_extend_tingkat_gelar_beasiswa` DIHAPUS, isinya dipindahkan ke migrasi create pemiliknya (`2026_08_25_000002_create_profil_pengguna_table`: `jenjang`, `tahun_masuk`, `kepala_keluarga`, `dokumen_pddikti`, `berdomisili_sejak`, enum `ikut_kk` empat nilai, `kk_ikut_famili_lain`; dan `2026_08_25_000003_create_beasiswa_table`: `tingkat_gelar` D1-S3). Cara membuktikan hasilnya setara: bangun database uji lewat `migrate:fresh`, lalu bandingkan `information_schema.COLUMNS` (nama, tipe, nullable, default), `ORDINAL_POSITION`, dan `information_schema.STATISTICS` tabel yang sama antara database produksi dan database uji — harus IDENTIK sebelum produksi disentuh. Di produksi, baris `migrations` untuk file yang dihapus ikut dihapus manual supaya daftarnya cocok dengan file yang ada (`php artisan migrate:status` harus nol Pending).
