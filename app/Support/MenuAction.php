<?php

namespace App\Support;

use App\Models\Menu;
use Spatie\Permission\Models\Role;

/**
 * Satu sumber kebenaran untuk aksi (read/create/edit/update/delete) di modul
 * yang punya CRUD.
 *
 * Kenapa perlu: tabel `role_menu` cuma menyatakan "menu ini tampil di sidebar
 * dan memberi akses ke modulnya", tanpa membedakan aksi. Akibatnya memberi
 * satu centang Pengguna ke Catpil otomatis memberinya tambah/ubah/hapus.
 * Lapisan ini memisahkan keduanya: `role_menu` mengatur TAMPILAN, dan
 * permission Spatie (`<scope>.<aksi>`) mengatur AKSI yang boleh dilakukan.
 *
 * Hanya modul CRUD yang punya lima aksi. Modul lain (verifikasi, dasbor,
 * beasiswa, dsb.) tetap satu centang seperti semula -- `MODULES` di bawah
 * adalah daftar tertutup, bukan pola.
 */
class MenuAction
{
    public const READ = 'read';

    public const CREATE = 'create';

    public const EDIT = 'edit';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    /**
     * Urutan tampil di halaman Akses Menu.
     *
     * `edit` (membuka form ubah) sengaja dipisah dari `update` (menyimpan
     * perubahannya) karena keduanya memang dua route berbeda di aplikasi ini.
     *
     * @var list<string>
     */
    public const ACTIONS = [self::READ, self::CREATE, self::EDIT, self::UPDATE, self::DELETE];

    /**
     * Scope menu yang punya aksi granular.
     *
     * @var list<string>
     */
    public const MODULES = ['admin.pengguna', 'admin.role', 'admin.menukelola'];

    /**
     * Label tampil tiap aksi.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        self::READ => 'Lihat',
        self::CREATE => 'Tambah',
        self::EDIT => 'Ubah',
        self::UPDATE => 'Simpan',
        self::DELETE => 'Hapus',
    ];

    /**
     * Route mana butuh aksi apa.
     *
     * Dipakai `EnsureMenuAction`; route yang tidak terdaftar di sini hanya
     * dijaga `akses.menu` seperti biasa (menu non-CRUD tidak punya aksi).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const ROUTES = [
        // Pengguna
        'admin.pengguna.index' => ['admin.pengguna', self::READ],
        'admin.pengguna.lihat' => ['admin.pengguna', self::READ],
        'admin.pengguna.buat' => ['admin.pengguna', self::CREATE],
        'admin.pengguna.simpan' => ['admin.pengguna', self::CREATE],
        'admin.pengguna.ubah' => ['admin.pengguna', self::EDIT],
        'admin.pengguna.perbarui' => ['admin.pengguna', self::UPDATE],
        'admin.pengguna.toggle-status' => ['admin.pengguna', self::UPDATE],
        'admin.pengguna.reset-password' => ['admin.pengguna', self::UPDATE],
        'admin.pengguna.hapus' => ['admin.pengguna', self::DELETE],

        // Role
        'admin.role.index' => ['admin.role', self::READ],
        'admin.role.simpan' => ['admin.role', self::CREATE],
        'admin.role.perbarui' => ['admin.role', self::UPDATE],
        'admin.role.hapus' => ['admin.role', self::DELETE],

        // Kelola Menu
        'admin.menukelola.index' => ['admin.menukelola', self::READ],
        'admin.menukelola.simpan' => ['admin.menukelola', self::CREATE],
        'admin.menukelola.perbarui' => ['admin.menukelola', self::UPDATE],
        'admin.menukelola.hapus' => ['admin.menukelola', self::DELETE],
    ];

    public static function isModule(?string $scope): bool
    {
        return $scope !== null && in_array($scope, self::MODULES, true);
    }

    public static function permissionName(string $scope, string $action): string
    {
        return $scope.'.'.$action;
    }

    /**
     * Semua nama permission yang mungkin dibuat, dipakai seeder.
     *
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        $names = [];

        foreach (self::MODULES as $scope) {
            foreach (self::ACTIONS as $action) {
                $names[] = self::permissionName($scope, $action);
            }
        }

        return $names;
    }

    /**
     * Aksi yang diminta sebuah route, atau null bila route itu tidak dijaga
     * lapisan aksi.
     *
     * @return array{scope: string, action: string}|null
     */
    public static function forRoute(?string $routeName): ?array
    {
        if ($routeName === null || ! isset(self::ROUTES[$routeName])) {
            return null;
        }

        [$scope, $action] = self::ROUTES[$routeName];

        return ['scope' => $scope, 'action' => $action];
    }

    /**
     * Aksi yang dipegang sebuah role untuk satu scope modul.
     *
     * @return list<string>
     */
    public static function actionsForRole(string $roleName, string $scope): array
    {
        $role = Role::query()->where('name', $roleName)->first();

        if (! $role) {
            return [];
        }

        $dimiliki = $role->permissions->pluck('name')->all();

        return array_values(array_filter(
            self::ACTIONS,
            fn (string $action) => in_array(self::permissionName($scope, $action), $dimiliki, true),
        ));
    }

    /**
     * Scope modul yang benar-benar dipakai baris `menus`.
     *
     * Dipakai halaman Akses Menu supaya checkbox aksi hanya dirender untuk
     * menu CRUD yang ada, bukan untuk scope yang tidak punya menu.
     *
     * @return list<string>
     */
    public static function moduleScopesInUse(): array
    {
        return Menu::query()
            ->whereIn('scope', self::MODULES)
            ->pluck('scope')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
