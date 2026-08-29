<?php

use App\Models\User;
use App\Notifications\VerifyEmailChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('settings');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->user = User::factory()->standardUser()->create([
        'email' => 'user@test.com',
        'password' => Hash::make('password'),
    ]);
});

test('user can access settings page', function () {
    actingAs($this->user)->get(route('settings'))->assertOk()->assertViewIs('settings.index');
});

test('user can change email', function () {
    actingAs($this->user)->put(route('settings.update'), [
        'email' => 'userbaru@gmail.com',
    ])->assertRedirect(route('settings'));

    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'email' => 'userbaru@gmail.com']);
});

test('email duplicate is rejected', function () {
    User::factory()->create(['email' => 'ambil@gmail.com']);

    actingAs($this->user)->put(route('settings.update'), [
        'email' => 'ambil@gmail.com',
    ])->assertSessionHasErrors('email');

    $this->assertDatabaseHas('users', ['id' => $this->user->id, 'email' => 'user@test.com']);
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

test('user can change password', function () {
    actingAs($this->user)->put(route('settings.update'), [
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect(route('settings'));

    expect(Hash::check('rahasia123', $this->user->fresh()->password))->toBeTrue();
});

test('OTP for email change is sent to the new email', function () {
    Notification::fake();

    actingAs($this->user)->post(route('settings.email.otp.send'), [
        'email' => 'emailbaru@gmail.com',
    ])->assertRedirect(route('settings.email.verify'));

    Notification::assertSentOnDemand(VerifyEmailChange::class, function ($notification, $channels, $notifiable) {
        return $notifiable->routes['mail'] === 'emailbaru@gmail.com';
    });
});
