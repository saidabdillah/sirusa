<?php

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('auth', 'login');

beforeEach(function () {
    seedAkses();

    $this->user = User::factory()->create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'status' => 'aktif',
    ]);
});

test('user can login using email', function () {
    $this->post(route('login.store'), [
        'login' => 'test@example.com',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));
});

test('user can login using username', function () {
    $this->post(route('login.store'), [
        'login' => 'testuser',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));
});

test('user can login using nik', function () {
    UserProfile::create([
        'user_id' => $this->user->id,
        'nik' => '6302000000000001',
        'nama_lengkap' => 'Test User',
    ]);

    $this->post(route('login.store'), [
        'login' => '6302000000000001',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));
});

test('login prefers nik match over identical username', function () {
    $other = User::factory()->create([
        'username' => '6302000000000001',
        'email' => 'other@example.com',
        'password' => bcrypt('otherpass123'),
        'status' => 'aktif',
    ]);
    UserProfile::create([
        'user_id' => $this->user->id,
        'nik' => '6302000000000001',
        'nama_lengkap' => 'Test User',
    ]);

    $this->post(route('login.store'), [
        'login' => '6302000000000001',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);
    expect($other->username)->toBe('6302000000000001');
});

test('login fails with wrong credentials', function () {
    $this->post(route('login.store'), [
        'login' => 'test@example.com',
        'password' => 'wrongpassword',
    ])->assertRedirect()
        ->assertSessionHasErrors('login');
});

test('login fails with non-existent user', function () {
    $this->post(route('login.store'), [
        'login' => 'unknown@example.com',
        'password' => 'password123',
    ])->assertRedirect()
        ->assertSessionHasErrors('login');
});

test('login requires login field', function () {
    $this->post(route('login.store'), [
        'password' => 'password123',
    ])->assertRedirect()
        ->assertSessionHasErrors('login');
});

test('inactive user cannot login', function () {
    $this->user->update(['status' => 'non-aktif']);

    $this->post(route('login.store'), [
        'login' => 'test@example.com',
        'password' => 'password123',
    ])->assertRedirect()
        ->assertSessionHasErrors('login');
});

test('semua peran bisa masuk dengan username', function (string $factory) {
    $staff = User::factory()->{$factory}()->create([
        'username' => 'akun'.$factory,
        'email' => $factory.'@example.com',
        'password' => bcrypt('password123'),
        'status' => 'aktif',
    ]);

    $this->post(route('login.store'), [
        'login' => 'akun'.$factory,
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($staff);
})->with(['catpil', 'kampusAdmin', 'admin', 'superAdmin']);

test('mahasiswa bisa masuk dengan NIK maupun username', function () {
    $mahasiswa = User::factory()->standardUser()->create([
        'username' => 'mhs001',
        'email' => 'mhs@example.com',
        'password' => bcrypt('password123'),
        'status' => 'aktif',
    ]);

    UserProfile::create([
        'user_id' => $mahasiswa->id,
        'nik' => '6302000000000009',
        'nama_lengkap' => 'Ahmad Fauzi',
    ]);

    $this->post(route('login.store'), [
        'login' => '6302000000000009',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($mahasiswa);

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'login' => 'mhs001',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($mahasiswa);
});

test('login page keeps the combined label and drops the hints and bootstrap alerts', function () {
    $this->get(route('login'))
        ->assertOk()
        // Label tetap NIK/username karena dua-duanya cara masuk yang sah.
        ->assertSee('NIK atau Username')
        // Petunjuk per peran ("Mahasiswa dapat memakai NIK atau username. Akun
        // staf memakai username.") sudah dihapus: ia cuma menambah kalimat
        // tanpa mengubah apa pun yang bisa dilakukan pengguna di halaman ini.
        ->assertDontSee('Mahasiswa dapat memakai NIK atau username')
        ->assertDontSee('Akun staf memakai username')
        // Pesan sesi tidak lagi memakai alert bootstrap -- invalid-feedback
        // per field saja yang tersisa.
        ->assertDontSee('alert-dismissible', false)
        // Tidak ada lagi teks "reset kata sandi" di halaman masuk.
        ->assertDontSee('Lupa kata sandi')
        ->assertDontSee('Hubungi admin untuk reset');
});
