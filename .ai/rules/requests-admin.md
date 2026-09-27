---
paths:
  - app/Http/Requests/Admin/VerifikasiProfilRequest.php
---

# Requests Admin

## Minta Perbaikan (revisi) wajib memakai Catatan
`catatan` memakai `required_if:status,revisi` (bukan `nullable` polos) karena mahasiswa tidak mungkin memperbaiki data kalau tidak pernah diberi tahu bagian mana yang salah. Pola yang sama sudah dipakai `KeputusanPendaftaranRequest` untuk `pendaftaran_catatan` saat pendaftaran ditolak. View `admin/verifikasi/lihat.blade.php` men-toggle atribut `required` + tanda bintang di label, tetapi YANG MENEGAKKAN ADALAH ATURAN INI -- atribut `required` cuma penanda semantik.
