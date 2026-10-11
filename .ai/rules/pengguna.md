---
paths:
  - 'resources/views/admin/pengguna/**'
---

# Pengguna

## Label tampil 'Role', field/kunci tetap `peran`
Teks yang dilihat pengguna memakai 'Role' (label form buat/ubah, kolom index yang sudah 'Role', `<dt>Role</dt>` di settings/index, dan atribut `'peran' => 'Role'` di lang/id/validation.php). Pesan validasi ikut: 'Role harus dipilih', 'Role tidak valid', 'Anda tidak dapat memberikan role Super Admin' (StoreUserRequest/UpdateUserRequest), dan 'Anda tidak dapat mengubah role ...' (PenggunaController). JANGAN ubah nama field/identitas: `name="peran"`, `id="peran"`, `$peranOptions`, `$peranValue`, `$peranLabel`, key validasi `'peran'`, var JS `peran`. Pola yang sama seperti 'Program Studi' vs `jurusan_id` di .ai/rules/views-admin.md. Alasan: halaman index/lihat pengguna sudah memakai 'Role', jadi 'Peran' membuat istilah tidak konsisten.
