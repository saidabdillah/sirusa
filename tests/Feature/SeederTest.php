<?php

use App\Models\Applicant;
use App\Models\Fakultas;
use App\Models\Kampus;
use App\Models\Menu;
use App\Models\Prodi;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('seeder');

/*
|--------------------------------------------------------------------------
| Seeder harus aman dijalankan berkali-kali (spesifikasi batch 2, poin 8)
|--------------------------------------------------------------------------
*/

/**
 * Pohon menu + grant setelah seeder, sudah dinormalisasi supaya bisa
 * dibandingkan antar-jalankan seeder.
 *
 * @return array{menu: list<array>, grant: list<array>}
 */
function sidikMenu(): array
{
    $menu = Menu::query()
        ->orderBy('section')
        ->orderBy('label')
        ->get()
        ->map(fn (Menu $m) => [
            'label' => $m->label,
            'parent' => $m->parent?->label,
            'section' => $m->section,
            'icon' => $m->icon,
            'route' => $m->route,
            'scope' => $m->scope,
            'urutan' => $m->urutan,
            'aktif' => (int) $m->aktif,
            'wajib' => (int) $m->wajib,
        ])
        ->all();

    $grant = DB::table('role_menu')
        ->join('roles', 'roles.id', '=', 'role_menu.role_id')
        ->join('menus', 'menus.id', '=', 'role_menu.menu_id')
        ->orderBy('roles.name')
        ->orderBy('menus.label')
        ->get(['roles.name as role', 'menus.label as menu'])
        ->map(fn ($r) => $r->role.'>'.$r->menu)
        ->all();

    return ['menu' => $menu, 'grant' => $grant];
}

// ─── Section sidebar: nama harus menyebut fungsinya ─────────────────────

test('nama section sidebar tidak memakai nama generik menu admin pengguna', function () {
    foreach (Menu::sections() as $section) {
        expect($section)->not->toBe('Menu Admin')
            ->and($section)->not->toBe('Menu Pengguna');
    }

    // Section yang benar-benar dipakai seeder harus terdaftar di `sections()`,
    // kalau tidak sidebar tidak pernah merendernya.
    foreach (Menu::query()->distinct()->pluck('section') as $section) {
        expect(Menu::sections())
            ->toContain($section, "Section '{$section}' tidak ada di Menu::sections()");
    }
});

test('setiap role melihat grup section yang memang untuknya', function () {
    seedAkses();

    $section = fn (string $peran) => actingAs(User::factory()->{$peran}()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    // Grup "Administrasi" = back-office, "Layanan Mahasiswa" = menu milik user.
    expect($section('capil'))->toContain('Administrasi')->not->toContain('Layanan Mahasiswa')
        ->and($section('standardUser'))->toContain('Layanan Mahasiswa')->not->toContain('Administrasi');
});

/**
 * Pasangan label -> section dikunci di sini.
 *
 * `menus.section` bukan foreign key, jadi menukar section di `MenuSeeder`
 * tanpa menjalankan seeder akan meninggalkan baris lama di section lamanya --
 * dan karena sidebar hanya mengiterasi `Menu::sections()`, menunya hilang tanpa
 * error sama sekali. Test inilah yang menangkapnya.
 *
 * Urutannya ikut diperiksa: section menentukan urutan tampil di sidebar, bukan
 * cuma pengelompokannya.
 */
test('setiap menu punya section yang sesuai di sidebar', function () {
    seedAkses();

    $pasangan = [
        // Menu Utama
        'Dasbor' => 'Menu Utama',
        // SECTION 1 -- ADMINISTRASI
        'Beasiswa' => 'Administrasi',
        'Pendaftar' => 'Administrasi',
        'Master Data' => 'Administrasi',
        // SECTION 2 -- VERIFIKASI
        'Dukcapil' => 'Verifikasi',
        'Kampus' => 'Verifikasi',
        'Kesra' => 'Verifikasi',
        // SECTION 3 -- ADMINISTRATOR
        'Kelola Akses' => 'Administrator',
        // Layanan Mahasiswa
        'Beasiswa Saya' => 'Layanan Mahasiswa',
    ];

    foreach ($pasangan as $label => $section) {
        // Section yang tidak terdaftar di `Menu::sections()` tidak akan pernah
        // dirender sidebar, jadi assert ini harus lebih dulu.
        expect(Menu::sections())->toContain($section);

        $menu = Menu::query()->where('label', $label)->whereNull('parent_id')->first();

        expect($menu)->not->toBeNull()
            ->and($menu->section)->toBe($section);
    }

    // Tidak boleh ada menu di luar section yang dikenal: sidebar tidak akan
    // merendernya sama sekali.
    foreach (Menu::query()->distinct()->pluck('section') as $section) {
        expect(Menu::sections())->toContain($section);
    }

    // Urutan section: operasional harian -> antrean verifikasi -> alat sistem.
    $urutanSection = Menu::query()
        ->whereNull('parent_id')
        ->whereIn('section', ['Administrasi', 'Verifikasi', 'Administrator'])
        ->pluck('section')
        ->unique()
        ->values()
        ->all();

    $sectionSesuaiUrutanSidebar = array_values(array_filter(
        Menu::sections(),
        fn (string $s) => in_array($s, ['Administrasi', 'Verifikasi', 'Administrator'], true)
    ));

    expect(array_values(array_intersect($urutanSection, $sectionSesuaiUrutanSidebar)))
        ->toEqual($sectionSesuaiUrutanSidebar);
});

/**
 * Setelah section dirombak, tiap role harus tetap melihat section-nya sendiri.
 * Role yang tidak punya menu di suatu section tidak boleh melihat header
 * section itu -- itulah gejala yang paling kelihatan kalau filter permission
 * sampai ikut dirusak.
 */
test('section baru tidak bocor menu ke role yang tidak berhak', function () {
    seedAkses();

    $html = fn (string $peran) => actingAs(User::factory()->{$peran}()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    // Role user: only "Layanan Mahasiswa". The new sections must not appear.
    $user = $html('standardUser');
    foreach (['Menu Utama', 'Administrasi', 'Verifikasi', 'Administrator', 'Layanan Mahasiswa'] as $section) {
        if ($section === 'Layanan Mahasiswa' || $section === 'Menu Utama') {
            continue;
        }

        expect($user)->not->toContain('<li class="menu-header">'.$section.'</li>');
    }

    // Role capil: has "Administrasi" and "Verifikasi" (its own queue), but
    // not "Administrator".
    $capil = $html('capil');
    expect($capil)
        ->toContain('<li class="menu-header">Verifikasi</li>')
        ->toContain(route('admin.capil.index'))
        ->not->toContain('<li class="menu-header">Administrator</li>')
        ->not->toContain(route('admin.role.index'));

    // Role kesra: has "Administrator" (granted `admin.pengguna`) but not the
    // other two verification queues.
    $kesra = $html('admin');
    expect($kesra)
        ->toContain('<li class="menu-header">Administrator</li>')
        ->toContain(route('admin.pengguna.index'))
        ->toContain(route('admin.kesra.index'))
        ->not->toContain(route('admin.capil.index'))
        ->not->toContain(route('admin.kampusverif.index'))
        ->not->toContain(route('admin.role.index'));
});

// ─── Grant menu: kunci yang meleset harus gagal keras ───────────────────

test('grant yang tidak cocok dengan menu mana pun membuat seeding gagal keras', function () {
    seedAkses();

    $keyToId = Menu::query()->pluck('id', 'scope')->all();
    $seeder = new MenuSeeder;

    $pasangGrant = new ReflectionMethod($seeder, 'pasangGrant');
    $pasangGrant->setAccessible(true);

    // Kunci `scope` yang benar tetap lolos.
    $pasangGrant->invoke($seeder, ['capil' => ['admin.capil']], $keyToId);

    // Kunci yang tidak ada harus melempar, bukan dilewati diam-diam: inilah
    // yang menahan bug "ganti label menu tapi grant-nya hilang".
    expect(fn () => $pasangGrant->invoke($seeder, ['capil' => ['scope-hantu']], $keyToId))
        ->toThrow(RuntimeException::class, 'scope-hantu');
});

test('kunci grant berbasis label berubah kalau labelnya diubah', function () {
    // Penjaga untuk trap di `pasangGrant`: kunci `Str::kebab($label)` berubah
    // bersama labelnya. Kalau test ini gagal setelah sengaja mengubah label,
    // berarti daftar grant di `MenuSeeder` harus ikut diperbarui.
    expect(Str::kebab('Menu Beasiswa'))->not->toBe(Str::kebab('Beasiswa Saya'));
});

test('menuseeder idempoten: dua kali jalan menghasilkan pohon dan grant yang sama', function () {
    seedAkses();
    $pertama = sidikMenu();

    (new MenuSeeder)->run();
    $kedua = sidikMenu();

    expect($kedua['menu'])->toEqual($pertama['menu'])
        ->and($kedua['grant'])->toEqual($pertama['grant'])
        // 9 top-level + 8 anak = 17
        ->and(count($pertama['menu']))->toBe(17);
});

test('menuseeder tidak menggandakan grant di pivot role menu', function () {
    seedAkses();

    $sebelum = DB::table('role_menu')->count();
    (new MenuSeeder)->run();
    $sesudah = DB::table('role_menu')->count();

    // Punya primary key (role_id, menu_id); kalau tidak `insertOrIgnore`,
    // jalankan kedua akan kena violate unique key.
    expect($sesudah)->toBe($sebelum);
});

test('semua route yang direferensikan menu benar-benar terdaftar', function () {
    seedAkses();

    foreach (Menu::whereNotNull('route')->get() as $menu) {
        expect(Route::has($menu->route))
            ->toBeTrue("Route '{$menu->route}' (menu '{$menu->label}') tidak terdaftar");
    }
});

test('setiap scope menu memakai huruf kecil', function () {
    seedAkses();

    foreach (Menu::whereNotNull('scope')->get() as $menu) {
        expect($menu->scope)
            ->toMatch('/^[a-z][a-z0-9._-]*$/', "Scope '{$menu->scope}' harus huruf kecil semua");
    }
});

test('scope menukelola_grant menutupi route kelolak menu', function () {
    seedAkses();

    $scope = Menu::where('scope', 'admin.menukelola')->value('scope');

    expect($scope)->toBe('admin.menukelola')
        ->and(User::menuScopeCovers('admin.menukelola', 'admin.menukelola.index'))->toBeTrue()
        ->and(User::menuScopeCovers('admin.menukelola', 'admin.menukelola.hapus'))->toBeTrue()
        // Prefix `admin.menu` tidak boleh ikut menutupi, itu bentrok dengan
        // menu "Akses Menu".
        ->and(User::menuScopeCovers('admin.menu', 'admin.menukelola.index'))->toBeFalse();
});

test('run renamed menuseeder membuang scope lama supaya route tidak yatim', function () {
    seedAkses();

    // Simulasikan database yang sudah di-seed dengan nama lama.
    $lama = Menu::where('scope', 'admin.menukelola')->first();
    $lama->update([
        'scope' => 'admin.menuKelola',
        'route' => 'admin.menuKelola.index',
    ]);

    expect(Menu::where('scope', 'admin.menuKelola')->exists())->toBeTrue();

    (new MenuSeeder)->run();

    // Baris lama harus hilang; kalau tidak, sidebar memanggil
    // `route('admin.menuKelola.index')` yang sudah tidak ada -> 500.
    expect(Menu::where('scope', 'admin.menuKelola')->exists())->toBeFalse()
        ->and(Menu::where('scope', 'admin.menukelola')->exists())->toBeTrue()
        ->and(Menu::whereNotNull('parent_id')->whereNotIn('parent_id', Menu::pluck('id'))->exists())->toBeFalse();
});

// ─── Akun demo role `user` ──────────────────────────────────────────────

test('seeder membuat akun role user yang siap dipakai untuk testing', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::where('username', 'user')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->email)->toBe('user@sirusa.test')
        ->and($user->status)->toBe('aktif')
        // Kata sandi di-hash oleh cast `hashed` di model User, jadi yang
        // dibandingkan adalah hash-nya, bukan string polos.
        ->and(Hash::check('password', $user->password))->toBeTrue();

    $profile = $user->profile;

    expect($profile)->not->toBeNull()
        ->and($profile->nik)->toBe('6300000000000001')
        ->and($profile->nama_lengkap)->toBe('User Demo')
        ->and($profile->nim)->not->toBeNull()
        ->and($profile->tempat_lahir)->not->toBeNull()
        ->and($profile->tanggal_lahir)->not->toBeNull()
        ->and($profile->jenis_kelamin)->not->toBeNull()
        ->and($profile->telepon)->not->toBeNull()
        ->and($profile->alamat)->not->toBeNull()
        ->and($profile->provinsi)->not->toBeNull()
        ->and($profile->kabupaten_kota)->not->toBeNull()
        ->and($profile->kecamatan)->not->toBeNull()
        ->and($profile->desa_kelurahan)->not->toBeNull()
        ->and($profile->ipk)->not->toBeNull()
        ->and($profile->semester)->not->toBeNull()
        ->and($profile->ukt)->not->toBeNull()
        ->and($profile->nama_ayah)->not->toBeNull()
        ->and($profile->nama_ibu)->not->toBeNull();
});

test('verif_kesra pada data demo selalu mengikuti status pendaftaran', function () {
    $this->seed(DatabaseSeeder::class);
    // `UserSeeder` sengaja tidak ikut `DatabaseSeeder` (dinyalakan lewat
    // uncomment), tapi konsistensi penanda Kesra di sana tetap harus benar --
    // paling mudah dicek dengan menjalankannya langsung. Role, kampus, dan
    // beasiswa sudah dibuat `UserDemoSeeder`.
    $this->seed(UserSeeder::class);

    $sudahDiputuskan = Applicant::query()
        ->whereIn('status', ['diterima', 'ditolak'])
        ->with('user.profile')
        ->get();

    expect($sudahDiputuskan)->not->toBeEmpty();

    foreach ($sudahDiputuskan as $applicant) {
        // Kesra tidak memverifikasi profil: yang diputuskan ada di baris
        // `pendaftar`. `verif_kesra` hanya menyatakan bahwa pendaftaran itu sudah
        // diputuskan, jadi tidak boleh ada mahasiswa yang punya pendaftaran
        // `diterima` sementara `verif_kesra`-nya masih `menunggu` -- kondisi yang
        // mustahil terjadi di aplikasi tapi mudah muncul kalau seedernya menulis
        // kedua nilai secara terpisah.
        expect($applicant->user?->profile?->verif_kesra, $applicant->user?->username)
            ->toBe('setuju');
    }
});

test('pendaftaran yang sudah diputuskan pada data demo punya tanggal keputusan', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(UserSeeder::class);

    $diterima = Applicant::query()->where('status', 'diterima')->firstOrFail();
    $menunggu = Applicant::query()->where('status', 'verifikasi')->firstOrFail();

    expect($diterima->diputuskan_at)->not->toBeNull()
        ->and($menunggu->diputuskan_at)->toBeNull();
});

test('akun demo user tertaut ke prodi, kampus, dan pendaftar', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::where('username', 'user')->firstOrFail();
    $profile = $user->profile;

    // Rantai relasi: profil -> prodi -> fakultas -> kampus.
    $prodi = Prodi::query()->findOrFail($profile->prodi_id);
    $fakultas = Fakultas::query()->findOrFail($prodi->fakultas_id);
    $kampus = Kampus::query()->findOrFail($fakultas->kampus_id);

    expect($kampus->nama_kampus)->toBe('Universitas Lambung Mangkurat')
        ->and($fakultas->nama)->toBe('Fakultas Teknik')
        ->and($prodi->nama)->toBe('Teknik Informatika')
        ->and(Applicant::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('akun demo user punya satu pendaftar saja dan status verifikasi menunggu', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::where('username', 'user')->firstOrFail();

    expect(Applicant::query()->where('user_id', $user->id)->count())->toBe(1);

    $profile = UserProfile::where('user_id', $user->id)->firstOrFail();

    // Default `menunggu` di level database, jadi akun baru tidak pernah mulai
    // dari `terverifikasi` tanpa keputusan verifikator.
    expect($profile->verif_capil)->toBe('menunggu')
        ->and($profile->verif_kampus)->toBe('menunggu')
        ->and($profile->verif_kesra)->toBe('menunggu');
});

test('akun demo user tidak dibuat dua kali saat seeder dijalankan berulang', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::where('username', 'user')->count())->toBe(1)
        ->and(UserProfile::where('nik', '6300000000000001')->count())->toBe(1)
        ->and(Applicant::query()->count())->toBe(1)
        ->and(Role::where('name', 'user')->count())->toBe(1);
});

test('akun demo user tidak menabrak akun admin yang sudah ada', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('username', 'kesra')->firstOrFail();

    expect($admin->hasRole('kesra'))->toBeTrue()
        ->and($admin->hasRole('user'))->toBeFalse();
});
