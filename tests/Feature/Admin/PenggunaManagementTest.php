<?php

use App\Models\Kampus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class)->group('admin', 'pengguna');

beforeEach(function () {
    seedAkses();

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
    $response->assertSee('Ubah');
    $response->assertDontSee('Ubah Role');
    $response->assertSee('Reset Password');
    $response->assertSee('Hapus User');
});

test('user list shows admin and super_admin users', function () {
    $this->actingAs($this->superAdmin);

    $response = get(route('admin.pengguna.index'));

    $response->assertOk();
    $response->assertSee('superadmin@test.com');
    $response->assertSee('admin@test.com');
    $response->assertSee('Super Admin');
    $response->assertSee('Kesra');
});

test('user list buttons use delegated confirm handlers instead of inline onclick', function () {
    $this->actingAs($this->superAdmin);

    $response = get(route('admin.pengguna.index'));

    $response->assertOk();
    $response->assertSee('btn-confirm-toggle', false);
    $response->assertSee('btn-delete', false);
    $response->assertDontSee('confirmToggleStatus(', false);
    $response->assertDontSee('confirmDelete(', false);
});

// ─── Super Admin: Create ─────────────────────────────────────────

test('super admin can access create user form', function () {
    $this->actingAs($this->superAdmin);

    $response = get(route('admin.pengguna.buat'));

    $response->assertOk();
    $response->assertViewIs('admin.pengguna.buat');
    $response->assertDontSee(' required>', false);
});

test('super admin can access edit user form without html5 required attribute', function () {
    $this->actingAs($this->superAdmin);

    get(route('admin.pengguna.ubah', $this->admin))->assertOk()->assertDontSee(' required>', false);
});

test('super admin can create mahasiswa user with NIK and NIM and initial password set to 12345678', function () {
    $this->actingAs($this->superAdmin);

    $response = post(route('admin.pengguna.simpan'), [
        'peran' => 'user',
        'status' => 'aktif',
        'nik' => '6302000000000001',
        'nim' => '2010123456',
    ]);

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('success');

    $newUser = User::whereHas('profile', fn ($q) => $q->where('nik', '6302000000000001'))->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->hasRole('user'))->toBeTrue()
        ->and($newUser->status)->toBe('aktif')
        ->and($newUser->username)->toStartWith('usr')
        ->and(Hash::check('12345678', $newUser->password))->toBeTrue();
});

test('super admin can create admin user', function () {
    $this->actingAs($this->superAdmin);

    $response = post(route('admin.pengguna.simpan'), [
        'username' => 'newadmin',
        'email' => 'newadmin@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'kesra',
        'status' => 'aktif',
    ]);

    $response->assertRedirect(route('admin.pengguna.index'));

    $newUser = User::where('email', 'newadmin@test.com')->first();
    expect($newUser->hasRole('kesra'))->toBeTrue();
});

test('kampus admin user requires a kampus selection on create', function () {
    $this->actingAs($this->superAdmin);

    post(route('admin.pengguna.simpan'), [
        'username' => 'adminkampus',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'kampus',
        'status' => 'aktif',
    ])->assertSessionHasErrors('kampus_id');

    expect(User::where('username', 'adminkampus')->exists())->toBeFalse();

    $kampus = Kampus::create(['nama_kampus' => 'Universitas Kampus Test']);

    post(route('admin.pengguna.simpan'), [
        'username' => 'adminkampus',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'kampus',
        'status' => 'aktif',
        'kampus_id' => $kampus->id,
    ])->assertRedirect(route('admin.pengguna.index'));

    $newUser = User::where('username', 'adminkampus')->first();
    expect($newUser->hasRole('kampus'))->toBeTrue()
        ->and($newUser->kampus_id)->toBe($kampus->id);
});

test('kampus admin user requires a kampus selection on edit', function () {
    $this->actingAs($this->superAdmin);

    put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => 'kampus',
        'status' => 'aktif',
    ])->assertSessionHasErrors('kampus_id');

    $kampus = Kampus::create(['nama_kampus' => 'Universitas Kampus Edit']);

    put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => 'kampus',
        'status' => 'aktif',
        'kampus_id' => $kampus->id,
    ])->assertRedirect(route('admin.pengguna.index'));

    $this->user->refresh();
    expect($this->user->kampus_id)->toBe($kampus->id)
        ->and($this->user->hasRole('kampus'))->toBeTrue();
});

test('mahasiswa user does not require NIK unless peran is user', function () {
    $this->actingAs($this->superAdmin);

    // Peran kesra tidak butuh NIK.
    post(route('admin.pengguna.simpan'), [
        'username' => 'kesratanpanik',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'kesra',
        'status' => 'aktif',
    ])->assertRedirect(route('admin.pengguna.index'));

    expect(User::where('username', 'kesratanpanik')->exists())->toBeTrue();
});

test('admin user cannot be created without a username', function () {
    $this->actingAs($this->superAdmin);

    post(route('admin.pengguna.simpan'), [
        'peran' => 'kesra',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'status' => 'aktif',
    ])->assertSessionHasErrors('username');

    expect(User::count())->toBe(3);
});

test('mahasiswa user cannot be created without NIK', function () {
    $this->actingAs($this->superAdmin);

    post(route('admin.pengguna.simpan'), [
        'peran' => 'user',
        'status' => 'aktif',
    ])->assertSessionHasErrors('nik');

    expect(User::count())->toBe(3);
});

test('super admin validation requires all fields on create', function () {
    $this->actingAs($this->superAdmin);

    $response = post(route('admin.pengguna.simpan'), []);

    $response->assertSessionHasErrors(['username', 'password', 'password_confirmation', 'peran', 'status']);
});

// ─── Super Admin: Edit ───────────────────────────────────────────

test('super admin can access edit user form', function () {
    $this->actingAs($this->superAdmin);

    $response = get(route('admin.pengguna.ubah', $this->user));

    $response->assertOk();
    $response->assertViewIs('admin.pengguna.ubah');
    $response->assertDontSee('Reset Kata Sandi');
    $response->assertDontSee('Konfirmasi Kata Sandi');
    $response->assertDontSee('name="password"', false);
    $response->assertDontSee('name="email"', false);
    $response->assertDontSee('name="nik"', false);
    $response->assertDontSee('name="nim"', false);
});

test('super admin can update user role and status', function () {
    $this->actingAs($this->superAdmin);

    $response = put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => 'kesra',
        'status' => 'non-aktif',
    ]);

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('success');

    $this->user->refresh();
    expect($this->user->status)->toBe('non-aktif');
    expect($this->user->hasRole('kesra'))->toBeTrue();
    expect($this->user->hasRole('user'))->toBeFalse();
});

test('NIK and NIM cannot be changed through the edit form', function () {
    $this->actingAs($this->superAdmin);

    $profile = $this->user->profile()->create([
        'nik' => '6302000000000001',
        'nim' => '2010123456',
    ]);

    put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => 'user',
        'status' => 'aktif',
        'nik' => '9999999999999999',
        'nim' => '2099999999',
    ])->assertRedirect(route('admin.pengguna.index'));

    $profile->refresh();
    expect($profile->nik)->toBe('6302000000000001')
        ->and($profile->nim)->toBe('2010123456');
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

test('admin cannot view user list', function () {
    $this->actingAs($this->admin);

    get(route('admin.pengguna.index'))->assertForbidden();
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
        'peran' => 'kesra',
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

test('admin cannot toggle any user status', function () {
    $this->actingAs($this->admin);

    patch(route('admin.pengguna.toggle-status', $this->user))->assertForbidden();
});

test('admin cannot toggle super admin status', function () {
    $this->actingAs($this->admin);

    patch(route('admin.pengguna.toggle-status', $this->superAdmin))->assertForbidden();
});

// ─── Super Admin: Reset Password ─────────────────────────────────

test('super admin can reset a user password to 12345678', function () {
    $this->actingAs($this->superAdmin);

    $response = post(route('admin.pengguna.reset-password', $this->user));

    $response->assertRedirect(route('admin.pengguna.index'));
    $response->assertSessionHas('success');

    expect(Hash::check('12345678', $this->user->fresh()->password))->toBeTrue();
});

test('admin cannot reset a user password', function () {
    $this->actingAs($this->admin);

    post(route('admin.pengguna.reset-password', $this->user))->assertForbidden();
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
