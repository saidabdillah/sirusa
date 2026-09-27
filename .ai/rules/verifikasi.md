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
