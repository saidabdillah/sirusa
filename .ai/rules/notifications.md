---
paths:
  - 'app/Notifications/**'
  - 'app/Notifications/*.php'
  - 'app/Providers/AppServiceProvider.php'
  - 'resources/views/layouts/partials/navbar.blade.php'
  - 'routes/web.php'
---

# Notifications (fitur DIHAPUS total)

## Jangan menghidupkan kembali fitur notifikasi
Fitur notifikasi dihapus seluruhnya (keputusan user, "hapus total"). Yang sudah dihapus dan HARUS TETAP hilang:

- Tabel `notifications` (migrasinya dihapus), `app/Notifications/*` (NewScholarship, NewApplication, UserActivated, DataVerificationChanged), dan `NotificationController` + route + views `notifications/`.
- Trait `Notifiable` di `App\Models\User` dan SEMUA pemanggilan `notify()` (di VerifikasiController, PendaftaranController, KeputusanPendaftaranController, PenggunaController, BeasiswaController).
- Lonceng notifikasi di `layouts/partials/navbar.blade.php` dan View::composer navbar di `AppServiceProvider` (composer landing tetap ada).
- Test notification lama (NotificationTest, BeasiswaNotifTest) — jangan dipulihkan.

Kalau ada kebutuhan "kabari admin/user", bukan jalur notifikasi: gunakan flash `success`/`error` yang sudah jadi pola aplikasi. Test penjaga: `grep` tidak boleh menemukan `Notifiable`, `->notify(`, atau `Notification::` di `app/` dan `routes/`.
