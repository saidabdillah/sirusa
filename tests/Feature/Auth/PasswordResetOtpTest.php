<?php

use App\Models\User;
use App\Notifications\OtpPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class)->group('auth', 'password-reset');

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    Cache::flush();
    RateLimiter::clear('otp-send:test@example.com');
});

test('user can request OTP for password reset', function () {
    Notification::fake();

    $response = post(route('password.otp.send'), [
        'email' => 'test@example.com',
    ]);

    $response->assertRedirect(route('password.otp.verify', ['email' => 'test@example.com']));
    $response->assertSessionHas('success');

    expect(Cache::has('otp:test@example.com'))->toBeTrue();
    Notification::assertSentTo($this->user, OtpPasswordReset::class);
});

test('user cannot request OTP for non-existent email', function () {
    $response = post(route('password.otp.send'), [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertSessionHasErrors('email');
});

test('rate limiting prevents multiple OTP requests within 1 minute', function () {
    Notification::fake();

    post(route('password.otp.send'), ['email' => 'test@example.com']);

    $response = post(route('password.otp.send'), ['email' => 'test@example.com']);

    $response->assertSessionHasErrors('email');
});

test('user can access OTP verification page with valid email', function () {
    Cache::put('otp:test@example.com', '123456', now()->addMinutes(5));

    $response = get(route('password.otp.verify', ['email' => 'test@example.com']));

    $response->assertOk();
    $response->assertViewIs('auth.verifikasi-otp');
    $response->assertSee('updateHiddenInput');
});

test('user cannot access OTP verification page without valid session', function () {
    $response = get(route('password.otp.verify', ['email' => 'test@example.com']));

    $response->assertRedirect(route('password.request'));
    $response->assertSessionHasErrors('email');
});

test('user can verify correct OTP', function () {
    Cache::put('otp:test@example.com', '123456', now()->addMinutes(5));
    Cache::put('otp:attempts:test@example.com', 0, now()->addMinutes(5));

    $response = post(route('password.otp.check', ['email' => 'test@example.com']), [
        'otp' => '123456',
    ]);

    $response->assertRedirect(route('password.reset.form'));
    $response->assertSessionHas('success');
    expect(session('password_reset_verified'))->toBe('test@example.com');
});

test('user cannot verify incorrect OTP', function () {
    Cache::put('otp:test@example.com', '123456', now()->addMinutes(5));
    Cache::put('otp:attempts:test@example.com', 0, now()->addMinutes(5));

    $response = post(route('password.otp.check', ['email' => 'test@example.com']), [
        'otp' => '654321',
    ]);

    $response->assertSessionHasErrors('otp');
    expect(Cache::get('otp:attempts:test@example.com'))->toBe(1);
});

test('OTP expires after 5 failed attempts', function () {
    Cache::put('otp:test@example.com', '123456', now()->addMinutes(5));
    Cache::put('otp:attempts:test@example.com', 5, now()->addMinutes(5));

    $response = post(route('password.otp.check', ['email' => 'test@example.com']), [
        'otp' => '654321',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect(route('password.request'));
    $response->assertSessionHasErrors('otp');
    expect(Cache::has('otp:test@example.com'))->toBeFalse();
});

test('user can access reset password form after OTP verification', function () {
    $this->withSession(['password_reset_verified' => 'test@example.com']);

    $response = get(route('password.reset.form'));

    $response->assertOk();
    $response->assertViewIs('auth.buat-kata-sandi-baru');
});

test('user cannot access reset password form without OTP verification', function () {
    $response = get(route('password.reset.form'));

    $response->assertRedirect(route('password.request'));
    $response->assertSessionHasErrors('email');
});

test('user can reset password with valid new password', function () {
    $this->withSession(['password_reset_verified' => 'test@example.com']);

    $response = post(route('password.reset.store'), [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('success');

    $this->user->refresh();
    expect($this->user->password)->not->toBe(bcrypt('password123'));
    expect(session('password_reset_verified'))->toBeNull();
});

test('user cannot reset password with mismatched confirmation', function () {
    $this->withSession(['password_reset_verified' => 'test@example.com']);

    $response = post(route('password.reset.store'), [
        'password' => 'newpassword123',
        'password_confirmation' => 'differentpassword',
    ]);

    $response->assertSessionHasErrors('password');
});

test('user cannot reset password with too short password', function () {
    $this->withSession(['password_reset_verified' => 'test@example.com']);

    $response = post(route('password.reset.store'), [
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
});

test('full password reset flow works end to end', function () {
    Notification::fake();

    // Step 1: Request OTP
    post(route('password.otp.send'), ['email' => 'test@example.com']);

    // Step 2: Get OTP from cache (simulating email)
    $otp = Cache::get('otp:test@example.com');
    expect($otp)->not->toBeNull();

    // Step 3: Verify OTP
    post(route('password.otp.check', ['email' => 'test@example.com']), ['otp' => $otp]);

    // Step 4: Reset password
    $this->withSession(['password_reset_verified' => 'test@example.com']);

    $response = post(route('password.reset.store'), [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('success');

    // Verify password changed
    $this->user->refresh();
    expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
});
