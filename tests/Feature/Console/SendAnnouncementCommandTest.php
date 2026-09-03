<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Notifications\PengumumanBeasiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class)->group('announcement', 'console');

beforeEach(function () {
    Notification::fake();

    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas']);
});

test('command notifies all applicants when window is active', function () {
    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    $users = User::factory()->count(3)->standardUser()->create();

    foreach ($users as $user) {
        Applicant::factory()->create([
            'beasiswa_id' => $scholarship->id,
            'user_id' => $user->id,
        ]);
    }

    artisan('announcements:send')->assertSuccessful();

    Notification::assertSentTo($users, PengumumanBeasiswa::class);
    expect($scholarship->refresh()->pengumuman_notified_at)->not->toBeNull();
});

test('command does not notify twice after already notified', function () {
    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
        'pengumuman_notified_at' => now(),
    ]);
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $user->id,
    ]);

    artisan('announcements:send')->assertSuccessful();

    Notification::assertNothingSent();
});

test('command does not notify when outside the window', function () {
    $before = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->addDays(2),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);
    Applicant::factory()->create([
        'beasiswa_id' => $before->id,
        'user_id' => $user->id,
    ]);

    artisan('announcements:send')->assertSuccessful();

    Notification::assertNothingSent();
    expect($before->fresh()->pengumuman_notified_at)->toBeNull();
});

test('command notifies only applicants of active-window scholarships', function () {
    $active = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(3),
    ]);
    $eligible = User::factory()->standardUser()->create(['email' => 'eligible@test.com']);
    Applicant::factory()->create([
        'beasiswa_id' => $active->id,
        'user_id' => $eligible->id,
    ]);

    $later = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->addDays(10),
        'tanggal_pengumuman_selesai' => now()->addDays(13),
    ]);
    $laterUser = User::factory()->standardUser()->create(['email' => 'later@test.com']);
    Applicant::factory()->create([
        'beasiswa_id' => $later->id,
        'user_id' => $laterUser->id,
    ]);

    artisan('announcements:send')->assertSuccessful();

    Notification::assertSentTo($eligible, PengumumanBeasiswa::class);
    Notification::assertNotSentTo($laterUser, PengumumanBeasiswa::class);
    expect($later->fresh()->pengumuman_notified_at)->toBeNull();
});
