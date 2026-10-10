<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
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
                'section' => 'Administrasi',
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
                'section' => 'Administrasi',
                'urutan' => 20,
            ],
            // Tiga menu verifikasi dipindah ke section sendiri dan labelnya
            // dipangkas: section "Verifikasi" sudah menyebut prosesnya, jadi
            // mengulanginya di tiap label cuma menambah ruang kosong di sidebar.
            //
            // "Kampus" sekarang jadi nama menu DAN nama child "Master Data".
            // Keduanya tidak bentrok karena `updateOrCreate` dicocokkan dengan
            // (section, label, parent_id) -- section dan parent-nya berbeda --
            // tapi teks konfirmasi hapus di "Kelola Menu" jadi ambigu
            // ("Hapus menu 'Kampus'?" untuk dua menu berbeda). Halaman
            // "Akses Menu" tidak ambigu karena menampilkan `(section)` di
            // samping label.
            [
                'label' => 'Catpil',
                'icon' => 'fas fa-id-card',
                'route' => 'admin.catpil.index',
                'scope' => 'admin.catpil',
                'section' => 'Verifikasi',
                'urutan' => 25,
            ],
            [
                'label' => 'Kampus',
                'icon' => 'fas fa-graduation-cap',
                'route' => 'admin.kampusverif.index',
                'scope' => 'admin.kampusverif',
                'section' => 'Verifikasi',
                'urutan' => 26,
            ],
            // Kesra adalah satu-satunya antrean verifikasi berbentuk dropdown:
            // halaman kerjanya dua daftar yang terpisah (antrean putusan + arsip
            // keputusan), jadi menunya jadi induk tanpa route -- sidebar merender
            // induk beranak sebagai dropdown. `scope` induk TETAP `admin.kesra`
            // karena dialah yang menutup `admin.kesra.*` untuk `hasMenuAccess()`
            // dan `EnsureMenuAccess`; scope anak harus unik supaya `keyToId`
            // tidak menimpa kunci grant induk (induk wajib tetap di-grant,
            // `sidebarMenus()` hanya mengambil `parent_id` null).
            [
                // "Kesra" jadi dropdown tiga anak: antrean verifikasi, daftar
                // penerima (pendaftar berstatus `diterima`) tempat cetak dan
                // ekspor, serta arsip keputusan. Parent tanpa route karena
                // `scope` induk `admin.kesra` menutup seluruh `admin.kesra.*`;
                // tiap anak tetap punya scope unik sendiri untuk sidebar.
                'label' => 'Kesra',
                'icon' => 'fas fa-clipboard-check',
                'scope' => 'admin.kesra',
                'section' => 'Verifikasi',
                'urutan' => 27,
                'children' => [
                    ['label' => 'Verifikasi', 'route' => 'admin.kesra.index', 'scope' => 'admin.kesra.index'],
                    ['label' => 'Penerima Beasiswa', 'route' => 'admin.penerima.index', 'scope' => 'admin.penerima'],
                    ['label' => 'Keputusan', 'route' => 'admin.kesra.disetujui', 'scope' => 'admin.kesra.disetujui'],
                ],
            ],
            // Status Verifikasi MENJADI leaf mandiri, bukan anak dropdown Kesra
            // (permintaan pengguna): Catpil dan Kampus harus bisa melihat rekap
            // status semua profil, jadi menempatkannya di dalam dropdown Kesra
            // akan menyembunyikannya dari sidebar mereka -- `sidebarMenus()`
            // hanya merender anak yang grant-nya sendiri dipegang role.
            //
            // Berada di section "Administrasi" (bukan "Verifikasi", permintaan
            // pengguna): semua role sudah melihat section itu lewat menu
            // Pendaftar, dan section "Verifikasi" jadi murni antrean kerja.
            // Urutannya ditempatkan setelah "Master Data" (30).
            [
                'label' => 'Status Verifikasi',
                'icon' => 'fas fa-clipboard-list',
                'route' => 'admin.kesra.status',
                'scope' => 'admin.kesra.status',
                'section' => 'Administrasi',
                'urutan' => 31,
            ],
            [
                'label' => 'Master Data',
                'icon' => 'fas fa-university',
                'section' => 'Administrasi',
                'urutan' => 30,
                'children' => [
                    ['label' => 'Kampus', 'route' => 'admin.kampus.index', 'scope' => 'admin.kampus'],
                ],
            ],
            [
                'label' => 'Kelola Akses',
                'icon' => 'fas fa-user-shield',
                'section' => 'Administrator',
                'urutan' => 50,
                'children' => [
                    ['label' => 'Role', 'route' => 'admin.role.index', 'scope' => 'admin.role'],
                    ['label' => 'Pengguna', 'route' => 'admin.pengguna.index', 'scope' => 'admin.pengguna'],
                    ['label' => 'Akses Menu', 'route' => 'admin.menu.index', 'scope' => 'admin.menu'],
                    ['label' => 'Kelola Menu', 'route' => 'admin.menukelola.index', 'scope' => 'admin.menukelola'],
                ],
            ],
            [
                'label' => 'Beasiswa Saya',
                'icon' => 'fas fa-award',
                'section' => 'Layanan Mahasiswa',
                'urutan' => 10,
                'children' => [
                    ['label' => 'Daftar Beasiswa', 'route' => 'user.beasiswa.index', 'scope' => 'user.beasiswa'],
                    ['label' => 'Pendaftaran Saya', 'route' => 'user.pendaftaran.index', 'scope' => 'user.pendaftaran'],
                ],
            ],
        ];

        $keyToId = [];

        foreach ($menus as $item) {
            // Idempoten lewat (section, label, parent_id). `create()` dipakai
            // sebelumnya, jadi menjalankan seeder dua kali menghasilkan dua
            // pohon menu -- dan `role_menu` ikut jadi rangkap. `label` saja
            // tidak cukup sebagai kunci: "Daftar Beasiswa" ada di section
            // "Administrasi" dan "Layanan Mahasiswa" sekaligus.
            $parent = Menu::updateOrCreate(
                [
                    'section' => $item['section'],
                    'label' => $item['label'],
                    'parent_id' => null,
                ],
                [
                    'icon' => $item['icon'] ?? null,
                    'route' => $item['route'] ?? null,
                    'scope' => $item['scope'] ?? null,
                    'urutan' => $item['urutan'],
                    'aktif' => true,
                    'wajib' => $item['wajib'] ?? false,
                ]
            );

            if (isset($item['scope'])) {
                $keyToId[$item['scope']] = $parent->id;
            }
            $keyToId[Str::kebab($item['label'])] = $parent->id;

            foreach (array_values($item['children'] ?? []) as $index => $child) {
                $childMenu = Menu::updateOrCreate(
                    [
                        'section' => $item['section'],
                        'label' => $child['label'],
                        'parent_id' => $parent->id,
                    ],
                    [
                        'icon' => $child['icon'] ?? null,
                        'route' => $child['route'],
                        'scope' => $child['scope'],
                        'urutan' => $index + 1,
                        'aktif' => true,
                        'wajib' => false,
                    ]
                );

                $keyToId[$child['scope']] = $childMenu->id;
            }
        }

        // Pangkas menu yang tidak lagi ada di daftar seed. Tanpa ini, mengganti
        // nama scope/route (mis. `admin.menuKelola` -> `admin.menukelola`)
        // meninggalkan baris lama yang `route`-nya sudah tidak terdaftar --
        // sidebar memanggil `route($menu->route)` dan seluruh aplikasi akan 500
        // dengan RouteNotFoundException sampai cache view dibersihkan.
        $this->buangMenuLama(array_values($keyToId));

        $grants = [
            'super_admin' => [
                'dasbor',
                'beasiswa',
                'admin.beasiswa',
                'admin.pendaftar',
                'admin.catpil',
                'admin.kampusverif',
                // `kesra` = parent dropdown "Kesra" (kunci labelnya
                // `Str::kebab($label)`), sedangkan `admin.kesra` = scope induk.
                // Tanpa keduanya parent tidak ter-grant dan seluruh dropdown
                // (Verifikasi + Penerima Beasiswa + Keputusan) hilang dari
                // sidebar -- sidebarMenus() hanya mengambil menu parent_id null.
                'kesra',
                'admin.kesra',
                'admin.penerima',
                'admin.kesra.disetujui',
                'admin.kesra.index',
                'admin.kesra.status',
                'master-data',
                'admin.kampus',
                'kelola-akses',
                'admin.role',
                'admin.pengguna',
                'admin.menu',
                'admin.menukelola',
            ],
            'catpil' => [
                'dasbor',
                'admin.catpil',
                'admin.pendaftar',
                'admin.kesra.status',
                // `admin.penerima` (anak dropdown Kesra) untuk melihat daftar
                // penerima; parent `kesra` sengaja TIDAK di-grant supaya scope
                // induk `admin.kesra` tidak membuka antrean/keputusan Kesra.
                'admin.penerima',
            ],
            'kampus' => [
                'dasbor',
                'admin.kampusverif',
                'admin.pendaftar',
                'admin.kesra.status',
                // Lihat komentar grant `admin.penerima` pada role catpil.
                'admin.penerima',
            ],
            'kesra' => [
                'dasbor',
                'beasiswa',
                'admin.beasiswa',
                'admin.pendaftar',
                // `kesra` (parent dropdown) + `kelola-akses` (parent) ikut
                // di-grant supaya dropdown itu sendiri muncul di sidebar —
                // sidebarMenus() hanya query menu parent_id null.
                'kesra',
                'admin.kesra',
                'admin.penerima',
                'admin.kesra.disetujui',
                'admin.kesra.index',
                'admin.kesra.status',
                'master-data',
                'admin.kampus',
                'kelola-akses',
                'admin.pengguna',
            ],
            'user' => [
                'dasbor',
                'beasiswa-saya',
                'user.beasiswa',
                'user.pendaftaran',
            ],
        ];

        $this->pasangGrant($grants, $keyToId);
    }

    /**
     * Grant menu ke role.
     *
     * Kunci sebuah entri grant adalah `scope` menunya, atau
     * `Str::kebab($label)` untuk menu tanpa scope (mis. "Master Data" ->
     * `master-data`).
     *
     * Kunci label jauh lebih rapuh daripada `scope`: begitu labelnya diubah,
     * kuncinya ikut berubah dan grant-nya hilang tanpa keterangan -- gejalanya cuma
     * "menu tiba-tiba tidak muncul" di sidebar, yang jauh lebih sulit
     * ditelusuri daripada error saat seeding. Karena itu kunci yang tidak
     * ketemu sengaja dibiarkan gagal keras, bukan `continue` diam-diam.
     *
     * @param  array<string, list<string>>  $grants
     * @param  array<string, int>  $keyToId
     */
    private function pasangGrant(array $grants, array $keyToId): void
    {
        foreach ($grants as $roleName => $keys) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            foreach ($keys as $key) {
                if (! isset($keyToId[$key])) {
                    throw new RuntimeException(sprintf(
                        'MenuSeeder: grant "%s" untuk role "%s" tidak cocok dengan menu mana pun. '
                        .'Kunci grant = `scope` menu, atau `Str::kebab($label)` untuk menu tanpa scope.',
                        $key,
                        $roleName
                    ));
                }

                // `insertOrIgnore`, bukan `insert`: seeder harus aman
                // dijalankan berkali-kali tanpa violate unique key pada
                // pivot `role_menu` (primary key gabungan role_id+menu_id).
                DB::table('role_menu')->insertOrIgnore([
                    'role_id' => $role->id,
                    'menu_id' => $keyToId[$key],
                ]);
            }
        }
    }

    /**
     * Hapus menu yang id-nya tidak ada di hasil seed, beserta grant-nya.
     *
     * Dipanggil SEBELUM grant ditambahkan supaya `role_menu` untuk menu
     * lama ikut hilang (dipivot `cascade`).
     *
     * @param  list<int>  $idYangDipertahankan
     */
    private function buangMenuLama(array $idYangDipertahankan): void
    {
        $idLama = Menu::query()
            ->whereNotIn('id', $idYangDipertahankan)
            ->pluck('id');

        // `menus.parent_id` di-declare `nullOnDelete`, jadi anak yang tidak lagi
        // ada di daftar seed akan tertinggal sebagai yatim `parent_id = NULL`
        // dan muncul sebagai menu top-level yang salah. Bersihkan eksplisit.
        $yatim = Menu::query()
            ->whereNotNull('parent_id')
            ->whereNotIn('parent_id', $idYangDipertahankan)
            ->pluck('id');

        $idHapus = $idLama->merge($yatim)->unique()->values();

        if ($idHapus->isEmpty()) {
            return;
        }

        DB::table('role_menu')->whereIn('menu_id', $idHapus)->delete();
        Menu::query()->whereIn('id', $idHapus)->delete();
    }
}
