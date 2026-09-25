<?php

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        'section' => 'Menu Admin',
        'urutan' => 99,
        'aktif' => 1,
    ], $overrides);
}

test('super admin can open kelola page with modal forms', function () {
    $menu = Menu::where('scope', 'admin.beasiswa')->firstOrFail();

    actingAs($this->superAdmin)
        ->get(route('admin.menuKelola.index'))
        ->assertOk()
        ->assertSee('Form Tambah Menu')
        ->assertSee('modal-tambah-menu', false)
        ->assertSee('modal-ubah-menu-'.$menu->id, false)
        ->assertSee('name="label"', false);
});

test('super admin can create a menu and it is auto granted to super admin', function () {
    $superAdminRoleId = Role::where('name', 'super_admin')->firstOrFail()->id;

    actingAs($this->superAdmin)
        ->post(route('admin.menuKelola.simpan'), menuPayload())
        ->assertRedirect(route('admin.menuKelola.index'))
        ->assertSessionHas('success');

    $menu = Menu::where('label', 'Menu Baru')->firstOrFail();
    expect($menu->section)->toBe('Menu Admin')
        ->and($menu->route)->toBe('admin.beasiswa.index')
        ->and($menu->aktif)->toBeTrue();

    $this->assertDatabaseHas('role_menu', [
        'role_id' => $superAdminRoleId,
        'menu_id' => $menu->id,
    ]);
});

test('menu store rejects unregistered route', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.menuKelola.simpan'), menuPayload(['route' => 'admin.tidak.ada']))
        ->assertSessionHasErrors('route');
});

test('menu store rejects invalid section', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.menuKelola.simpan'), menuPayload(['section' => 'Section Lain']))
        ->assertSessionHasErrors('section');
});

test('menu store requires route or scope', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.menuKelola.simpan'), menuPayload(['route' => null, 'scope' => null]))
        ->assertSessionHasErrors('route');
});

test('super admin can update a menu label and section', function () {
    $menu = Menu::create([
        'label' => 'Lama',
        'icon' => 'fas fa-star',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Menu Admin',
        'urutan' => 5,
        'aktif' => true,
    ]);

    actingAs($this->superAdmin)
        ->put(route('admin.menuKelola.perbarui', $menu), menuPayload([
            'label' => 'Baru',
            'urutan' => 7,
        ]))
        ->assertRedirect(route('admin.menuKelola.index'))
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
        'section' => 'Menu Admin',
        'urutan' => 5,
        'aktif' => true,
    ]);

    actingAs($this->superAdmin)
        ->put(route('admin.menuKelola.perbarui', $menu), menuPayload(['parent_id' => $menu->id]))
        ->assertSessionHasErrors('parent_id');
});

test('super admin cannot delete a wajib menu', function () {
    $wajibMenu = Menu::where('wajib', true)->firstOrFail();

    actingAs($this->superAdmin)
        ->delete(route('admin.menuKelola.hapus', $wajibMenu))
        ->assertRedirect(route('admin.menuKelola.index'))
        ->assertSessionHas('error');

    expect(Menu::where('id', $wajibMenu->id)->exists())->toBeTrue();
});

test('super admin cannot delete a menu that still has children', function () {
    $parent = Menu::where('label', 'Beasiswa')->whereNotNull('children')->firstOrFail();

    actingAs($this->superAdmin)
        ->delete(route('admin.menuKelola.hapus', $parent))
        ->assertRedirect(route('admin.menuKelola.index'))
        ->assertSessionHas('error');

    expect(Menu::where('id', $parent->id)->exists())->toBeTrue();
});

test('super admin can delete a leaf menu', function () {
    $menu = Menu::create([
        'label' => 'Daun',
        'icon' => 'fas fa-star',
        'route' => 'admin.beasiswa.index',
        'scope' => 'admin.beasiswa',
        'section' => 'Menu Admin',
        'urutan' => 90,
        'aktif' => true,
    ]);

    actingAs($this->superAdmin)
        ->delete(route('admin.menuKelola.hapus', $menu))
        ->assertRedirect(route('admin.menuKelola.index'))
        ->assertSessionHas('success');

    expect(Menu::where('id', $menu->id)->exists())->toBeFalse();
});

test('non super admin gets forbidden on all menu management routes', function () {
    $menu = Menu::firstOrFail();

    actingAs($this->admin)->get(route('admin.menu.index'))->assertForbidden();
    actingAs($this->admin)->get(route('admin.menuKelola.index'))->assertForbidden();
    actingAs($this->admin)->post(route('admin.menuKelola.simpan'), menuPayload())->assertForbidden();
    actingAs($this->admin)->put(route('admin.menuKelola.perbarui', $menu), menuPayload())->assertForbidden();
    actingAs($this->admin)->delete(route('admin.menuKelola.hapus', $menu))->assertForbidden();
    actingAs($this->admin)->put(route('admin.menu.grants'), ['grants' => []])->assertForbidden();
});

test('super admin can open kelola menu list page', function () {
    actingAs($this->superAdmin)
        ->get(route('admin.menuKelola.index'))
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

    $verifikasiKesra = $topLevel->firstWhere('label', 'Verifikasi Kesra');
    expect($verifikasiKesra)->not->toBeNull()
        ->and($topLevel->firstWhere('label', 'Verifikasi Capil'))->toBeNull()
        ->and($topLevel->firstWhere('label', 'Verifikasi Kampus'))->toBeNull()
        ->and($topLevel->firstWhere('label', 'Verifikasi'))->toBeNull();
});

test('sidebar renders child menu label only once (no duplicate)', function () {
    $response = actingAs($this->admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Verifikasi Kesra');
    expect(substr_count($response->getContent(), 'Verifikasi Kesra'))->toBe(1);
});
