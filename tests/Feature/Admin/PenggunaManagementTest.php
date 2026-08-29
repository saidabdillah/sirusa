<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class)->group('admin', 'pengguna');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->superAdmin = User::factory()->superAdmin()->create([
        'email' => 'superadmin@test.com',
        'password' => bcrypt('password'),
    ]);

    $this->admin = User::factory()->admin()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);

    $this->user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => bcrypt('password'),
    ]);
});

// ─── Super Admin: Index ──────────────────────────────────────────

test('super admin can view user list', function () {
    $this->actingAs($this->superAdmin);

    $response = get(route('admin.pengguna.index'));

    $response->assertOk();
    $response->assertViewIs('admin.pengguna.index');
});

// ─── Super Admin: Create ─────────────────────────────────────────

test('super admin can access create user form', function () {
    $this->actingAs($this->superAdmin);

    $response = get(route('admin.pengguna.buat'));

    $response->assertOk();
    $response->assertViewIs('admin.pengguna.buat');
});

test('super admin can create user with role', function () {
    $this->actingAs($this->superAdmin);

    $response = post(route('admin.pengguna.simpan'), [
        'username' => 'newuser',
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'user',
        'status' => 'aktif',
    ]);

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'username' => 'newuser',
        'email' => 'newuser@test.com',
        'status' => 'aktif',
    ]);

    $newUser = User::where('email', 'newuser@test.com')->first();
    expect($newUser->hasRole('user'))->toBeTrue();
});

test('super admin can create admin user', function () {
    $this->actingAs($this->superAdmin);

    $response = post(route('admin.pengguna.simpan'), [
        'username' => 'newadmin',
        'email' => 'newadmin@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'admin',
        'status' => 'aktif',
    ]);

    $response->assertRedirect(route('admin.pengguna.index'));

    $newUser = User::where('email', 'newadmin@test.com')->first();
    expect($newUser->hasRole('admin'))->toBeTrue();
});

test('super admin validation requires all fields on create', function () {
    $this->actingAs($this->superAdmin);

    $response = post(route('admin.pengguna.simpan'), []);

    $response->assertSessionHasErrors(['username', 'email', 'password', 'peran', 'status']);
});

// ─── Super Admin: Edit ───────────────────────────────────────────

test('super admin can access edit user form', function () {
    $this->actingAs($this->superAdmin);

    $response = get(route('admin.pengguna.ubah', $this->user));

    $response->assertOk();
    $response->assertViewIs('admin.pengguna.ubah');
});

test('super admin can update user role and status', function () {
    $this->actingAs($this->superAdmin);

    $response = put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => 'admin',
        'status' => 'non-aktif',
    ]);

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('success');

    $this->user->refresh();
    expect($this->user->status)->toBe('non-aktif');
    expect($this->user->hasRole('admin'))->toBeTrue();
    expect($this->user->hasRole('user'))->toBeFalse();
});

// ─── Super Admin: Delete ─────────────────────────────────────────

test('super admin can delete regular user', function () {
    $this->actingAs($this->superAdmin);

    $response = delete(route('admin.pengguna.hapus', $this->user));

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
});

test('super admin cannot delete self', function () {
    $this->actingAs($this->superAdmin);

    $response = delete(route('admin.pengguna.hapus', $this->superAdmin));

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('error');

    $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
});

test('super admin cannot delete other super admin', function () {
    $anotherSuperAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($this->superAdmin);

    $response = delete(route('admin.pengguna.hapus', $anotherSuperAdmin));

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('error');

    $this->assertDatabaseHas('users', ['id' => $anotherSuperAdmin->id]);
});

// ─── Super Admin: Toggle Status ──────────────────────────────────

test('super admin can toggle user status', function () {
    $this->actingAs($this->superAdmin);

    $response = patch(route('admin.pengguna.toggle-status', $this->user));

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('success');

    $this->user->refresh();
    expect($this->user->status)->toBe('non-aktif');
});

// ─── Admin: Index ────────────────────────────────────────────────

test('admin can view user list', function () {
    $this->actingAs($this->admin);

    $response = get(route('admin.pengguna.index'));

    $response->assertOk();
    $response->assertViewIs('admin.pengguna.index');
});

// ─── Admin: Cannot Create ────────────────────────────────────────

test('admin cannot access create user form', function () {
    $this->actingAs($this->admin);

    $response = get(route('admin.pengguna.buat'));

    $response->assertForbidden();
});

test('admin cannot store user', function () {
    $this->actingAs($this->admin);

    $response = post(route('admin.pengguna.simpan'), [
        'username' => 'hackuser',
        'email' => 'hack@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'user',
        'status' => 'aktif',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'hack@test.com']);
});

// ─── Admin: Cannot Edit ──────────────────────────────────────────

test('admin cannot access edit user form', function () {
    $this->actingAs($this->admin);

    $response = get(route('admin.pengguna.ubah', $this->user));

    $response->assertForbidden();
});

test('admin cannot update user', function () {
    $this->actingAs($this->admin);

    $response = put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => 'admin',
        'status' => 'non-aktif',
    ]);

    $response->assertForbidden();
});

// ─── Admin: Cannot Delete ────────────────────────────────────────

test('admin cannot delete user', function () {
    $this->actingAs($this->admin);

    $response = delete(route('admin.pengguna.hapus', $this->user));

    $response->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $this->user->id]);
});

// ─── Admin: Toggle Status ────────────────────────────────────────

test('admin can toggle regular user status', function () {
    $this->actingAs($this->admin);

    $response = patch(route('admin.pengguna.toggle-status', $this->user));

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('success');

    $this->user->refresh();
    expect($this->user->status)->toBe('non-aktif');
});

test('admin cannot toggle super admin status', function () {
    $this->actingAs($this->admin);

    $response = patch(route('admin.pengguna.toggle-status', $this->superAdmin));

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('error');

    $this->superAdmin->refresh();
    expect($this->superAdmin->status)->toBe('aktif');
});

// ─── Unauthenticated: Cannot Access ──────────────────────────────

test('unauthenticated user cannot access user management', function () {
    get(route('admin.pengguna.index'))->assertRedirect(route('login'));
    get(route('admin.pengguna.buat'))->assertRedirect(route('login'));
    get(route('admin.pengguna.ubah', $this->user))->assertRedirect(route('login'));
});

// ─── Regular User: Cannot Access ─────────────────────────────────

test('regular user cannot access admin user management', function () {
    $this->actingAs($this->user);

    get(route('admin.pengguna.index'))->assertForbidden();
    get(route('admin.pengguna.buat'))->assertForbidden();
    get(route('admin.pengguna.ubah', $this->user))->assertForbidden();
});
