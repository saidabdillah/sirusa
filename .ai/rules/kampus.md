---
paths:
  - 'app/Http/Requests/Kampus/**'
---

# Kampus

## Pangkas spasi field nama sebelum validasi, lewat prepareForValidation
`required` hanya menolak null/string kosong/array kosong, jadi "   " lolos dan bisa membuat kampus/fakultas/prodi bernama spasi yang memblokir nama asli lewat unique index. Pengecekan duplikat di `after()` juga memakai nilai yang belum dipangkas, sehingga "  Teknik  " tidak dianggap bentrok dengan "Teknik". Enam request CRUDampus/Fakultas/Prodi memakai trait `App\Http\Requests\Concerns\TrimsNameInput` di `prepareForValidation()` supaya aturan `required` dan `exists` ikut melihat nilai bersih. Key array harus dipertahankan supaya pesan `nama.0`, `nama.1` masih targeting input yang benar.
