---
paths:
  - database/seeders/MenuSeeder.php
---

# Seeders

## Route names and menu scopes are camelCase and must match MenuSeeder
`Menu` scopes and route names are all-lowercase dot.separated (`admin.menukelola`, NOT `admin.menuKelola`). `EnsureMenuAccess` matches a route by `str_starts_with($routeName, $scope.'.')`, and a menu whose `route` column names a route that no longer exists makes `route($menu->route)` throw in the sidebar, so casing must match the route table exactly. When adding or renaming a route under a menu, update the seeder in the same commit. `MenuSeeder` is idempotent: menus use `updateOrCreate` keyed on `(section, label, parent_id)` (label alone is ambiguous -- "Daftar Beasiswa" exists in two sections), grants use `insertOrIgnore` (the pivot has a composite primary key), and `buangMenuLama()` prunes menus + orphans left behind by a rename. Running it twice must leave the same 17 menus and the same grants.

## Kunci grant menu yang tidak ketemu harus gagal keras
Kunci grant adalah `scope` menu, atau `Str::kebab($label)` untuk menu tanpa scope (mis. "Master Data" -> `master-data`). Kunci label rapuh: begitu labelnya diubah, kuncinya ikut berubah dan grant-nya hilang tanpa jejak. Gejalanya cuma "menu tiba-tiba tidak muncul" di sidebar, jauh lebih sulit ditelusuri daripada error saat seeding. Karena itu `pasangGrant()` melempar RuntimeException untuk kunci yang tidak ketemu, bukan `continue` diam-diam. Setelah mengganti label atau section di seeder, jalankan `php artisan db:seed` dan pastikan tidak ada exception.

## Ganti section/label di seeder = WAJIB `php artisan db:seed`
`section` ikut menjadi bagian kunci `updateOrCreate(['section','label','parent_id'])`. Kalau hanya kode seeder yang diedit tanpa menjalankan seeder, baris lama TETAP ADA di section lamanya -- `buangMenuLama()` juga tidak menolong karena ia memangkas berdasarkan daftar label, bukan section. Gejalanya khas sidebar: menu hilang tanpa error sama sekali, karena sidebar hanya mengiterasi `Menu::sections()` dan section lamanya sudah tidak ada di sana. Dua langkah wajib: (1) `sections()` di `app/Models/Menu.php` ikut diubah, karena `menus.section` bukan foreign key; (2) `php artisan db:seed` dijalankan supaya baris lama benar-benar pindah. Efek samping lain: sesi user yang masih terbuka memegang sidebar dari grant lama sampai page reload.

## Section sidebar = pengelompokan tampilan, bukan kontrol akses
Section tidak menambah atau mengurangi permission: `role_menu` tetap satu-satunya sumber akses, dan `User::sidebarMenus()` tidak memfilter `section` sama sekali (hanya `parent_id` null + `aktif` + granted). Jadi saat memindahkan menu ke section lain, `scope`, `route`, `icon`, `urutan`, dan isi `$grants` TIDAK boleh ikut berubah -- kalau ikut berubah, itu perubahan permission, bukan perubahan tampilan, dan harus diuji sebagai perubahan akses. Pasangan label -> section beserta urutannya saat ini dikunci di `tests/Feature/SeederTest.php` ("setiap menu punya section yang sesuai di sidebar") supaya edit seeder tanpa `db:seed` langsung ketahuan.

## Label menu verifikasi sudah dipangkas, jangan dikembalikan
Tiga antrean verifikasi bernama `Dukcapil`, `Kampus`, `Kesra` di section `Verifikasi` -- bukan `Verifikasi Capil`/`Verifikasi Kampus`/`Verifikasi Kesra`. Prefiksnya dihapus karena header section sudah menyebut prosesnya. `scope` (`admin.capil`, `admin.kampusverif`, `admin.kesra`) tidak berubah sama sekali, jadi grant dan permission aman. Konsekuensi yang harus diingat: `Kampus` sekarang dipakai dua menu (antrean verifikasi di section `Verifikasi` dan Master Data di section `Administrasi`) -- mereka tidak bentrok di DB karena `parent_id` dan `section`-nya berbeda, tapi tes sidebar WAJIB mencocokkan `href`/`scope`, bukan teks label, dan teks konfirmasi hapus di "Kelola Menu" jadi ambigu ("Hapus menu 'Kampus'?" untuk dua menu). String "Verifikasi Capil/Kampus/Kesra" di `app/Support/VerifikasiAntrean.php`, `app/Notifications/DataVerificationChanged.php`, dan `DashboardController::actorLabel()` adalah nama tahap/aktor, bukan label menu -- sengaja tidak ikut diubah.
