<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
    $this->assertDatabaseMissing('roles', ['name' => 'admin_catpil']);
    $this->assertDatabaseMissing('roles', ['name' => 'admin_kampus']);
    $this->assertDatabaseMissing('roles', ['name' => 'admin_kesra']);
});

test('database seeder hanya membuat akun super admin', function () {
    // beforeEach sudah membuat dua akun (super admin + admin) lewat factory,
    // jadi hitungannya dibandingkan sebelum-sesudah, bukan angka mutlak.
    $sebelum = User::count();

    $this->seed();

    // Seeder tidak lagi membuat akun staf kesra/kampus/catpil: akun staf
    // dibuat super admin lewat menu Pengguna (akun kampus wajib ditautkan ke
    // kampusnya), jadi seeder hanya menambah SATU akun.
    expect(User::count())->toBe($sebelum + 1);

    $superAdmin = User::where('username', 'superadmin')->firstOrFail();

    expect($superAdmin->email)->toBe('superadmin@email.com')
        ->and($superAdmin->hasRole('super_admin'))->toBeTrue();

    $this->assertDatabaseMissing('users', ['username' => 'kesra']);
    $this->assertDatabaseMissing('users', ['username' => 'kampus']);
    $this->assertDatabaseMissing('users', ['username' => 'catpil']);
});

test('database seeder menghapus akun staf lama tanpa menyentuh akun buatan admin', function () {
    // Database yang sudah di-seed versi lama: superadmin dengan email pribadi
    // plus tiga akun staf bawaan.
    $lama = User::create([
        'username' => 'superadmin',
        'email' => 'msaidabdillah18@gmail.com',
        'password' => '12345678',
        'status' => 'aktif',
    ]);
    $lama->assignRole(Role::firstOrCreate(['name' => 'super_admin']));

    foreach (['kesra', 'kampus', 'catpil'] as $username) {
        $user = User::create([
            'username' => $username,
            'email' => $username.'@sirusa.com',
            'password' => '12345678',
            'status' => 'aktif',
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => $username]));
    }

    // Akun staf yang dibuat SENDIRI oleh admin (username lain) harus selamat.
    $buatanAdmin = User::create([
        'username' => 'kesra2',
        'email' => 'kesra2@sirusa.com',
        'password' => '12345678',
        'status' => 'aktif',
    ]);
    $buatanAdmin->assignRole(Role::where('name', 'kesra')->firstOrFail());

    $this->seed();

    // Email superadmin ikut pindah, kata sandinya tidak direset.
    $superAdmin = User::where('username', 'superadmin')->firstOrFail();

    expect($superAdmin->email)->toBe('superadmin@email.com')
        ->and(Hash::check('12345678', $superAdmin->password))->toBeTrue();

    $this->assertDatabaseMissing('users', ['username' => 'kesra']);
    $this->assertDatabaseMissing('users', ['username' => 'kampus']);
    $this->assertDatabaseMissing('users', ['username' => 'catpil']);
    $this->assertDatabaseHas('users', ['username' => 'kesra2']);

    // Baris role milik akun yang dihapus tidak boleh tertinggal.
    expect(DB::table('model_has_roles')->whereIn('model_id', [2, 3, 4])->exists())->toBeFalse();
});
