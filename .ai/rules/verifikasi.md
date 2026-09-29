---
paths:
  - 'resources/views/admin/verifikasi/**'
  - resources/views/admin/verifikasi/lihat.blade.php
---

# Verifikasi

## No server-rendered empty-state row inside DataTables tbody
DataTables 1.10 maps `<td>` to columns by position and never honours `colspan`, so a single `@empty` row makes `_fnGetRowElements` return one cell and the initialiser throw on `_DT_CellIndex`. Use a bare `@foreach` (no `@empty`) and put the copy in the `language.emptyTable` option. `verifikasi/index.blade.php` was the only page that broke this.

## Dialog konfirmasi hanya untuk Setujui dan Tolak; opsi Tarik Kembali dihapus
Dropdown keputusan tidak lagi punya opsi "Tarik Kembali (kembalikan ke Menunggu)" -- keputusan jadi permanen setelah dibuat, dan `menunggu` tidak pernah jadi nilai yang bisa disubmit dari form. Konfirmasi SweetAlert memakai PETA `KONFIRMASI` (bukan rantai ternary, karena cabang `default`-nya dulu menyamar jadi teks penarik keputusan saat select masih `""`): hanya `setuju` -> "Yakin setuju?" dan `tolak` -> "Yakin ditolak?" yang dialog. `revisi` dan `— Pilih —` langsung `$form.trigger('submit')` tanpa dialog supaya pesan error inline (Catatan wajib / Status verifikasi harus dipilih) terlihat. Status `menunggu` dipetakan ke `""` saat menentukan opsi terpilih, dan select `pendaftar_status` juga punya placeholder supaya pendaftaran yang belum diputuskan tidak otomatis tampil sebagai "Terima".

## Dropdown keputusan verifikasi selalu dibuka kosong
Semua dropdown keputusan di `admin/verifikasi/lihat.blade.php` — profile (`status`, Capil/Kampus) dan pendaftaran (`pendaftar_status`, Kesra) — WAJIB selalu dibuka di "— Pilih —". Jangan pernah pakai nilai tersimpan sebagai default (`old('status', $decision['status'])`), walau label form sudah "Ubah Keputusan". Alasannya: admin bisa cuma membuka halaman lalu menekan simpan, dan keputusan lama terkirim ulang sebagai keputusan baru — menimpa `diputuskan_at` dan `diputuskan_oleh` tanpa ada keputusan yang sebenarnya diambil. Nilai lama cukup ditampilkan read-only di kotak "Keputusan saat ini" / badge status. `old()` tetap dipakai supaya pilihan tidak hilang saat validasi gagal. Berbeda dengan form pendaftaran beasiswa mahasiswa, yang memang memakai nilai tersimpan sebagai default.

## Antrean Kesra punya view sendiri, terpisah dari daftar profil
Capil dan Campus berbagi `index.blade.php` (kolom dari peta `$stageColumns`); Kesra punya `kesra.blade.php` sendiri karena sumber datanya baris `pendaftar` — kolom, filter, dan aksi berbeda bentuk. Kesra tidak dipaginasi, konsisten dengan dua halaman lain. Label statusnya selalu lewat `Applicant::STATUS_LABELS`/`statusLabel()`/`statusBadge()`, dan kolom Kampus lewat `prodi.fakultas.kampus.nama_kampus` (bukan `->nama`). Opsi "Semua Status" bernilai `Applicant::FILTER_ALL`, bukan string kosong.
