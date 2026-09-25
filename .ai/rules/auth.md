---
paths:
  - 'app/Http/Controllers/Auth/**'
---

# Auth

## No self-registration; accounts are created by admins only
There is no register flow, no OTP, and no public activation. `AuthController` exposes only `masuk` (GET login) and `simpanMasuk` (POST login). Users are created exclusively by admins in `Admin\PenggunaController::store` (peran `user` gets an auto-generated username like `usr<8 random>` and initial password = `12345678`). New statuses default to `aktif`.

## Login accepts NIK, username, then email
`StoreLoginRequest` exposes a single `login` field (`required|string|max:255`). `simpanMasuk` resolves the user in order: (1) by `profil_pengguna.nik`, (2) by `users.username`, (3) by `users.email`. It then `Hash::check`es the password, rejects accounts where `status !== 'aktif'`, and logs in with `Auth::login()` (not `Auth::attempt` with a dynamic column, since NIK lives on another table). Error key and `withInput` use `login`. The view `auth/masuk` labels the field "NIK atau Username" (hint: mahasiswa = NIK, admin = username). On success redirect to `dashboard`.
## Initial password is 12345678, changeable later

Users seeded/created with peran `user` have password = `12345678`. Users change it themselves in `settings/index` (password update); admins can reset any account to `12345678` from the Pengguna list via `admin.pengguna.reset-password` (POST `/pengguna/{user}/reset-password`).