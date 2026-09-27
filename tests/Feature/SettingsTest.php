<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('settings');

beforeEach(function () {
    seedAkses();

    $this->user = User::factory()->standardUser()->create([
        'email' => 'user@test.com',
        'password' => Hash::make('sandiLama123'),
    ]);
});

test('user can access settings page', function () {
    actingAs($this->user)->get(route('settings'))->assertOk()->assertViewIs('settings.index');
});

test('user can change password', function () {
    actingAs($this->user)->put(route('settings.update'), [
        'current_password' => 'sandiLama123',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect(route('settings'))
        ->assertSessionHas('success');

    expect(Hash::check('rahasia123', $this->user->fresh()->password))->toBeTrue();
});

test('password must be at least 8 characters', function () {
    actingAs($this->user)->put(route('settings.update'), [
        'current_password' => 'sandiLama123',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('sandiLama123', $this->user->fresh()->password))->toBeTrue();
});

test('password confirmation must match', function () {
    actingAs($this->user)->put(route('settings.update'), [
        'current_password' => 'sandiLama123',
        'password' => 'rahasia123',
        'password_confirmation' => 'lain123',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('sandiLama123', $this->user->fresh()->password))->toBeTrue();
});

// Batch 2: ketiga field wajib. Form kosong dulu "berhasil" Update karena
// `password` berstatus `sometimes|nullable`.
test('empty submission is rejected and leaves the password untouched', function () {
    actingAs($this->user)->put(route('settings.update'), [])
        ->assertSessionHasErrors(['current_password', 'password', 'password_confirmation']);

    expect(Hash::check('sandiLama123', $this->user->fresh()->password))->toBeTrue();
});

test('current password is required', function () {
    $response = actingAs($this->user)->put(route('settings.update'), [
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertSessionHasErrors('current_password');
    expect(Hash::check('sandiLama123', $this->user->fresh()->password))->toBeTrue();
});

test('wrong current password is rejected', function () {
    $response = actingAs($this->user)->put(route('settings.update'), [
        'current_password' => 'passwordsalah',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertSessionHasErrors('current_password');
    expect(Hash::check('sandiLama123', $this->user->fresh()->password))->toBeTrue();
});

test('new password is required', function () {
    $response = actingAs($this->user)->put(route('settings.update'), [
        'current_password' => 'sandiLama123',
        'password' => '',
        'password_confirmation' => '',
    ]);

    $response->assertSessionHasErrors('password');
    expect(Hash::check('sandiLama123', $this->user->fresh()->password))->toBeTrue();
});

test('password confirmation is required', function () {
    $response = actingAs($this->user)->put(route('settings.update'), [
        'current_password' => 'sandiLama123',
        'password' => 'rahasia123',
    ]);

    $response->assertSessionHasErrors('password_confirmation');
    expect(Hash::check('sandiLama123', $this->user->fresh()->password))->toBeTrue();
});

test('mismatch message is attached to the confirmation field', function () {
    $response = actingAs($this->user)->from(route('settings'))->put(route('settings.update'), [
        'current_password' => 'sandiLama123',
        'password' => 'rahasia123',
        'password_confirmation' => 'beda123',
    ]);

    $response->assertSessionHasErrors([
        'password_confirmation' => 'Konfirmasi password tidak cocok.',
    ]);
});

test('empty new password message is the requested wording', function () {
    $response = actingAs($this->user)->from(route('settings'))->put(route('settings.update'), [
        'current_password' => 'sandiLama123',
        'password' => '',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertSessionHasErrors([
        'password' => 'Password baru wajib diisi.',
    ]);
});

test('ajax password change returns success json', function () {
    $response = actingAs($this->user)->put(route('settings.update'), [
        'current_password' => 'sandiLama123',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);

    $response->assertOk()->assertJson(['success' => true]);
    expect(Hash::check('rahasia123', $this->user->fresh()->password))->toBeTrue();
});

test('ajax password change returns 422 with field errors', function () {
    $response = actingAs($this->user)->put(route('settings.update'), [
        'current_password' => 'salah',
        'password' => 'rahasia123',
        'password_confirmation' => 'beda123',
    ], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['current_password', 'password_confirmation']);

    expect(Hash::check('sandiLama123', $this->user->fresh()->password))->toBeTrue();
});

test('settings page shows account info and the three password fields', function () {
    $response = actingAs($this->user)->get(route('settings'));

    $response->assertOk()
        ->assertSee('NIK')
        ->assertSee('NIM')
        ->assertSee('Ganti Kata Sandi')
        ->assertSee('name="current_password"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false)
        ->assertDontSee('name="email"', false);
});

test('settings page never claims the initial password is the NIM', function () {
    $html = actingAs($this->user)->get(route('settings'))->assertOk()->getContent();

    expect($html)->not->toContain('adalah NIM')
        ->and($html)->not->toContain('awal (NIM)')
        ->and($html)->not->toContain('Login menggunakan NIK Anda');
});

test('mahasiswa is told NIK or username, staff only username', function () {
    actingAs($this->user)->get(route('settings'))->assertSee('NIK atau username');

    $capil = User::factory()->capil()->create(['email' => 'capil-setting@test.com']);
    actingAs($capil)->get(route('settings'))->assertSee('username')->assertDontSee('NIK atau username');
});
