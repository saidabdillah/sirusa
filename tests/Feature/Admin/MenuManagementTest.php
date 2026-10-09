<?php

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('menu', 'admin');

beforeEach(function () {
    seedAkses();

    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'menu-sa@test.com']);
    $this->admin = User::factory()->admin()->create(['email' => 'menu-admin@test.com']);
});

/**
 * Mewakili penyimpanan menu dari modal Tambah/Ubah. Superadmin hanya membuat
 * menu tampilan: payload dasar = label, icon, section, urutan. `route`/`scope`
 * BUKAN bidang form -- arah link-nya diisi developer lewat koding
 * (`MenuSeeder`/`tinker`), jadi request yang tetap mengirimnya diabaikan
 * `validated()`. `aktif`/`wajib` juga tanpa input: nilainya diset server
 * (aktif=true, wajib=false saat buat).
 *
 * @return array<string, mixed>
 */
function menuPayload(array $overrides = []): array
{
    return array_merge([
        'label' => 'Menu Baru',
        'icon' => 'fas fa-star',
        'section' => 'Administrasi',
        'urutan' => 99,
    ], $overrides);
}

test('super admin can open kelola page with modal forms', function () {
    $menu = Menu::where('scope', 'admin.beasiswa')->firstOrFail();

    actingAs($this->superAdmin)
        ->get(route('admin.menukelola.index'))
        ->assertOk()
        ->assertSee('Form Tambah Menu')
        ->assertSee('modal-tambah-menu', false)
        ->assertSee('modal-ubah-menu-'.$menu->id, false)
        ->assertSee('name="label"', false);
});

test('super admin can create a menu and it is auto granted to super admin', function () {
    $superAdminRoleId = Role::where('name', 'super_admin')->firstOrFail()->id;

    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload())
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    $menu = Menu::where('label', 'Menu Baru')->firstOrFail();
    expect($menu->section)->toBe('Administrasi')
        // Payload tidak membawa route/scope (form tidak punya inputnya): menu
        // lahir sebagai tampilan; route/scope diisi developer di koding.
        ->and($menu->route)->toBeNull()
        ->and($menu->scope)->toBeNull()
        // `aktif`/`wajib` tidak punya input: nilainya ditulis server.
        ->and($menu->aktif)->toBeTrue()
        ->and($menu->wajib)->toBeFalse();

    $this->assertDatabaseHas('role_menu', [
        'role_id' => $superAdminRoleId,
        'menu_id' => $menu->id,
    ]);
});

/**
 * route/scope bukan bagian form (superadmin hanya membuat menu tampilan); rule
 * keduanya tidak ada, jadi request yang masih mengirim nilainya DIABAIKAN
 * `store()` -- tersimpan null. Satu-satunya jalur mengisinya adalah koding
 * (`MenuSeeder`/`tinker`), bukan UI.
 */
test('menu store mengabaikan route dan scope yang dikirim', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload([
            'route' => 'admin.beasiswa.index',
            'scope' => 'admin.beasiswa',
        ]))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    $menu = Menu::where('label', 'Menu Baru')->firstOrFail();
    expect($menu->route)->toBeNull()
        ->and($menu->scope)->toBeNull();
});

/**
 * `Rule::in(Menu::sections())` sudah dilepas: section baru boleh diketik
 * langsung di form (Select2 `tags`) dan `Menu::sections()` menurunkan daftarnya
 * dari isi tabel `menus`, jadi section yang belum ada bukan error validasi.
 */
test('menu store accepts a brand new section', function () {
    expect(Menu::sections())->not->toContain('Section Lain');

    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['section' => 'Section Lain']))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    $menu = Menu::where('label', 'Menu Baru')->firstOrFail();
    expect($menu->section)->toBe('Section Lain')
        ->and(Menu::sections())->toContain('Section Lain');
});

/**
 * Modal Ubah juga tidak mengirim route/scope: `update()` memakai `validated()`
 * dan rule keduanya sudah dihilangkan, jadi route/scope apa pun yang dikirim
 * DIABAIKAN dan nilai tersimpan tetap utuh (koding-only).
 */
test('menu update mengabaikan route dan scope yang dikirim', function () {
    $menu = Menu::create([
        'label' => 'Tautan Lama',
        'icon' => 'fas fa-star',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Administrasi',
        'urutan' => 5,
        'aktif' => true,
        'wajib' => false,
    ]);

    actingAs($this->superAdmin)
        ->put(route('admin.menukelola.perbarui', $menu), menuPayload([
            'route' => 'admin.catpil.index',
            'scope' => 'admin.catpil',
        ]))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    expect($menu->fresh()->route)->toBe('admin.beasiswa.index')
        ->and($menu->fresh()->scope)->toBe('admin.beasiswa')
        ->and($menu->fresh()->label)->toBe('Menu Baru')
        ->and($menu->fresh()->section)->toBe('Administrasi');
});

/**
 * Route dan Scope TIDAK punya input di kedua modal: superadmin hanya membuat
 * menu untuk tampil di sidebar, dan keduanya diisi DEVELOPER via koding
 * (`MenuSeeder`/`tinker`) -- form sengaja tidak menawarkannya. `aktif` dan
 * `wajib` juga tetap tanpa input: keduanya diset server (aktif=true,
 * wajib=false saat buat) dan tidak pernah diedit lewat form.
 */
test('form menu tidak menampilkan route dan scope, juga tidak aktif dan wajib', function () {
    $html = actingAs($this->superAdmin)->get(route('admin.menukelola.index'))->getContent();

    $tambah = Str::betweenFirst($html, 'id="modal-tambah-menu"', 'id="modal-ubah-menu-');

    foreach (['route-tambah', 'scope-tambah', 'aktif-tambah', 'wajib-tambah'] as $idHilang) {
        expect($tambah)->not->toContain('id="'.$idHilang.'"');
    }

    foreach (['name="route"', 'name="scope"', 'name="aktif"', 'name="wajib"', 'daftar-route', 'Aktif (tampil di sidebar)', 'Wajib (selalu digrant ke semua role)'] as $hilang) {
        expect($tambah)->not->toContain($hilang);
    }

    // Modal Ubah dirender sekali per baris, jadi cukup dicek satu instance.
    $ubah = Str::betweenFirst($html, 'id="modal-ubah-menu-', '</form>');

    foreach (['name="route"', 'name="scope"', 'name="aktif"', 'name="wajib"', 'daftar-route'] as $hilang) {
        expect($ubah)->not->toContain($hilang);
    }
});

/**
 * Kedua input hilang dari form, bukan dari kolom: request yang tetap
 * membawanya diabaikan, dan menu yang sudah punya nilai `aktif`/`wajib`
 * tidak boleh berubah hanya karena diedit.
 */
test('menu store mengabaikan aktif dan wajib yang masih dikirim', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['aktif' => 0, 'wajib' => 1]))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    $menu = Menu::where('label', 'Menu Baru')->firstOrFail();
    expect($menu->aktif)->toBeTrue()
        ->and($menu->wajib)->toBeFalse();
});

test('menu update mempertahankan aktif dan wajib yang tersimpan', function () {
    $menu = Menu::create([
        'label' => 'Tersembunyi',
        'icon' => 'fas fa-star',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Administrasi',
        'urutan' => 5,
        'aktif' => false,
        'wajib' => true,
    ]);

    actingAs($this->superAdmin)
        ->put(route('admin.menukelola.perbarui', $menu), menuPayload(['label' => 'Terlihat']))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    expect($menu->fresh()->label)->toBe('Terlihat')
        ->and($menu->fresh()->aktif)->toBeFalse()
        ->and($menu->fresh()->wajib)->toBeTrue()
        // Route/scope tidak ikut berubah hanya karena form tidak membawanya.
        ->and($menu->fresh()->route)->toBe('admin.beasiswa.index')
        ->and($menu->fresh()->scope)->toBe('admin.beasiswa');
});

/**
 * Field wajib ditandai bintang secara VISUAL saja (span `text-danger`) --
 * `required` HTML dilarang rules form aplikasi ini. Route dan Scope BUKAN field
 * form (koding-only), jadi hanya field tampilan yang berbintang.
 */
test('form tambah menu menandai field wajib dengan bintang', function () {
    $html = actingAs($this->superAdmin)->get(route('admin.menukelola.index'))->getContent();

    $tambah = Str::betweenFirst($html, 'id="modal-tambah-menu"', 'id="modal-ubah-menu-');
    expect($tambah)->toBeString();

    foreach (['Label Menu', 'Section', 'Urutan'] as $wajib) {
        expect($tambah)->toContain('>'.$wajib.' <span class="text-danger">*</span></label>');
    }

    // Route/Scope bukan field form, jadi tidak boleh ada label berbintang.
    foreach (['Route', 'Scope'] as $bukanField) {
        expect($tambah)->not->toContain('>'.$bukanField.' <span class="text-danger">*</span></label>');
    }
});

test('super admin can update a menu label and section', function () {
    $menu = Menu::create([
        'label' => 'Lama',
        'icon' => 'fas fa-star',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Administrasi',
        'urutan' => 5,
        'aktif' => true,
    ]);

    actingAs($this->superAdmin)
        ->put(route('admin.menukelola.perbarui', $menu), menuPayload([
            'label' => 'Baru',
            'urutan' => 7,
        ]))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    expect($menu->fresh()->label)->toBe('Baru')
        ->and($menu->fresh()->urutan)->toBe(7);
});

test('menu update cannot set itself as parent', function () {
    $menu = Menu::create([
        'label' => 'Sendiri',
        'icon' => 'fas fa-star',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Administrasi',
        'urutan' => 5,
        'aktif' => true,
    ]);

    actingAs($this->superAdmin)
        ->put(route('admin.menukelola.perbarui', $menu), menuPayload(['parent_id' => $menu->id]))
        ->assertSessionHasErrors('parent_id');
});

test('super admin cannot delete a wajib menu', function () {
    $wajibMenu = Menu::where('wajib', true)->firstOrFail();

    actingAs($this->superAdmin)
        ->delete(route('admin.menukelola.hapus', $wajibMenu))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('error');

    expect(Menu::where('id', $wajibMenu->id)->exists())->toBeTrue();
});

test('super admin cannot delete a menu that still has children', function () {
    $parent = Menu::where('label', 'Beasiswa')->whereNotNull('children')->firstOrFail();

    actingAs($this->superAdmin)
        ->delete(route('admin.menukelola.hapus', $parent))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('error');

    expect(Menu::where('id', $parent->id)->exists())->toBeTrue();
});

test('super admin can delete a leaf menu', function () {
    $menu = Menu::create([
        'label' => 'Daun',
        'icon' => 'fas fa-star',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Administrasi',
        'urutan' => 90,
        'aktif' => true,
    ]);

    actingAs($this->superAdmin)
        ->delete(route('admin.menukelola.hapus', $menu))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    expect(Menu::where('id', $menu->id)->exists())->toBeFalse();
});

test('non super admin gets forbidden on all menu management routes', function () {
    $menu = Menu::firstOrFail();

    actingAs($this->admin)->get(route('admin.menu.index'))->assertForbidden();
    actingAs($this->admin)->get(route('admin.menukelola.index'))->assertForbidden();
    actingAs($this->admin)->post(route('admin.menukelola.simpan'), menuPayload())->assertForbidden();
    actingAs($this->admin)->put(route('admin.menukelola.perbarui', $menu), menuPayload())->assertForbidden();
    actingAs($this->admin)->delete(route('admin.menukelola.hapus', $menu))->assertForbidden();
    actingAs($this->admin)->put(route('admin.menu.grants'), ['role_id' => Role::where('name', 'kesra')->firstOrFail()->id, 'menus' => []])->assertForbidden();
});

test('super admin can open kelola menu list page', function () {
    actingAs($this->superAdmin)
        ->get(route('admin.menukelola.index'))
        ->assertOk()
        ->assertSee('Kelola Menu')
        ->assertSee('Tambah Menu')
        ->assertSee('Daftar Menu Sidebar');
});

test('access menu page filters by role and renders the selected role checklist', function () {
    $kesraId = Role::where('name', 'kesra')->firstOrFail()->id;

    // Nilai awal filter kosong: belum ada role terpilih dan checklist disembunyikan.
    actingAs($this->superAdmin)
        ->get(route('admin.menu.index'))
        ->assertOk()
        ->assertSee('id="roleFilter"', false)
        ->assertSee('-- Pilih Role --')
        ->assertDontSee('name="role_id"', false)
        ->assertDontSee('Tambah Menu');

    actingAs($this->superAdmin)
        ->get(route('admin.menu.index', ['role' => $kesraId]))
        ->assertOk()
        ->assertSee('name="role_id"', false)
        ->assertSee('value="'.$kesraId.'"', false)
        ->assertSee('Pilih semua')
        ->assertSee('Simpan Akses untuk Kesra');
});

test('saving one role does not wipe other roles', function () {
    $kesraId = Role::where('name', 'kesra')->firstOrFail()->id;
    $kampusId = Role::where('name', 'kampus')->firstOrFail()->id;
    $wajibIds = DB::table('menus')->where('wajib', true)->pluck('id')->all();

    $kesraMenus = DB::table('menus')->where('aktif', true)->limit(3)->pluck('id')->all();
    DB::table('role_menu')->where('role_id', $kesraId)->delete();
    foreach ($kesraMenus as $menuId) {
        DB::table('role_menu')->insert(['role_id' => $kesraId, 'menu_id' => $menuId]);
    }

    $kampusMenus = DB::table('menus')->where('aktif', true)->limit(2)->pluck('id')->all();

    actingAs($this->superAdmin)
        ->put(route('admin.menu.grants'), ['role_id' => $kampusId, 'menus' => $kampusMenus])
        ->assertRedirect(route('admin.menu.index', ['role' => $kampusId]))
        ->assertSessionHas('success');

    expect(DB::table('role_menu')->where('role_id', $kampusId)->pluck('menu_id')->all())
        ->toEqualCanonicalizing(array_unique(array_merge($kampusMenus, $wajibIds)));

    // Grant role kesra tidak boleh ikut terhapus oleh penyimpanan role kampus.
    expect(DB::table('role_menu')->where('role_id', $kesraId)->pluck('menu_id')->all())
        ->toEqualCanonicalizing($kesraMenus);
});

test('saving super admin keeps wajib and admin module menus', function () {
    $superAdminId = Role::where('name', 'super_admin')->firstOrFail()->id;

    actingAs($this->superAdmin)
        ->put(route('admin.menu.grants'), ['role_id' => $superAdminId, 'menus' => []])
        ->assertRedirect(route('admin.menu.index', ['role' => $superAdminId]))
        ->assertSessionHas('success');

    $expected = DB::table('menus')->where('aktif', true)
        ->where(function ($query) {
            $query->where('wajib', true)
                ->orWhereIn('scope', ['admin.role', 'admin.menu', 'admin.menukelola', 'admin.pengguna']);
        })
        ->pluck('id')->all();

    expect(DB::table('role_menu')->where('role_id', $superAdminId)->pluck('menu_id')->all())
        ->toEqualCanonicalizing($expected);
});

test('sidebarMenus returns only top level menus with children nested', function () {
    $user = $this->admin;
    $topLevel = $user->sidebarMenus();

    $childIds = Menu::query()->whereNotNull('parent_id')->pluck('id');

    foreach ($childIds as $childId) {
        expect($topLevel->pluck('id'))->not->toContain($childId);
    }

    // Role kesra diberi grant `admin.kesra` (scope induk dropdown) plus tiga
    // scope anak `admin.kesra.index`, `admin.penerima`, dan
    // `admin.kesra.disetujui`. "Status Verifikasi" (scope `admin.kesra.status`)
    // adalah leaf mandiri di section "Administrasi", terpisah dari dropdown
    // Kesra. Dicocokkan lewat `scope`, bukan `label`: sejak label dipersingkat,
    // "Kampus" dipakai dua menu (antrean verifikasi di section Verifikasi dan
    // master data di section Administrasi) dan tidak bisa dibedakan dari label.
    $kesraDropdown = $topLevel->firstWhere('scope', 'admin.kesra');
    expect($kesraDropdown)->not->toBeNull()
        ->and($kesraDropdown->label)->toBe('Kesra')
        ->and($kesraDropdown->children->pluck('scope')->all())
        ->toEqualCanonicalizing(['admin.kesra.index', 'admin.penerima', 'admin.kesra.disetujui'])
        ->and($topLevel->firstWhere('scope', 'admin.catpil'))->toBeNull()
        ->and($topLevel->firstWhere('scope', 'admin.kampusverif'))->toBeNull()
        // "Verifikasi" kini memang ADA sebagai menu anak (di bawah Kesra),
        // tapi `sidebarMenus()` hanya mengembalikan `parent_id` null, jadi
        // item top-level tidak boleh pernah berlabel "Verifikasi".
        ->and($topLevel->firstWhere('label', 'Verifikasi'))->toBeNull()
        // Leaf "Status Verifikasi" mandiri ikut di sidebar role kesra.
        ->and($topLevel->firstWhere('scope', 'admin.kesra.status'))->not->toBeNull();
});

test('sidebar renders child menu label only once (no duplicate)', function () {
    $response = actingAs($this->admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('<span>Kesra</span>', false);

    // Di-scope ke `<span>` supaya tidak ikut menghitung "Dasbor Kesra" yang
    // muncul di judul halaman.
    expect(substr_count($response->getContent(), '<span>Kesra</span>'))->toBe(1);
});

/**
 * Form Tambah Menu harus selalu dibuka dalam kondisi bersih. Nilai dari
 * `old()` hanya dipakai kalau `modal_target` cocok, jadi modal yang lain tidak
 * boleh ikut terisi.
 */
test('form tambah menu selalu kosong dan dropdownnya tidak preselect', function () {
    $response = actingAs($this->superAdmin)->get(route('admin.menukelola.index'));

    $response->assertOk();
    $html = $response->getContent();

    // `modal-tambah.blade.php` di-partial satu kali, jadi cukup dihitung satu.
    expect(substr_count($html, 'id="modal-tambah-menu"'))->toBe(1);

    // Semua input teks kosong -- tidak ada nilai yang tersisa dari modal
    // Edit sebelumnya. (route/scope bukan field form, jadi tidak dicek.)
    expect($html)->toMatch('/id="label-tambah"[^>]*value=""/');

    // Dropdown punya opsi kosong, dan tidak ada opsi yang ter-select. Tanpa
    // `<option value="">` yang ini akan diam-diam memilih "Menu Utama".
    expect($html)->toContain('<option value="">— Pilih —</option>');

    $tambah = Str::between($html, 'id="modal-tambah-menu"', 'id="modal-ubah-menu-');
    expect($tambah)->toBeString();

    // Opsi terpilih di form Tambah hanya boleh yang kosong. Dicek dari
    // ` selected>` (dengan tanda `>`), bukan `" selected"`: Blade merender
    // `{{ $x ? 'selected' : '' }}` sebagai `<option value="X" >` saat kosong,
    // jadi spasi sebelum `>` selalu ada dan pola longgar akan selalu cocok.
    //
    // `betweenFirst`, bukan `between`: `Str::between()` di Laravel versi ini
    // berakhir di occurance TERAKHIR dari needle penutup. Di halaman ini
    // `</select>` muncul puluhan kali, jadi potongannya akan meluber ke
    // seluruh sisa halaman dan assertion "tidak ada opsi terpilih" jadi sia-sia.
    foreach (['section-tambah', 'parent_id-tambah'] as $idSelect) {
        $isi = Str::betweenFirst($tambah, 'id="'.$idSelect.'"', '</select>');

        expect($isi)->toContain('<option value=""')
            ->and(substr_count($isi, ' selected>'))
            ->toBe(0, "Dropdown #{$idSelect} di form Tambah menyeleksi opsi non-kosong");
    }
});

/**
 * Perbaikan form Tambah tidak boleh merusak form Edit: setiap modal Edit punya
 * instance sendiri yang dirender dari `$menu`, dan `modal_target`-nya unik per
 * menu sehingga error dari satu modal tidak bocor ke modal lain.
 */
test('form edit menu mempertahankan data tersimpan dan tidak tertukar', function () {
    $menu = Menu::query()->where('label', 'Catpil')->firstOrFail();

    $html = actingAs($this->superAdmin)->get(route('admin.menukelola.index'))->getContent();

    $ubah = Str::betweenFirst($html, 'id="modal-ubah-menu-'.$menu->id.'"', '</form>');

    expect($ubah)
        ->toMatch('/id="label-'.$menu->id.'"[^>]*value="'.preg_quote($menu->label, '/').'"/')
        ->toMatch('/id="urutan-'.$menu->id.'"[^>]*value="'.$menu->urutan.'"/')
        // Dropdown Edit menyeleksi nilai tersimpan, bukan placeholder.
        ->toMatch('/<option value="Verifikasi" selected>/')
        // Target modal Edit harus unik: kalau tidak, `old()` dari Edit satu
        // menu bisa muncul di modal Edit menu lain.
        ->toContain('<input type="hidden" name="modal_target" value="modal-ubah-menu-'.$menu->id.'">');
});

/**
 * Sisa error validasi AJAX harus dibersihkan saat modal ditutup, bukan saat
 * dibuka. Kalau dibersihkan saat `show`, jalur fallback `old('modal_target')`
 * yang membuka ulang modal setelah page reload akan menghapus error yang
 * sudah dirender server sebelum sempat dibaca.
 */
test('modal kelola menu membersihkan state error saat ditutup', function () {
    $html = actingAs($this->superAdmin)->get(route('admin.menukelola.index'))->getContent();

    expect($html)
        ->toContain("on('hidden.bs.modal'")
        // Nilai input tidak boleh ikut di-backup/restore: kalau ada, form Edit
        // akan mengosongkan dirinya sendiri.
        ->not->toMatch('/hidden\.bs\.modal[\s\S]{0,600}?\.val\(/')
        ->not->toMatch('/hidden\.bs\.modal[\s\S]{0,600}?\.attr\(\s*[\'"]value[\'"]/');
});

/**
 * `@include` di-inline ke file induk, jadi isi `@push('script')` beserta
 * partial yang di-`@include`-nya ikut dikompilasi Blade sebagai PHP. Satu
 * direktif yang tidak balance di dalam KOMENTAR JS (mis. `@error` tanpa
 * `@enderror`) membuat seluruh halaman 500 dengan "unexpected end of file,
 * expecting elseif/else/endif" -- error yang jauh dari lokasi penyebabnya.
 *
 * Dicek statis supaya gagal di test ini, bukan TimeoutException 500 di test
 * lain yang kebetulan me-render halaman yang sama.
 */
test('blok script kelola menu bebas dari direktif Blade', function () {
    $sumber = file_get_contents(resource_path('views/admin/menu/kelola.blade.php'));

    $mulai = strpos($sumber, "@push('script')");
    expect($mulai)->not->toBeFalse();

    // Mulai SESUDAH `@push('script')` supaya directive pembuka tidak ikut dihitung.
    $blok = substr($sumber, $mulai + strlen("@push('script')"));

    // Satu-satunya direktif yang boleh ada adalah `@json(...)` pada baris
    // `openModal` (memang dipakai) plus `@endpush` penutup blok.
    $boleh = ['@endpush', '@json'];

    preg_match_all('/@[a-z]+/i', $blok, $direktif);

    expect(array_map('strtolower', array_unique($direktif[0])))->each->toBeIn($boleh);
});

// ─── Select2 `tags`: buat induk & section baru langsung dari form ────────

/**
 * Select2 adalah satu-satunya jalan admin membuat grup/section yang belum
 * ada, jadi konfigurasinya dikunci: tanpa `tags` admin hanya bisa memilih
 * yang sudah terdaftar, tanpa `dropdownParent` dropdown terpotong stacking
 * context modal Bootstrap, tanpa `data-placeholder` `allowClear` tidak punya
 * tujuan, dan tanpa `<option value="">` browser diam-diam memilih opsi pertama
 * (lihat test "selalu kosong dan dropdownnya tidak preselect" di atas).
 */
test('dropdown induk dan section memakai select2 tags', function () {
    $html = actingAs($this->superAdmin)->get(route('admin.menukelola.index'))->getContent();

    $tambah = Str::betweenFirst($html, 'id="modal-tambah-menu"', 'id="modal-ubah-menu-');
    foreach (['parent_id-tambah', 'section-tambah'] as $idSelect) {
        expect($tambah)
            ->toContain('id="'.$idSelect.'"')
            ->toContain('select2-baru');
    }

    expect($tambah)
        ->toContain('data-placeholder="— Menu Utama (tanpa induk) —"')
        ->toContain('data-placeholder="— Pilih —"');

    // Inisialisasinya ada di dalam blok `@push('script')` halaman Kelola.
    expect($html)
        ->toContain('$(\'.select2-baru\').each(function () {')
        ->toContain('dropdownParent: $select.closest(\'.modal\')')
        ->toContain('tags: true')
        ->toContain('allowClear: true');
});

test('isExistingMenuId membedakan id menu yang ada dan nama baru', function () {
    $menu = Menu::whereNull('parent_id')->orderBy('id')->firstOrFail();

    expect(Menu::isExistingMenuId((string) $menu->id))->toBeTrue()
        ->and(Menu::isExistingMenuId($menu->id))->toBeTrue()
        ->and(Menu::isExistingMenuId('99999'))->toBeFalse()
        ->and(Menu::isExistingMenuId('Grup Baru'))->toBeFalse()
        ->and(Menu::isExistingMenuId(''))->toBeFalse()
        ->and(Menu::isExistingMenuId(null))->toBeFalse();
});

/**
 * `parent_id` boleh berupa teks: inilah yang dikirim Select2 `tags` saat
 * admin mengetik nama grup baru dan menekan Enter. Menu induk yang dibuat
 * sengaja serendah mungkin (tanpa route/scope, ikon folder, urutan & section
 * mengikuti anaknya) supaya tidak pernah memberi akses route apa pun.
 */
test('parent_id berupa nama baru membuat menu induk top-level', function () {
    expect(Menu::whereNull('parent_id')->where('label', 'Grup Keuangan')->exists())->toBeFalse();

    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['parent_id' => 'Grup Keuangan', 'urutan' => 12]))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    $induk = Menu::whereNull('parent_id')->where('label', 'Grup Keuangan')->firstOrFail();
    $anak = Menu::where('label', 'Menu Baru')->firstOrFail();

    expect($anak->parent_id)->toBe($induk->id)
        ->and($induk->route)->toBeNull()
        ->and($induk->scope)->toBeNull()
        ->and($induk->icon)->toBe('fas fa-folder')
        ->and($induk->section)->toBe('Administrasi')
        ->and($induk->urutan)->toBe(12)
        ->and($induk->aktif)->toBeTrue()
        ->and($induk->wajib)->toBeFalse();

    // Tanpa grant ini induk baru tidak muncul di sidebar siapa pun sampai
    // admin membuka halaman Akses Menu -- sama seperti menu biasa.
    $this->assertDatabaseHas('role_menu', [
        'role_id' => Role::where('name', 'super_admin')->firstOrFail()->id,
        'menu_id' => $induk->id,
    ]);
});

/**
 * Mengetik ulang nama grup yang sudah ada (beda huruf besar/kecil) harus
 * memakai baris yang sama, bukan membuat induk kembar yang membelah sidebar.
 */
test('parent_id nama induk yang sudah ada tidak digandakan', function () {
    $beasiswa = Menu::whereNull('parent_id')->where('label', 'Beasiswa')->firstOrFail();

    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['parent_id' => 'bEaSiSwA']))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    expect(Menu::whereNull('parent_id')->where('label', 'Beasiswa')->count())->toBe(1)
        ->and(Menu::where('label', 'Menu Baru')->firstOrFail()->parent_id)->toBe($beasiswa->id);
});

test('parent_id berupa id menu yang ada tetap diterima', function () {
    $induk = Menu::whereNull('parent_id')->orderBy('id')->firstOrFail();
    $jumlahTopLevelSebelum = Menu::whereNull('parent_id')->count();

    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['parent_id' => (string) $induk->id]))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    expect(Menu::where('label', 'Menu Baru')->firstOrFail()->parent_id)->toBe($induk->id)
        ->and(Menu::whereNull('parent_id')->count())->toBe($jumlahTopLevelSebelum);
});

/**
 * `min:2` hanya berlaku untuk nama BARU, dan karena aturannya pakai
 * `Rule::when()`, id menu yang ada lolos walaupun hanya satu digit.
 */
test('nama induk baru wajib minimal dua karakter', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['parent_id' => 'A']))
        ->assertSessionHasErrors(['parent_id' => 'Nama menu induk baru minimal 2 karakter.']);

    expect(Menu::where('label', 'Menu Baru')->exists())->toBeFalse()
        ->and(Menu::whereNull('parent_id')->where('label', 'A')->exists())->toBeFalse();
});

/**
 * Section baru juga bisa dibuat lewat form Ubah, dan begitu tersimpan ia
 * langsung menjadi bagian `Menu::sections()` -- sumber iterasi sidebar --
 * jadi header-nya tampil tanpa ada yang mengedit kode.
 */
test('section baru langsung menjadi header sidebar', function () {
    expect(Menu::sections())->not->toContain('Arsip Digital');

    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['section' => 'Arsip Digital']))
        ->assertSessionHas('success');

    expect(Menu::sections())->toContain('Arsip Digital');

    actingAs($this->superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<li class="menu-header">Arsip Digital</li>', false);
});

test('form ubah bisa membuat menu induk baru lewat parent_id teks', function () {
    $menu = Menu::create([
        'label' => 'Daun Lepas',
        'icon' => 'fas fa-star',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Administrasi',
        'urutan' => 80,
        'aktif' => true,
    ]);

    actingAs($this->superAdmin)
        ->put(route('admin.menukelola.perbarui', $menu), menuPayload(['parent_id' => 'Grup Edit']))
        ->assertRedirect(route('admin.menukelola.index'))
        ->assertSessionHas('success');

    $induk = Menu::whereNull('parent_id')->where('label', 'Grup Edit')->firstOrFail();

    expect($menu->fresh()->parent_id)->toBe($induk->id)
        ->and($induk->section)->toBe('Administrasi')
        ->and($induk->route)->toBeNull();
});
