<?php

namespace Database\Seeders;

use App\Support\MenuAction;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission aksi (read/create/edit/update/delete) untuk modul CRUD.
 *
 * Dipisah dari `MenuSeeder` supaya bisa dijalankan sendiri
 * (`php artisan db:seed --class=PermissionSeeder`) tanpa menyentuh pohon menu.
 * Grant default-nya ikut di sini, karena permission tanpa grant tidak berguna.
 *
 * Hanya modul CRUD (`MenuAction::MODULES`) yang punya aksi granular; modul lain
 * tetap dijaga grant menu seperti semula.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Cache registrar dibuang DUA kali dengan sengaja. `findOrCreate()`
        // membaca cache untuk memutuskan "sudah ada atau belum", sedangkan
        // `syncPermissions()` memakai `Permission::findByName()` yang MELEMPAR
        // `PermissionDoesNotExist` begitu namanya tidak ada di cache. Tanpa
        // pembuangan kedua, permission yang baru dibuat di baris atas masih
        // dianggap tidak ada di baris bawah.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (MenuAction::allPermissionNames() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Model diambil ulang dari database dan dioper sebagai model (bukan
        // string nama): `syncPermissions()` dengan string akan memanggil
        // `findByName()` lagi, dan itulah jalur yang melempar tadi.
        //
        // Daftar permission disaring dengan `filter()`, BUKAN `only()`:
        // `Collection::only()` di Laravel versi ini memperlakukan key bertitik
        // ("admin.pengguna.read") sebagai path bersarang, sehingga hasilnya
        // selalu kosong dan role tidak pernah dapat permission apa pun.
        $semua = Permission::query()
            ->whereIn('name', MenuAction::allPermissionNames())
            ->get();

        // Grant default:
        // - `super_admin` selalu penuh. `User::hasMenuAction()` juga mematok itu
        //   di level model, jadi halaman Akses Menu tidak bisa mengunci sistem.
        // - `kesra` memegang modul Pengguna sepenuhnya KECUALI hapus, sama
        //   dengan perilaku sebelum aksi granular ada.
        // - Role lain tidak dapat apa-apa sampai admin memberikannya lewat
        //   halaman Akses Menu.
        $grants = [
            'super_admin' => MenuAction::allPermissionNames(),
            'kesra' => [
                MenuAction::permissionName('admin.pengguna', MenuAction::READ),
                MenuAction::permissionName('admin.pengguna', MenuAction::CREATE),
                MenuAction::permissionName('admin.pengguna', MenuAction::EDIT),
                MenuAction::permissionName('admin.pengguna', MenuAction::UPDATE),
            ],
        ];

        foreach ($grants as $roleName => $permissions) {
            // `Role::findByName()` juga melempar bila role-nya belum ada, jadi
            // role dicari lewat query biasa: seeder ini harus aman dijalankan
            // pada database yang belum punya role sama sekali.
            $role = Role::query()->where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $role->syncPermissions(
                $semua->filter(fn (Permission $permission) => in_array($permission->name, $permissions, true))->values()->all(),
            );
        }

        // Role di luar daftar `$grants` sengaja TIDAK di-sync: permission yang
        // sudah diatur admin lewat halaman Akses Menu tidak boleh terhapus
        // setiap kali seeder dijalankan.

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
