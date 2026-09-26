<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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

    // Role tanpa grant `admin.pengguna` — harus tetap tertutup walau pun modul ini
    // dibuka lewat grant menu (bukan hardcode role).
    $this->capil = User::factory()->capil()->create([
        'email' => 'capil@test.com',
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

test('super admin can create mahasiswa user with NIK and initial password set to 12345678', function () {
    $this->actingAs($this->superAdmin);

    $response = post(route('admin.pengguna.simpan'), [
        'peran' => 'user',
        'status' => 'aktif',
        'nik' => '6302000000000001',
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

    expect(User::count())->toBe(4);
});

test('mahasiswa user cannot be created without NIK', function () {
    $this->actingAs($this->superAdmin);

    post(route('admin.pengguna.simpan'), [
        'peran' => 'user',
        'status' => 'aktif',
    ])->assertSessionHasErrors('nik');

    expect(User::count())->toBe(4);
});

test('super admin validation requires all fields on create', function () {
    $this->actingAs($this->superAdmin);

    $response = post(route('admin.pengguna.simpan'), []);

    $response->assertSessionHasErrors(['username', 'password', 'password_confirmation', 'peran', 'status']);
});

// ─── Validasi mengikuti peran yang dipilih ────────────────────────
//
// Blok `#akunStaf`/`#akunMahasiswa`/`#akunKampus`/[data-field="username"] disembunyikan
// dengan `d-none`, yang sifatnya CSS only: input di dalamnya tetap terkirim. `toggleAkun()`
// sekarang ikut men-set `disabled` supaya browser tidak mengirimnya, dan
// `StoreUserRequest::prepareForValidation()` menutup jalur sisanya. Test di bawah memakai
// payload TANPA key tersebut — persis yang terkirim setelah inputnya dinonaktifkan.

test('mahasiswa is created when the disabled staff block sends no password keys', function () {
    $this->actingAs($this->superAdmin);

    post(route('admin.pengguna.simpan'), [
        'peran' => 'user',
        'status' => 'aktif',
        'nik' => '6302000000000001',
    ])->assertRedirect(route('admin.pengguna.index'))
        ->assertSessionHas('success');

    $newUser = User::whereHas('profile', fn ($q) => $q->where('nik', '6302000000000001'))->first();

    expect($newUser)->not->toBeNull()
        ->and($newUser->hasRole('user'))->toBeTrue()
        ->and($newUser->username)->toStartWith('usr')
        ->and(Hash::check('12345678', $newUser->password))->toBeTrue();
});

test('mahasiswa creation ignores a password left over in the hidden staff block', function () {
    $this->actingAs($this->superAdmin);

    // Sisa input dari peran staf (atau auto-fill browser) tidak boleh ikut divalidasi.
    post(route('admin.pengguna.simpan'), [
        'peran' => 'user',
        'status' => 'aktif',
        'nik' => '6302000000000002',
        'password' => '123',
        'password_confirmation' => 'beda',
    ])->assertRedirect(route('admin.pengguna.index'))
        ->assertSessionHas('success')
        ->assertSessionHasNoErrors();

    $newUser = User::whereHas('profile', fn ($q) => $q->where('nik', '6302000000000002'))->first();

    expect($newUser)->not->toBeNull()
        ->and(Hash::check('12345678', $newUser->password))->toBeTrue();
});

test('staff creation ignores the disabled mahasiswa and kampus blocks', function () {
    $this->actingAs($this->superAdmin);

    // NIK dari blok mahasiswa dan kampus_id dari select yang dinonaktifkan tidak ikut
    // divalidasi — akun staf tidak butuh keduanya.
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Non Target']);

    post(route('admin.pengguna.simpan'), [
        'username' => 'stafnodanik',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'capil',
        'status' => 'aktif',
        'nik' => '6302000000000003',
        'kampus_id' => $kampus->id,
    ])->assertRedirect(route('admin.pengguna.index'));

    $newUser = User::where('username', 'stafnodanik')->first();

    expect($newUser)->not->toBeNull()
        ->and($newUser->hasRole('capil'))->toBeTrue()
        // `store()` tetap mengabaikan kampus_id untuk peran selain `kampus`.
        ->and($newUser->kampus_id)->toBeNull()
        ->and(UserProfile::where('user_id', $newUser->id)->exists())->toBeFalse();
});

test('kampus creation still demands a campus once its select is enabled', function () {
    $this->actingAs($this->superAdmin);

    // Kebalikan dari kasus di atas: saat peran `kampus` select-nya aktif, jadi key
    // `kampus_id` benar-benar terkirim — albeit kosong — dan harus ditolak.
    post(route('admin.pengguna.simpan'), [
        'username' => 'adminkampus',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'kampus',
        'status' => 'aktif',
        'kampus_id' => '',
    ])->assertSessionHasErrors(['kampus_id' => 'Kampus harus dipilih untuk admin kampus']);

    expect(User::where('username', 'adminkampus')->exists())->toBeFalse();
});

test('create and edit forms disable the inputs of the hidden blocks', function () {
    $this->actingAs($this->superAdmin);

    // Guard anti-regresi: `d-none` saja tidak menghentikan pengiriman input, jadi
    // `toggleAkun()` harus tetap menyetel `disabled` pada isi blok yang disembunyikan.
    get(route('admin.pengguna.buat'))
        ->assertOk()
        ->assertSee("prop('disabled'", false)
        ->assertSee('function syncBlock(', false);

    get(route('admin.pengguna.ubah', $this->user))
        ->assertOk()
        ->assertSee("prop('disabled'", false);
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

// ─── Tanpa grant admin.pengguna: Tertutup ────────────────────────

test('role without the admin.pengguna grant cannot view user list', function () {
    $this->actingAs($this->capil);

    get(route('admin.pengguna.index'))->assertForbidden();
});

// ─── Tanpa grant admin.pengguna: Cannot Create ───────────────────

test('role without the admin.pengguna grant cannot access create user form', function () {
    $this->actingAs($this->capil);

    $response = get(route('admin.pengguna.buat'));

    $response->assertForbidden();
});

test('role without the admin.pengguna grant cannot store user', function () {
    $this->actingAs($this->capil);

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

// ─── Tanpa grant admin.pengguna: Cannot Edit ─────────────────────

test('role without the admin.pengguna grant cannot access edit user form', function () {
    $this->actingAs($this->capil);

    $response = get(route('admin.pengguna.ubah', $this->user));

    $response->assertForbidden();
});

test('role without the admin.pengguna grant cannot update user', function () {
    $this->actingAs($this->capil);

    $response = put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => 'kesra',
        'status' => 'non-aktif',
    ]);

    $response->assertForbidden();
});

// ─── Kesra: Cannot Delete ────────────────────────────────────────

test('kesra cannot delete user', function () {
    $this->actingAs($this->admin);

    $response = delete(route('admin.pengguna.hapus', $this->user));

    $response->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $this->user->id]);
});

// ─── Kesra: Toggle Status ────────────────────────────────────────

test('kesra cannot toggle any user status', function () {
    $this->actingAs($this->admin);

    patch(route('admin.pengguna.toggle-status', $this->user))->assertForbidden();
});

test('kesra cannot toggle super admin status', function () {
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

test('kesra cannot reset a user password', function () {
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

// ─── Kesra: Bisa Kelola Pengguna ─────────────────────────────────

test('kesra can view user list with the Tambah Pengguna button', function () {
    $this->actingAs($this->admin);

    get(route('admin.pengguna.index'))
        ->assertOk()
        ->assertSee('Tambah Pengguna')
        ->assertSee(route('admin.pengguna.buat'));
});

test('kesra sees only the Ubah action, not delete or reset password', function () {
    $this->actingAs($this->admin);

    $response = get(route('admin.pengguna.index'));

    $response->assertOk();
    $response->assertSee(route('admin.pengguna.ubah', $this->user));
    $response->assertDontSee('Hapus User');
    $response->assertDontSee('Reset Password');
    $response->assertDontSee('Nonaktifkan');
});

test('kesra can access create user form without the super admin option', function () {
    $this->actingAs($this->admin);

    $response = get(route('admin.pengguna.buat'));

    $response->assertOk();
    $response->assertViewIs('admin.pengguna.buat');
    $response->assertDontSee('Super Admin');
    $response->assertSee('Kesra');
    $response->assertSee('Capil');
    $response->assertSee('Kampus');
});

test('kesra can create a kesra account', function () {
    $this->actingAs($this->admin);

    post(route('admin.pengguna.simpan'), [
        'username' => 'kesrabaru',
        'email' => 'kesrabaru@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'kesra',
        'status' => 'aktif',
    ])->assertRedirect(route('admin.pengguna.index'));

    $newUser = User::where('email', 'kesrabaru@test.com')->first();

    expect($newUser->hasRole('kesra'))->toBeTrue()
        ->and(Hash::check('password123', $newUser->password))->toBeTrue();
});

test('kesra cannot create a super admin account', function () {
    $this->actingAs($this->admin);

    post(route('admin.pengguna.simpan'), [
        'username' => 'superadminbaru',
        'email' => 'superadminbaru@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'super_admin',
        'status' => 'aktif',
    ])->assertSessionHasErrors('peran');

    $this->assertDatabaseMissing('users', ['email' => 'superadminbaru@test.com']);
});

test('kesra can edit a non super admin user', function () {
    $this->actingAs($this->admin);

    get(route('admin.pengguna.ubah', $this->user))
        ->assertOk()
        ->assertViewIs('admin.pengguna.ubah');

    put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => 'capil',
        'status' => 'aktif',
    ])->assertRedirect(route('admin.pengguna.index'));

    expect($this->user->fresh()->hasRole('capil'))->toBeTrue();
});

test('kesra cannot edit a super admin user', function () {
    $this->actingAs($this->admin);

    get(route('admin.pengguna.ubah', $this->superAdmin))->assertForbidden();
});

test('kesra cannot update a super admin user', function () {
    $this->actingAs($this->admin);

    put(route('admin.pengguna.perbarui', $this->superAdmin), [
        'peran' => 'super_admin',
        'status' => 'aktif',
    ])->assertForbidden();

    expect($this->superAdmin->fresh()->status)->toBe('aktif');
});

test('kesra cannot change their own role', function () {
    $this->actingAs($this->admin);

    put(route('admin.pengguna.perbarui', $this->admin), [
        'peran' => 'capil',
        'status' => 'aktif',
    ])->assertRedirect(route('admin.pengguna.index'))->assertSessionHas('error');

    expect($this->admin->fresh()->hasRole('kesra'))->toBeTrue();
});

test('super admin role cannot be demoted through the edit form', function () {
    $anotherSuperAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($this->superAdmin);

    put(route('admin.pengguna.perbarui', $anotherSuperAdmin), [
        'peran' => 'user',
        'status' => 'aktif',
    ])->assertRedirect(route('admin.pengguna.index'))->assertSessionHas('error');

    expect($anotherSuperAdmin->fresh()->hasRole('super_admin'))->toBeTrue();
});

test('super admin cannot be deactivated through the edit form', function () {
    $anotherSuperAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($this->superAdmin);

    put(route('admin.pengguna.perbarui', $anotherSuperAdmin), [
        'peran' => 'super_admin',
        'status' => 'non-aktif',
    ])->assertRedirect(route('admin.pengguna.index'))->assertSessionHas('error');

    expect($anotherSuperAdmin->fresh()->status)->toBe('aktif');
});

test('super admin cannot deactivate their own account through the edit form', function () {
    $this->actingAs($this->superAdmin);

    put(route('admin.pengguna.perbarui', $this->superAdmin), [
        'peran' => 'super_admin',
        'status' => 'non-aktif',
    ])->assertRedirect(route('admin.pengguna.index'))->assertSessionHas('error');

    expect($this->superAdmin->fresh()->status)->toBe('aktif');
});

test('super admin cannot toggle their own status from the list', function () {
    $this->actingAs($this->superAdmin);

    patch(route('admin.pengguna.toggle-status', $this->superAdmin))
        ->assertRedirect(route('admin.pengguna.index'))
        ->assertSessionHas('error');

    expect($this->superAdmin->fresh()->status)->toBe('aktif');
});

// ─── Peran: Whitelist ────────────────────────────────────────────

test('peran is required on edit so the empty placeholder fails', function () {
    $this->actingAs($this->superAdmin);

    put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => '',
        'status' => 'aktif',
    ])->assertSessionHasErrors('peran');
});

test('a role name outside the canonical set is rejected on create', function () {
    Role::create(['name' => 'hacked_role']);

    $this->actingAs($this->superAdmin);

    post(route('admin.pengguna.simpan'), [
        'username' => 'hackrole',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'peran' => 'hacked_role',
        'status' => 'aktif',
    ])->assertSessionHasErrors('peran');

    $this->assertDatabaseMissing('users', ['username' => 'hackrole']);
});

test('a role name outside the canonical set is rejected on edit', function () {
    Role::create(['name' => 'hacked_role']);

    $this->actingAs($this->superAdmin);

    put(route('admin.pengguna.perbarui', $this->user), [
        'peran' => 'hacked_role',
        'status' => 'aktif',
    ])->assertSessionHasErrors('peran');

    expect($this->user->fresh()->roles->pluck('name')->all())->not->toContain('hacked_role');
});

// ─── Mahasiswa: NIM diisi sendiri di Profil ──────────────────────

test('mahasiswa created by an admin has no NIM until they fill it in', function () {
    $this->actingAs($this->superAdmin);

    post(route('admin.pengguna.simpan'), [
        'peran' => 'user',
        'status' => 'aktif',
        'nik' => '6302000000000009',
        'nim' => '2099999999',
    ])->assertRedirect(route('admin.pengguna.index'));

    $profile = UserProfile::where('nik', '6302000000000009')->first();

    expect($profile)->not->toBeNull()
        ->and($profile->nim)->toBeNull();
});
