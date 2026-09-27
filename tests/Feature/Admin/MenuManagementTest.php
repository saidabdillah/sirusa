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

function menuPayload(array $overrides = []): array
{
    return array_merge([
        'label' => 'Menu Baru',
        'icon' => 'fas fa-star',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Administrasi',
        'urutan' => 99,
        'aktif' => 1,
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
        ->and($menu->route)->toBe('admin.beasiswa.index')
        ->and($menu->aktif)->toBeTrue();

    $this->assertDatabaseHas('role_menu', [
        'role_id' => $superAdminRoleId,
        'menu_id' => $menu->id,
    ]);
});

test('menu store rejects unregistered route', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['route' => 'admin.tidak.ada']))
        ->assertSessionHasErrors('route');
});

test('menu store rejects invalid section', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['section' => 'Section Lain']))
        ->assertSessionHasErrors('section');
});

test('menu store requires route or scope', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.menukelola.simpan'), menuPayload(['route' => null, 'scope' => null]))
        ->assertSessionHasErrors('route');
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
    actingAs($this->admin)->put(route('admin.menu.grants'), ['grants' => []])->assertForbidden();
});

test('super admin can open kelola menu list page', function () {
    actingAs($this->superAdmin)
        ->get(route('admin.menukelola.index'))
        ->assertOk()
        ->assertSee('Kelola Menu')
        ->assertSee('Tambah Menu')
        ->assertSee('Daftar Menu Sidebar');
});

test('access menu matrix is rendered as a collapsible tree', function () {
    actingAs($this->superAdmin)
        ->get(route('admin.menu.index'))
        ->assertOk()
        ->assertSee('tree-toggle', false)
        ->assertSee('data-tree-parent', false)
        ->assertDontSee('Tambah Menu');
});

test('matrix page still saves grants', function () {
    $roleId = Role::where('name', 'kesra')->firstOrFail()->id;
    $menuIds = DB::table('menus')->pluck('id')->all();

    actingAs($this->superAdmin)
        ->put(route('admin.menu.grants'), ['grants' => [$roleId => $menuIds]])
        ->assertRedirect(route('admin.menu.index'))
        ->assertSessionHas('success');

    expect(DB::table('role_menu')->where('role_id', $roleId)->count())->toBe(count($menuIds));
});

test('sidebarMenus returns only top level menus with children nested', function () {
    $user = $this->admin;
    $topLevel = $user->sidebarMenus();

    $childIds = Menu::query()->whereNotNull('parent_id')->pluck('id');

    foreach ($childIds as $childId) {
        expect($topLevel->pluck('id'))->not->toContain($childId);
    }

    // Role kesra hanya diberi grant `admin.kesra`, jadi dari tiga menu
    // verifikasi di section "Verifikasi" hanya itu yang boleh muncul. Dicocokkan
    // lewat `scope`, bukan `label`: sejak label dipersingkat, "Kampus" dipakai
    // dua menu (antrean verifikasi di section Verifikasi dan master data di
    // section Administrasi) dan tidak bisa dibedakan dari label saja.
    $verifikasiKesra = $topLevel->firstWhere('scope', 'admin.kesra');
    expect($verifikasiKesra)->not->toBeNull()
        ->and($verifikasiKesra->label)->toBe('Kesra')
        ->and($topLevel->firstWhere('scope', 'admin.capil'))->toBeNull()
        ->and($topLevel->firstWhere('scope', 'admin.kampusverif'))->toBeNull()
        // "Verifikasi" bukan menu, melainkan section -- tidak punya `scope`
        // dan tidak boleh ikut terambil sebagai item sidebar.
        ->and($topLevel->firstWhere('label', 'Verifikasi'))->toBeNull();
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
    // Edit sebelumnya.
    expect($html)->toMatch('/id="label-tambah"[^>]*value=""/')
        ->toMatch('/id="route-tambah"[^>]*value=""/');

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
    $menu = Menu::query()->where('label', 'Kesra')->firstOrFail();

    $html = actingAs($this->superAdmin)->get(route('admin.menukelola.index'))->getContent();

    $ubah = Str::betweenFirst($html, 'id="modal-ubah-menu-'.$menu->id.'"', '</form>');

    expect($ubah)
        ->toMatch('/id="label-'.$menu->id.'"[^>]*value="'.preg_quote($menu->label, '/').'"/')
        ->toMatch('/id="route-'.$menu->id.'"[^>]*value="'.preg_quote($menu->route, '/').'"/')
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
