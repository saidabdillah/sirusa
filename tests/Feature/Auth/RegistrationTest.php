<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\post;

uses(RefreshDatabase::class)->group('auth', 'registration');

beforeEach(function () {
    Role::create(['name' => 'user']);
});

test('newly registered user defaults to non-aktif status', function () {
    $response = post(route('register.store'), [
        'email' => 'pendaftar.sirusa@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree' => '1',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('success');

    $user = User::where('email', 'pendaftar.sirusa@gmail.com')->first();

    expect($user)->not->toBeNull();
    expect($user->status)->toBe('non-aktif');
    expect($user->hasRole('user'))->toBeTrue();
    expect($user->username)->not->toBeNull();
});

test('newly registered user cannot login before activation', function () {
    post(route('register.store'), [
        'email' => 'pendaftar.sirusa@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree' => '1',
    ]);

    $response = post(route('login.store'), [
        'login' => 'pendaftar.sirusa@gmail.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('login');
    expect(auth()->check())->toBeFalse();
});
