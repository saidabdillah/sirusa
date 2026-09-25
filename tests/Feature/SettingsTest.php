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
        'password' => Hash::make('password'),
    ]);
});

test('user can access settings page', function () {
    actingAs($this->user)->get(route('settings'))->assertOk()->assertViewIs('settings.index');
});

test('user can change password', function () {
    actingAs($this->user)->put(route('settings.update'), [
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect(route('settings'))
        ->assertSessionHas('success');

    expect(Hash::check('rahasia123', $this->user->fresh()->password))->toBeTrue();
});

test('password must be at least 8 characters', function () {
    actingAs($this->user)->put(route('settings.update'), [
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('short', $this->user->fresh()->password))->toBeFalse();
});

test('password confirmation must match', function () {
    actingAs($this->user)->put(route('settings.update'), [
        'password' => 'rahasia123',
        'password_confirmation' => 'lain123',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('rahasia123', $this->user->fresh()->password))->toBeFalse();
});

test('password field is optional and leaves the current password unchanged', function () {
    actingAs($this->user)->put(route('settings.update'), [])->assertRedirect(route('settings'));

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

test('settings page shows account info without an email change form', function () {
    $response = actingAs($this->user)->get(route('settings'));

    $response->assertOk()
        ->assertSee('NIK')
        ->assertSee('NIM')
        ->assertSee('Ganti Kata Sandi')
        ->assertDontSee('name="email"', false)
        ->assertDontSee(' required>', false);
});
