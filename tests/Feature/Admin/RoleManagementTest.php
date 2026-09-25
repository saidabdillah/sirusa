<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('role', 'admin');

beforeEach(function () {
    seedAkses();

    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'role-sa@test.com']);
    $this->admin = User::factory()->admin()->create(['email' => 'role-admin@test.com']);
});

test('super admin can access role index', function () {
    actingAs($this->superAdmin)
        ->get(route('admin.role.index'))
        ->assertOk();
});

test('admin cannot access role index', function () {
    actingAs($this->admin)
        ->get(route('admin.role.index'))
        ->assertForbidden();
});

test('super admin cannot access role index when not granted', function () {
    $granted = $this->superAdmin;
    $roleId = $granted->roles->first()->id;
    $menuIds = DB::table('menus')->where('scope', 'admin.role')->pluck('id');
    DB::table('role_menu')->where('role_id', $roleId)->whereIn('menu_id', $menuIds)->delete();

    actingAs($granted->fresh())
        ->get(route('admin.role.index'))
        ->assertForbidden();
});

test('super admin can create a role with wajib menus auto granted', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.role.simpan'), ['name' => 'verifikator'])
        ->assertRedirect(route('admin.role.index'));

    $role = Role::where('name', 'verifikator')->firstOrFail();

    $wajibMenuIds = DB::table('menus')->where('wajib', true)->pluck('id');
    $grantedMenuIds = DB::table('role_menu')->where('role_id', $role->id)->pluck('menu_id');

    expect($grantedMenuIds)->toHaveCount($wajibMenuIds->count());
    foreach ($wajibMenuIds as $menuId) {
        expect($grantedMenuIds)->toContain($menuId);
    }
});

test('role name must be unique', function () {
    actingAs($this->superAdmin)
        ->post(route('admin.role.simpan'), ['name' => 'kesra'])
        ->assertSessionHasErrors('name');
});

test('super admin cannot delete the super_admin role', function () {
    $role = Role::where('name', 'super_admin')->firstOrFail();

    actingAs($this->superAdmin)
        ->delete(route('admin.role.hapus', $role))
        ->assertRedirect(route('admin.role.index'))
        ->assertSessionHas('error');

    expect(Role::where('name', 'super_admin')->exists())->toBeTrue();
});

test('super admin cannot delete a role still in use', function () {
    $role = Role::where('name', 'kesra')->firstOrFail();

    actingAs($this->superAdmin)
        ->delete(route('admin.role.hapus', $role))
        ->assertRedirect(route('admin.role.index'))
        ->assertSessionHas('error');

    expect(Role::where('name', 'kesra')->exists())->toBeTrue();
});

test('role index page renders add and edit modal forms', function () {
    $role = Role::where('name', 'kesra')->firstOrFail();

    actingAs($this->superAdmin)
        ->get(route('admin.role.index'))
        ->assertOk()
        ->assertSee('modal-tambah-role', false)
        ->assertSee('modal-ubah-role-'.$role->id, false)
        ->assertSee('name="name"', false);
});

test('non super admin cannot store or update a role', function () {
    $role = Role::where('name', 'kesra')->firstOrFail();

    actingAs($this->admin)->post(route('admin.role.simpan'), ['name' => 'baru'])->assertForbidden();
    actingAs($this->admin)->put(route('admin.role.perbarui', $role), ['name' => 'baru'])->assertForbidden();
});

test('super admin can update a role name', function () {
    $role = Role::create(['name' => 'verifikator-dua']);

    actingAs($this->superAdmin)
        ->put(route('admin.role.perbarui', $role), ['name' => 'verifikator-baru'])
        ->assertRedirect(route('admin.role.index'))
        ->assertSessionHas('success');

    expect(Role::where('id', $role->id)->firstOrFail()->name)->toBe('verifikator-baru');
});

test('role update rejects a duplicate name', function () {
    $role = Role::create(['name' => 'duplikat-satu']);

    actingAs($this->superAdmin)
        ->put(route('admin.role.perbarui', $role), ['name' => 'kesra'])
        ->assertSessionHasErrors('name');
});

test('role update toggles peran dropdown (bug fix)', function () {
    $role = Role::create(['name' => 'ubahnama-role']);

    actingAs($this->superAdmin)
        ->put(route('admin.role.perbarui', $role), ['name' => 'ubahnama-lain'])
        ->assertRedirect(route('admin.role.index'));

    expect(Role::where('id', $role->id)->firstOrFail()->name)->toBe('ubahnama-lain');
});

test('super admin can delete an unused role', function () {
    $role = Role::create(['name' => 'tidak-terpakai']);

    actingAs($this->superAdmin)
        ->delete(route('admin.role.hapus', $role))
        ->assertRedirect(route('admin.role.index'))
        ->assertSessionHas('success');

    expect(Role::where('name', 'tidak-terpakai')->exists())->toBeFalse();
});

test('database seeder creates exactly the five canonical roles', function () {
    $this->seed();

    $this->assertDatabaseCount('roles', 5);
    $this->assertDatabaseMissing('roles', ['name' => 'admin_capil']);
    $this->assertDatabaseMissing('roles', ['name' => 'admin_kampus']);
    $this->assertDatabaseMissing('roles', ['name' => 'admin_kesra']);

    expect(User::where('username', 'kesra')->firstOrFail()->hasRole('kesra'))->toBeTrue();
    expect(User::where('username', 'kampus')->firstOrFail()->hasRole('kampus'))->toBeTrue();
    expect(User::where('username', 'capil')->firstOrFail()->hasRole('capil'))->toBeTrue();
});
