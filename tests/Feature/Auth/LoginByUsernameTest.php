<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class)->group('auth', 'login');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

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
