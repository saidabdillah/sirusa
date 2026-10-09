<?php

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('sidebar', 'menu');

beforeEach(function () {
    seedAkses();
});

test('super admin sidebar shows role and menu access but not template surat', function () {
    $superAdmin = User::factory()->superAdmin()->create(['email' => 'sidebar-sa@test.com']);

    actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Kelola Akses', false)
        ->assertSee('<span>Role</span>', false)
        ->assertSee('<span>Akses Menu</span>', false)
        ->assertDontSee('<span>Template Surat</span>', false);
});

/**
 * `route` diisi developer lewat koding (`MenuSeeder`/`tinker`), bukan form
 * Kelola Menu. Menu dengan route terdaftar dirender sebagai `<a>` yang bisa
 * diklik ke halaman aslinya.
 */
test('menu buatan super admin dengan route dirender sebagai link di sidebar', function () {
    $superAdmin = User::factory()->superAdmin()->create(['email' => 'sidebar-tautan-baru@test.com']);

    $menu = Menu::create([
        'label' => 'Tautan Baru',
        'icon' => 'fas fa-link',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Administrasi',
        'urutan' => 99,
        'aktif' => true,
        'wajib' => false,
    ]);

    DB::table('role_menu')->insertOrIgnore([
        'role_id' => Role::where('name', 'super_admin')->firstOrFail()->id,
        'menu_id' => $menu->id,
    ]);

    actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<span>Tautan Baru</span>', false)
        ->assertSee('<a href="'.route('admin.beasiswa.index').'" class="nav-link">', false);
});

/**
 * Menu yang route-nya masih kosong (NULL) TETAP dirender sebagai
 * `<a>` dengan `href="#"` (inert, tanpa navigasi ke 404), bukan
 * link sentinel `/__menu__/{id}` dan bukan `<span>` mati. Link tujuan
 * diisi developer di koding; sebelum itu menu tetap tampil dan bisa
 * diklik, hanya scroll ke atas saat ditekan.
 */
test('menu tanpa route tetap dirender sebagai link, bukan span inert', function () {
    $superAdmin = User::factory()->superAdmin()->create(['email' => 'sidebar-span-@test.com']);

    $menu = Menu::create([
        'label' => 'Tanpa Tautan',
        'icon' => 'fas fa-folder',
        'route' => null,
        'scope' => null,
        'section' => 'Administrasi',
        'urutan' => 98,
        'aktif' => true,
        'wajib' => false,
    ]);

    DB::table('role_menu')->insertOrIgnore([
        'role_id' => Role::where('name', 'super_admin')->firstOrFail()->id,
        'menu_id' => $menu->id,
    ]);

    actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<span>Tanpa Tautan</span>', false)
        // Link inert #, bukan link sentinel 404.
        ->assertSee('<a href="#" class="nav-link">', false)
        ->assertDontSee('<span class="nav-link"><i class="fas fa-folder"></i><span>Tanpa Tautan</span></span>', false);
});

test('kesra sidebar shows admin menus plus Pengguna but not role and menu management', function () {
    $admin = User::factory()->admin()->create(['email' => 'sidebar-admin@test.com']);

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<span>Pendaftar</span>', false)
        // "Kesra" sekarang dropdown dua anak: antrean verifikasi dan daftar
        // penerima (cetak/ekspor/tarik).
        ->assertSee('<span>Kesra</span>', false)
        ->assertSee(route('admin.kesra.index'), false)
        ->assertSee(route('admin.penerima.index'), false)
        ->assertSee(route('admin.kesra.disetujui'), false)
        ->assertSee(route('admin.kesra.status'), false)
        ->assertSee('<span>Verifikasi</span>', false)
        ->assertSee('<span>Penerima Beasiswa</span>', false)
        ->assertSee('<span>Keputusan</span>', false)
        ->assertSee('<span>Status Verifikasi</span>', false)
        // Halaman Kampus (Master Data) dikelola role kesra. Diuji lewat href,
        // bukan `<span>Kampus</span>`, karena sejak menu verifikasi dipersingkat
        // jadi "Kampus" ada dua menu dengan label yang sama di sidebar.
        ->assertSee(route('admin.kampus.index'), false)
        // Role kesra tidak diberi grant `admin.kampusverif`, jadi label "Kampus"
        // di section Verifikasi tidak boleh ikut muncul.
        ->assertDontSee(route('admin.kampusverif.index'), false)
        // Kesra diberi grant `admin.pengguna`; group "Kelola Akses" ikut tampil karena
        // sidebarMenus() hanya mengambil menu parent, tapi anak lain tetap tersaring.
        ->assertSee('Kelola Akses', false)
        ->assertSee('<span>Pengguna</span>', false)
        ->assertDontSee('<span>Role</span>', false)
        ->assertDontSee('<span>Akses Menu</span>', false)
        ->assertDontSee('<span>Kelola Menu</span>', false);
});

test('kampus role sidebar does not show kampus master data menu', function () {
    $kampusAdmin = User::factory()->kampusAdmin()->create(['email' => 'sidebar-kampus@test.com']);

    actingAs($kampusAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<span>Kampus</span>', false)
        // Label "Kampus" sekarang dipakai dua menu, jadi yang diuji di sini
        // adalah href-nya: role kampus punya antrean verifikasi, tapi tidak
        // boleh punya master data kampus.
        ->assertSee(route('admin.kampusverif.index'), false)
        ->assertDontSee(route('admin.kampus.index'), false)
        // Halaman "Status Verifikasi" dibuka untuk Catpil dan Kampus (rekap
        // read-only), jadi leaf-nya ikut muncul di sidebar mereka.
        ->assertSee(route('admin.kesra.status'), false)
        ->assertDontSee('Kelola Akses', false);
});

test('user sidebar only shows user module menus', function () {
    $user = User::factory()->standardUser()->create(['email' => 'sidebar-user@test.com']);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Daftar Beasiswa', false)
        ->assertSee('Pendaftaran Saya', false)
        ->assertDontSee('Kelola Akses', false)
        ->assertDontSee('Template Surat', false);
});

/**
 * Section sidebar diuji dari posisinya di dalam HTML, bukan dari keberadaan
 * teksnya. "Kampus" dan "Beasiswa" muncul sebagai label di beberapa section
 * sekaligus, jadi `assertSee('Kampus')` tidak bisa membuktikan section mana
 * menu itu berada.
 */
test('sidebar groups menu ke section yang sesuai', function () {
    $superAdmin = User::factory()->superAdmin()->create(['email' => 'sidebar-section@test.com']);

    $html = actingAs($superAdmin)->get(route('dashboard'))->getContent();
    $htmlRapi = preg_replace('/\s+/', ' ', $html);

    // Helper: ambil potongan sidebar dari satu header section sampai header
    // berikutnya.
    //
    // Section terakhir yang dirender harus dibatasi di `</aside>`, bukan
    // "sampai akhir string": tanpa itu potongannya ikut menelan isi halaman di
    // luar sidebar (kartu statistik dasbor) dan assertion "menu ini tidak ada
    // di section itu" selalu salah. `</ul>` tidak bisa dipakai sebagai batas
    // karena menu dropdown punya `<ul class="dropdown-menu">` sendiri, jadi
    // `</ul>` pertama yang muncul bukan penutup sidebar.
    $sidebar = Str::between($htmlRapi, '<ul class="sidebar-menu">', '</aside>');
    expect($sidebar)->not->toBe('', 'Sidebar tidak ditemukan di halaman dasbor');

    $bagian = function (string $section) use ($sidebar) {
        $mulai = strpos($sidebar, '<li class="menu-header">'.$section.'</li>');
        expect($mulai)->not->toBeFalse("Section '{$section}' tidak muncul di sidebar");

        $selesai = strpos($sidebar, '<li class="menu-header">', $mulai + 1);

        return $selesai === false
            ? substr($sidebar, $mulai)
            : substr($sidebar, $mulai, $selesai - $mulai);
    };

    // SECTION 1 -- ADMINISTRASI: Beasiswa, Pendaftar, Master Data, dan Status
    // Verifikasi. Master Data tetap di sini: kampus adalah back-office
    // operasional, sama seperti dua menu lainnya, bukan alat sistem. "Status
    // Verifikasi" ikut pindah ke sini (permintaan pengguna): semua role melihat
    // section ini lewat menu Pendaftar, dan section "Verifikasi" jadi murni
    // antrean kerja.
    $administrasi = $bagian('Administrasi');
    expect($administrasi)
        ->toContain(route('admin.beasiswa.index'))
        ->toContain(route('admin.pendaftar.index'))
        ->toContain(route('admin.kampus.index'))
        ->toContain(route('admin.kesra.status'))
        ->not->toContain(route('admin.catpil.index'))
        ->not->toContain(route('admin.role.index'));

    // SECTION 2 -- VERIFIKASI: tiga menu verifikasi; Catpil & Kampus tetap
    // leaf, Kesra induk dropdown berisi Verifikasi + Penerima Beasiswa +
    // Keputusan. "Status Verifikasi" sudah bukan bagian section ini (leaf
    // mandiri di Administrasi). Tanpa prefiks "Verifikasi" pada label karena
    // section-nya sudah menyebut prosesnya.
    $verifikasi = $bagian('Verifikasi');
    expect($verifikasi)
        ->toContain(route('admin.catpil.index'))
        ->toContain(route('admin.kampusverif.index'))
        ->toContain(route('admin.kesra.index'))
        ->toContain(route('admin.penerima.index'))
        ->toContain(route('admin.kesra.disetujui'))
        ->toContain('<span>Catpil</span>')
        ->toContain('<span>Kampus</span>')
        ->toContain('<span>Kesra</span>')
        ->not->toContain(route('admin.kesra.status'))
        ->not->toContain(route('admin.beasiswa.index'))
        ->not->toContain(route('admin.pendaftar.index'));

    // SECTION 3 -- ADMINISTRATOR: seluruh pengelolaan akses.
    $administrator = $bagian('Administrator');
    expect($administrator)
        ->toContain('<span>Kelola Akses</span>')
        ->toContain(route('admin.role.index'))
        ->toContain(route('admin.pengguna.index'))
        ->toContain(route('admin.menu.index'))
        ->toContain(route('admin.menukelola.index'))
        ->not->toContain(route('admin.beasiswa.index'))
        ->not->toContain(route('admin.kesra.index'));

    // Urutan section mengikuti `Menu::sections()` dan tidak boleh diacak ulang.
    // Hanya section yang benar-benar dirender super admin yang ikut diuji:
    // "Layanan Mahasiswa" memang tidak di-grant ke `super_admin`, jadi
    // kehadirannya di sidebar bukan urusan section.
    $sectionTampil = ['Menu Utama', 'Administrasi', 'Verifikasi', 'Administrator'];

    $urutan = array_map(
        fn (string $section) => strpos($htmlRapi, '<li class="menu-header">'.$section.'</li>'),
        $sectionTampil
    );

    $urutanUrut = $urutan;
    sort($urutanUrut);

    expect($urutan)->toBe($urutanUrut);

    // Tidak ada section lain yang muncul di sidebar super admin.
    foreach (Menu::sections() as $section) {
        if (in_array($section, $sectionTampil, true)) {
            continue;
        }

        expect($htmlRapi)->not->toContain('<li class="menu-header">'.$section.'</li>');
    }
});

/**
 * Section hanya mengelompokkan tampilan. Memindahkannya tidak boleh
 * menambah atau mengurangi menu yang boleh diakses(role, permission) maupun
 * menutup section yang sebelumnya tampil.
 */
test('memindah section tidak mengubah menu yang terlihat per role', function () {
    // Role yang tidak punya satu pun menu di section baru harus tetap
    // kehilangan section itu -- kalau tidak, user role terbatas akan melihat
    // header kosong atau, lebih buruk, menu di luar haknya.
    $user = User::factory()->standardUser()->create(['email' => 'sidebar-geser-user@test.com']);

    $htmlUser = actingAs($user)->get(route('dashboard'))->getContent();
    expect($htmlUser)
        ->toContain('<li class="menu-header">Layanan Mahasiswa</li>')
        ->not->toContain('<li class="menu-header">Verifikasi</li>')
        ->not->toContain('<li class="menu-header">Administrator</li>')
        ->not->toContain('<li class="menu-header">Administrasi</li>');

    // Setiap section yang punya isinya harus punya tepat satu header, tidak
    // ada duplikat (efek khas seeder dijalankan dua kali tanpa idempotensi).
    $superAdmin = User::factory()->superAdmin()->create(['email' => 'sidebar-geser-sa@test.com']);

    $htmlAdmin = preg_replace('/\s+/', ' ', actingAs($superAdmin)->get(route('dashboard'))->getContent());

    foreach (Menu::sections() as $section) {
        expect(substr_count($htmlAdmin, '<li class="menu-header">'.$section.'</li>'))
            ->toBeLessThanOrEqual(1, "Header section '{$section}' muncul lebih dari sekali");
    }
});
