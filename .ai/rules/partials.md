---
paths:
  - 'routes/web.php, app/Http/Controllers/Admin/PenggunaController.php, resources/views/admin/pengguna/**, resources/views/partials/profil-detail.blade.php'
---

# Partials

## admin.pengguna.lihat is a read-only detail page scoped by stage key `diri`
Route order matters: `Route::get('/pengguna/{user}', ...)` MUST be registered AFTER `Route::get('/pengguna/buat')`, otherwise `/{user}` swallows `/pengguna/buat` (Laravel matches in registration order). It inherits its authorization from the `admin.pengguna` menu grant via `akses.menu`, so only kesra + super_admin pass; `show()` adds `abort_unless(auth()->user()->canManageUsers(), 403)` as defense-in-depth. The page reuses `partials/profil-detail` with the new stage key `'diri' => ['diri']` in `$stageCards` — never fork the partial. It deliberately does NOT call `abortUnlessMayTouch()`, so a super_admin target stays readable, but the Ubah button is hidden for super_admin targets to match what the pengguna table already shows. `User::ROLE_LABELS` must be passed as `$roleLabels` from the controller; Blade views cannot resolve `App\Models\User` (no imports). Covered by tests/Feature/Admin/PenggunaDetailTest.php.
