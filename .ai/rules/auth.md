---
paths:
  - 'app/Http/Controllers/Auth/**'
---

# Auth

## No self-registration; accounts are created by admins only
There is no register flow, no OTP, and no public activation. `AuthController` exposes only `masuk` (GET login) and `simpanMasuk` (POST login). Users are created exclusively by admins in `Admin\PenggunaController::store` (peran `user` gets an auto-generated username like `usr<8 random>` and initial password = `12345678`). New statuses default to `aktif`.

## Login accepts NIK, username, then email
`StoreLoginRequest` exposes a single `login` field (`required|string|max:255`). `simpanMasuk` resolves the user in order: (1) by `profil_pengguna.nik`, (2) by `users.username`, (3) by `users.email`. It then `Hash::check`es the password, rejects accounts where `status !== 'aktif'`, and logs in with `Auth::login()` (not `Auth::attempt` with a dynamic column, since NIK lives on another table). Error key and `withInput` use `login`. The view `auth/masuk` labels the field "NIK atau Username" (hint: mahasiswa = NIK, admin = username) — and deliberately carries NO required-star (`*`) on either label: user decision (Oct 2026), login pages follow the plain-login convention even though both fields are server-side required. On success redirect to `dashboard`.
## Initial password is 12345678, changeable later

Users seeded/created with peran `user` have password = `12345678`. Users change it themselves in `settings/index` (password update); admins can reset any account to `12345678` from the Pengguna list via `admin.pengguna.reset-password` (POST `/pengguna/{user}/reset-password`).

## Login and registration are rate limited
`AppServiceProvider::daftarkanPembatasPermintaan()` registers five named limiters: `web` (180/min per user id, or IP when guest), `tulis` (60/min, and `Limit::none()` for GET/HEAD/OPTIONS so reads are never counted), `register` (5/hour per IP), and `login-ip` (20/min per IP). `routes/web.php` applies `throttle:web` + `throttle:tulis` to the whole authenticated group, `throttle:login-ip` to `login.store`, and `throttle:register` to `register.store`.

`AuthController::simpanMasuk()` adds a per-account limit of 5 failed attempts / 60 seconds keyed on `masuk:<lowercased login>|<ip>`: it checks `RateLimiter::tooManyAttempts()` BEFORE verifying the password, returns the error on the `login` key with the remaining seconds, calls `RateLimiter::clear()` on success, and only calls `RateLimiter::hit()` after a failed attempt — a successful login never consumes the quota. Never count failures before the password check or a legitimate user gets locked out by their own successful logins.

Middleware `throttle:` stores keys as `md5(limiterName.key)`; tests that pre-fill a limiter by hand must use the same hash (see `tests/Feature/RateLimitTest.php`).

## Login and registration are rate limited
`AppServiceProvider::daftarkanPembatasPermintaan()` registers named limiters: `web` (180/min per user id, IP when guest), `tulis` (60/min, `Limit::none()` for GET/HEAD/OPTIONS so reads are never counted), `register` (5/hour per IP), `login-ip` (20/min per IP). `routes/web.php` applies `throttle:web` + `throttle:tulis` to the authenticated group, `throttle:web` to the guest group and landing page, `throttle:login-ip` to `login.store`, `throttle:register` to `register.store`.

`AuthController::simpanMasuk()` adds a per-account limit of 5 failed attempts / 60s keyed `masuk:<lowercased login>|<ip>`: check `tooManyAttempts()` BEFORE verifying the password (so a locked account cannot be used to keep probing), `clear()` on success, and `hit()` ONLY after a failed attempt — never count before the check, or a legitimate user burns the quota with their own successful logins. The throttle error is returned on the `login` key with the remaining seconds so it renders inside the form; the IP-level limiter produces the 429 page instead (`errors/429.blade.php`).

`throttle:` middleware stores keys as `md5(limiterName.key)`; tests that pre-fill a limiter by hand must use that same hash (see `tests/Feature/RateLimitTest.php`).

## NIK di tabel `nik_diizinkan` langsung aktif saat mendaftar
Tabel `nik_diizinkan` (kolom `id`, `nik` unik 16 digit) berisi NIK mahasiswa yang sudah terdata dan boleh langsung aktif. `RegisterController::simpan()` mengecek `NikDiizinkan::mengizinkan($nik)` dan menyimpan `status = 'aktif'` alih-alih `'non-aktif'`, dengan pesan sukses berbeda ("Akun Anda sudah aktif, silakan masuk."). `AuthController::simpanMasuk()` juga mengaktifkan akun `non-aktif` saat login berhasil bila NIK-nya ada di tabel itu — supaya NIK yang didaftarkan SETELAH akun dibuat tetap berfungsi. Aktivasi hanya terjadi setelah `Hash::check()` lolos, jadi kata sandi salah tidak pernah mengaktifkan akun. NIK yang tidak terdaftar tetap menunggu admin seperti semula.

Isi tabel lewat `php artisan nik:izinkan {nik|path.csv}` (idempoten, melewati baris judul dan NIK non-16-digit, menerima `"6302...",` dan `6302 0000 0000 0001`), atau `php artisan nik:izinkan {nik|path.csv} --hapus`. Jangan membuat halaman admin untuk tabel ini kecuali diminta: pengguna meminta tabel + perilaku aktivasi saja.
