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

    /**
     * Susunan menu sidebar.
     *
     * Dikelompokkan per PEKERJAAN, bukan per jenis data: bagian menyebut apa
     * yang dikerjakan petugas, bukan tabel apa yang ada di belakangnya.
     *
     * `section` bukan enum database: `Menu::sections()` menggabungkan
     * `DEFAULT_SECTIONS` dengan nilai yang benar-benar dipakai baris `menus`,
     * jadi menambah bagian cukup di sini.
     *
     * Kunci grant (`key`) hanya dipakai saat `scope` atau `Str::kebab($label)`
     * tidak unik: dua menu boleh berbagi scope yang sama (Verifikasi Semester
     * di dropdown Kampus dan Kesra), dan induk dropdown yang scope-nya dipakai
     * anaknya. Tanpa `key` eksplisit kunci itu saling menimpa di `$keyToId`
     * dan grant menu yang kalah hilang tanpa error.
     *
     * @var list<array<string, mixed>>
     */
    private const MENUS = [
        // Satu halaman kerja yang dibuka tiap hari.
        [
            'label' => 'Dasbor',
            'icon' => 'fas fa-fire',
            'route' => 'dashboard',
            'scope' => 'dashboard',
            'section' => 'Menu Utama',
            'urutan' => 1,
            'wajib' => true,
        ],

        // PENGELOLAAN BEASISWA: apa yang ditawarkan (program) dan data
        // acuannya (perguruan tinggi -> fakultas -> prodi).
        [
            'label' => 'Program Beasiswa',
            'icon' => 'fas fa-award',
            'section' => 'Pengelolaan Beasiswa',
            'urutan' => 10,
            'children' => [
                ['label' => 'Daftar Program', 'route' => 'admin.beasiswa.index', 'scope' => 'admin.beasiswa'],
            ],
        ],
        [
            'label' => 'Data Referensi',
            'icon' => 'fas fa-university',
            'section' => 'Pengelolaan Beasiswa',
            'urutan' => 20,
            'children' => [
                ['label' => 'Perguruan Tinggi', 'route' => 'admin.kampus.index', 'scope' => 'admin.kampus'],
            ],
        ],

        // PENDAFTARAN: rekap siapa saja yang mendaftar. Bukan antrean kerja
        // (itu bagian Seleksi & Verifikasi), dan dibaca read-only oleh
        // Catpil, Kampus, maupun Kesra.
        [
            'label' => 'Daftar Pendaftar',
            'icon' => 'fas fa-clipboard-list',
            'route' => 'admin.kesra.status',
            'scope' => 'admin.kesra.status',
            'section' => 'Pendaftaran',
            'urutan' => 30,
        ],

        // SELEKSI & VERIFIKASI: urutan kerja verifikasi. Catpil memeriksa
        // kependudukan; Kampus memeriksa akademik; Kesra memutuskan.
        [
            'label' => 'Catpil',
            'icon' => 'fas fa-id-card',
            'route' => 'admin.catpil.index',
            'scope' => 'admin.catpil',
            'section' => 'Seleksi & Verifikasi',
            'urutan' => 40,
        ],
        [
            'label' => 'Kampus',
            'icon' => 'fas fa-graduation-cap',
            'scope' => 'admin.kampusverif',
            // Kunci induk dipisah dari `scope` karena anak pertamanya memakai
            // scope yang sama: tanpa ini anak menimpa kunci induk dan
            // dropdown-nya tidak pernah ter-grant.
            'key' => 'kampus-dropdown',
            'section' => 'Seleksi & Verifikasi',
            'urutan' => 50,
            'children' => [
                ['label' => 'Verifikasi Awal', 'route' => 'admin.kampusverif.index', 'scope' => 'admin.kampusverif', 'key' => 'kampusverif-awal'],
                ['label' => 'Verifikasi Semester', 'route' => 'admin.semester.kampus', 'scope' => 'admin.semester', 'key' => 'semester-kampus'],
            ],
        ],
        [
            'label' => 'Kesra',
            'icon' => 'fas fa-clipboard-check',
            'scope' => 'admin.kesra',
            'key' => 'kesra-dropdown',
            'section' => 'Seleksi & Verifikasi',
            'urutan' => 60,
            'children' => [
                ['label' => 'Verifikasi Awal', 'route' => 'admin.kesra.index', 'scope' => 'admin.kesra.index', 'key' => 'kesraverif-awal'],
                ['label' => 'Keputusan', 'route' => 'admin.kesra.disetujui', 'scope' => 'admin.kesra.disetujui'],
                ['label' => 'Verifikasi Semester', 'route' => 'admin.semester.kesra', 'scope' => 'admin.semester', 'key' => 'semester-kesra'],
            ],
        ],
        // PENGUMUMAN: bagian sendiri (permintaan pengguna). Menerbitkan hasil
        // seleksi adalah pekerjaan tersendiri yang terjadi SETELAH verifikasi
        // selesai, bukan langkah berikutnya di antrean yang sama.
        [
            'label' => 'Pengumuman Hasil',
            'icon' => 'fas fa-bullhorn',
            'route' => 'admin.kesra.pengumuman',
            'scope' => 'admin.kesra.pengumuman',
            'section' => 'Pengumuman',
            'urutan' => 70,
        ],

        // MONITORING & LAPORAN: hasil, bukan antrean kerja. Tanpa kolom Aksi.
        [
            'label' => 'Penerima Beasiswa',
            'icon' => 'fas fa-user-check',
            'route' => 'admin.penerima.index',
            'scope' => 'admin.penerima',
            'section' => 'Monitoring & Laporan',
            'urutan' => 80,
        ],

        // ADMINISTRASI SISTEM: alat pengelola aplikasi.
        [
            'label' => 'Manajemen Akses',
            'icon' => 'fas fa-user-shield',
            'section' => 'Administrasi Sistem',
            'urutan' => 90,
            'children' => [
                ['label' => 'Pengguna', 'route' => 'admin.pengguna.index', 'scope' => 'admin.pengguna'],
                ['label' => 'Peran', 'route' => 'admin.role.index', 'scope' => 'admin.role'],
                ['label' => 'Hak Akses Menu', 'route' => 'admin.menu.index', 'scope' => 'admin.menu'],
                ['label' => 'Kelola Menu', 'route' => 'admin.menukelola.index', 'scope' => 'admin.menukelola'],
            ],
        ],

        // LAYANAN MAHASISWA: sisi akun mahasiswa.
        [
            'label' => 'Beasiswa Saya',
            'icon' => 'fas fa-award',
            'section' => 'Layanan Mahasiswa',
            'urutan' => 100,
            'children' => [
                ['label' => 'Daftar Beasiswa', 'route' => 'user.beasiswa.index', 'scope' => 'user.beasiswa'],
                ['label' => 'Pendaftaran Saya', 'route' => 'user.pendaftaran.index', 'scope' => 'user.pendaftaran'],
            ],
        ],
    ];

    public function run(): void
    {
        $keyToId = [];

        foreach (self::MENUS as $item) {
            // Idempoten lewat (section, label, parent_id). `label` saja tidak
            // cukup sebagai kunci: "Daftar Beasiswa" ada di dua bagian.
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
            // `key` eksplisit ditulis TERAKHIR supaya menang atas `scope` dan
            // label: dipakai saat dua menu sengaja berbagi scope yang sama.
            if (! empty($item['key'])) {
                $keyToId[$item['key']] = $parent->id;
            }

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
                // `key` eksplisit ditulis TERAKHIR supaya menang atas `scope`.
                if (! empty($child['key'])) {
                    $keyToId[$child['key']] = $childMenu->id;
                }
            }
        }

        // Pangkas menu yang tidak lagi ada di daftar seed. Tanpa ini, mengganti
        // nama scope/route meninggalkan baris lama yang `route`-nya sudah tidak
        // terdaftar -- sidebar memanggil `route($menu->route)` dan seluruh
        // aplikasi akan 500 sampai cache view dibersihkan. Ini juga yang
        // membuang menu lama saat label/bagiannya diganti ("Beasiswa" ->
        // "Program Beasiswa", "Master Data" -> "Data Referensi", dst).
        $this->buangMenuLama(array_values($keyToId));

        $grants = [
            'super_admin' => [
                'dasbor',
                'program-beasiswa',
                'admin.beasiswa',
                'data-referensi',
                'admin.kampus',
                'admin.kesra.status',
                'admin.catpil',
                'kampus-dropdown',
                'kampusverif-awal',
                'semester-kampus',
                'kesra-dropdown',
                'admin.kesra',
                'admin.penerima',
                'admin.kesra.disetujui',
                'kesraverif-awal',
                'admin.kesra.pengumuman',
                'semester-kesra',
                'manajemen-akses',
                'admin.role',
                'admin.pengguna',
                'admin.menu',
                'admin.menukelola',
            ],
            'catpil' => [
                'dasbor',
                'admin.catpil',
                'admin.kesra.status',
                // `admin.penerima` (Monitoring & Laporan) untuk melihat daftar
                // penerima read-only. Scope induk `admin.kesra` sengaja TIDAK
                // di-grant supaya antrean/keputusan Kesra tetap tertutup.
                'admin.penerima',
            ],
            'kampus' => [
                'dasbor',
                'kampus-dropdown',
                'kampusverif-awal',
                'admin.kesra.status',
                'admin.penerima',
                // Verifikasi paket semester: Kampus tahap pertamanya. Kuncinya
                // `semester-kampus`, bukan scope, karena scope `admin.semester`
                // juga dipakai menu Kesra.
                'semester-kampus',
            ],
            'kesra' => [
                'dasbor',
                'program-beasiswa',
                'admin.beasiswa',
                // Induk dropdown + induk Manajemen Akses ikut di-grant supaya
                // dropdown-nya muncul di sidebar (sidebar hanya mengambil menu
                // `parent_id` null).
                'kesra-dropdown',
                'admin.kesra',
                'admin.penerima',
                'admin.kesra.disetujui',
                'kesraverif-awal',
                'admin.kesra.pengumuman',
                'admin.kesra.status',
                'semester-kesra',
                'data-referensi',
                'admin.kampus',
                'manajemen-akses',
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
     * Kunci sebuah entri grant adalah `scope` menunya, `Str::kebab($label)`
     * untuk menu tanpa scope (mis. "Program Beasiswa" -> `program-beasiswa`),
     * atau `key` eksplisit saat scope harus dibagi dua menu.
     *
     * Kunci label lebih rapuh daripada `scope`: begitu labelnya diubah,
     * kuncinya ikut berubah dan grant-nya hilang tanpa keterangan -- gejalanya
     * cuma "menu tiba-tiba tidak muncul" di sidebar. Karena itu kunci yang
     * tidak ketemu sengaja dibiarkan gagal keras, bukan `continue` diam-diam.
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
                        .'Kunci grant = `scope` menu, `Str::kebab($label)` untuk menu tanpa scope, '
                        .'atau `key` eksplisit saat dua menu berbagi scope.',
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
