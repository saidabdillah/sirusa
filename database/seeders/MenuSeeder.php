<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MenuSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $menus = [
            [
                'label' => 'Dasbor',
                'icon' => 'fas fa-fire',
                'route' => 'dashboard',
                'scope' => 'dashboard',
                'section' => 'Menu Utama',
                'urutan' => 1,
                'wajib' => true,
            ],
            [
                'label' => 'Beasiswa',
                'icon' => 'fas fa-award',
                'section' => 'Menu Admin',
                'urutan' => 10,
                'children' => [
                    ['label' => 'Daftar Beasiswa', 'route' => 'admin.beasiswa.index', 'scope' => 'admin.beasiswa'],
                ],
            ],
            [
                'label' => 'Pendaftar',
                'icon' => 'fas fa-users',
                'route' => 'admin.pendaftar.index',
                'scope' => 'admin.pendaftar',
                'section' => 'Menu Admin',
                'urutan' => 20,
            ],
            [
                'label' => 'Verifikasi Capil',
                'icon' => 'fas fa-id-card',
                'route' => 'admin.capil.index',
                'scope' => 'admin.capil',
                'section' => 'Menu Admin',
                'urutan' => 25,
            ],
            [
                'label' => 'Verifikasi Kampus',
                'icon' => 'fas fa-graduation-cap',
                'route' => 'admin.kampusverif.index',
                'scope' => 'admin.kampusverif',
                'section' => 'Menu Admin',
                'urutan' => 26,
            ],
            [
                'label' => 'Verifikasi Kesra',
                'icon' => 'fas fa-clipboard-check',
                'route' => 'admin.kesra.index',
                'scope' => 'admin.kesra',
                'section' => 'Menu Admin',
                'urutan' => 27,
            ],
            [
                'label' => 'Master Data',
                'icon' => 'fas fa-university',
                'section' => 'Menu Admin',
                'urutan' => 30,
                'children' => [
                    ['label' => 'Kampus', 'route' => 'admin.kampus.index', 'scope' => 'admin.kampus'],
                ],
            ],
            [
                'label' => 'Kelola Akses',
                'icon' => 'fas fa-user-shield',
                'section' => 'Menu Admin',
                'urutan' => 50,
                'children' => [
                    ['label' => 'Role', 'route' => 'admin.role.index', 'scope' => 'admin.role'],
                    ['label' => 'Pengguna', 'route' => 'admin.pengguna.index', 'scope' => 'admin.pengguna'],
                    ['label' => 'Akses Menu', 'route' => 'admin.menu.index', 'scope' => 'admin.menu'],
                    ['label' => 'Kelola Menu', 'route' => 'admin.menukelola.index', 'scope' => 'admin.menukelola'],
                ],
            ],
            [
                'label' => 'Menu Beasiswa',
                'icon' => 'fas fa-award',
                'section' => 'Menu Pengguna',
                'urutan' => 10,
                'children' => [
                    ['label' => 'Daftar Beasiswa', 'route' => 'user.beasiswa.index', 'scope' => 'user.beasiswa'],
                    ['label' => 'Pendaftaran Saya', 'route' => 'user.pendaftaran.index', 'scope' => 'user.pendaftaran'],
                ],
            ],
        ];

        $keyToId = [];

        foreach ($menus as $item) {
            $parent = Menu::create([
                'label' => $item['label'],
                'icon' => $item['icon'] ?? null,
                'route' => $item['route'] ?? null,
                'scope' => $item['scope'] ?? null,
                'section' => $item['section'],
                'urutan' => $item['urutan'],
                'aktif' => true,
                'wajib' => $item['wajib'] ?? false,
            ]);

            if (isset($item['scope'])) {
                $keyToId[$item['scope']] = $parent->id;
            }
            $keyToId[Str::kebab($item['label'])] = $parent->id;

            foreach (array_values($item['children'] ?? []) as $index => $child) {
                $childMenu = $parent->children()->create([
                    'label' => $child['label'],
                    'icon' => $child['icon'] ?? null,
                    'route' => $child['route'],
                    'scope' => $child['scope'],
                    'section' => $item['section'],
                    'urutan' => $index + 1,
                    'aktif' => true,
                    'wajib' => false,
                ]);

                $keyToId[$child['scope']] = $childMenu->id;
            }
        }

        $grants = [
            'super_admin' => [
                'dasbor',
                'beasiswa',
                'admin.beasiswa',
                'admin.pendaftar',
                'admin.capil',
                'admin.kampusverif',
                'admin.kesra',
                'master-data',
                'admin.kampus',
                'kelola-akses',
                'admin.role',
                'admin.pengguna',
                'admin.menu',
                'admin.menukelola',
            ],
            'capil' => [
                'dasbor',
                'admin.capil',
                'admin.pendaftar',
            ],
            'kampus' => [
                'dasbor',
                'admin.kampusverif',
                'admin.pendaftar',
            ],
            'kesra' => [
                'dasbor',
                'beasiswa',
                'admin.beasiswa',
                'admin.pendaftar',
                'admin.kesra',
                'master-data',
                'admin.kampus',
                // `kelola-akses` (parent) ikut di-grant supaya item "Pengguna" muncul di
                // sidebar — sidebarMenus() hanya query menu parent_id null.
                'kelola-akses',
                'admin.pengguna',
            ],
            'user' => [
                'dasbor',
                'menu-beasiswa',
                'user.beasiswa',
                'user.pendaftaran',
            ],
        ];

        foreach ($grants as $roleName => $keys) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            foreach ($keys as $key) {
                if (isset($keyToId[$key])) {
                    DB::table('role_menu')->insert([
                        'role_id' => $role->id,
                        'menu_id' => $keyToId[$key],
                    ]);
                }
            }
        }
    }
}
