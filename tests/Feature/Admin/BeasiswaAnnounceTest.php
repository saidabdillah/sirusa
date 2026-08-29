<?php

use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use App\Notifications\PengumumanBeasiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('announce', 'beasiswa');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'admin@announce.test']);
    $this->user = User::factory()->standardUser()->create(['email' => 'user@announce.test']);
});

test('admin announcing scholarship sets tanggal_pengumuman, tanggal_pengumuman_selesai and notifies all applicants', function () {
    Notification::fake();

    $scholarship = Scholarship::factory()->create();
    $applicantDiterima = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'diterima',
        'hasil_pengumuman' => 'diterima',
    ]);

    // Create another applicant with hasil_pengumuman = tidak_diterima (should ALSO be notified now)
    $user2 = User::factory()->standardUser()->create(['email' => 'user2@announce.test']);
    $applicantTidakDiterima = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $user2->id,
        'status' => 'diterima',
        'hasil_pengumuman' => 'tidak_diterima',
    ]);

    // Create applicant with status verifikasi (should also be notified)
    $user3 = User::factory()->standardUser()->create(['email' => 'user3@announce.test']);
    $applicantVerifikasi = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $user3->id,
        'status' => 'verifikasi',
        'hasil_pengumuman' => null,
    ]);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.umumkan', $scholarship), [
            'tanggal_pengumuman' => now()->toDateString(),
            'tanggal_pengumuman_selesai' => now()->addDays(7)->toDateString(),
        ])
        ->assertRedirect();

    $scholarship->refresh();
    expect($scholarship->tanggal_pengumuman)->not->toBeNull();
    expect($scholarship->tanggal_pengumuman_selesai)->not->toBeNull();

    // All applicants should be notified (diterima, tidak_diterima, verifikasi)
    Notification::assertSentTo($this->user, PengumumanBeasiswa::class);
    Notification::assertSentTo($user2, PengumumanBeasiswa::class);
    Notification::assertSentTo($user3, PengumumanBeasiswa::class);
});

test('admin can choose announcement date and end date', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->admin)
        ->post(route('admin.beasiswa.umumkan', $scholarship), [
            'tanggal_pengumuman' => '2026-09-15',
            'tanggal_pengumuman_selesai' => '2026-09-20',
        ])
        ->assertRedirect();

    expect($scholarship->refresh()->tanggal_pengumuman->toDateString())->toBe('2026-09-15');
    expect($scholarship->tanggal_pengumuman_selesai->toDateString())->toBe('2026-09-20');
});

test('admin can edit announcement date and end date after announced', function () {
    $scholarship = Scholarship::factory()->create([
        'tanggal_pengumuman' => '2026-07-25',
        'tanggal_pengumuman_selesai' => '2026-08-01',
    ]);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.umumkan', $scholarship), [
            'tanggal_pengumuman' => '2026-08-01',
            'tanggal_pengumuman_selesai' => '2026-08-04',
        ])
        ->assertRedirect();

    expect($scholarship->refresh()->tanggal_pengumuman->toDateString())->toBe('2026-08-01');
    expect($scholarship->tanggal_pengumuman_selesai->toDateString())->toBe('2026-08-04');
});

test('applicant with status diterima and hasil_pengumuman=diterima is diumumkan when pengumuman active', function () {
    $scholarship = Scholarship::factory()->create([
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'diterima',
        'hasil_pengumuman' => 'diterima',
    ]);

    expect($applicant->isPengumumanBerlangsung())->toBeTrue();
    expect($applicant->isDiumumkan())->toBeTrue();
});

test('applicant with status diterima but hasil_pengumuman=tidak_diterima is not diumumkan', function () {
    $scholarship = Scholarship::factory()->create([
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'diterima',
        'hasil_pengumuman' => 'tidak_diterima',
    ]);

    expect($applicant->isPengumumanBerlangsung())->toBeTrue();
    expect($applicant->isDiumumkan())->toBeFalse();
});

test('applicant with status verifikasi is not diumumkan even when pengumuman active', function () {
    $scholarship = Scholarship::factory()->create([
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'verifikasi',
        'hasil_pengumuman' => null,
    ]);

    expect($applicant->isPengumumanBerlangsung())->toBeTrue();
    expect($applicant->isDiumumkan())->toBeFalse();
});

test('editing announcement date does not send duplicate notifications', function () {
    Notification::fake();

    $scholarship = Scholarship::factory()->create(['tanggal_pengumuman' => now()->subDay()]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'diterima',
        'hasil_pengumuman' => 'diterima',
    ]);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.umumkan', $scholarship), ['tanggal_pengumuman' => '2026-08-01'])
        ->assertRedirect();

    Notification::assertNothingSent();
});

test('invalid announcement date is rejected', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->admin)
        ->post(route('admin.beasiswa.umumkan', $scholarship), ['tanggal_pengumuman' => 'bukan-tanggal'])
        ->assertSessionHasErrors('tanggal_pengumuman');
});

test('non-admin user cannot announce a scholarship', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->user)
        ->post(route('admin.beasiswa.umumkan', $scholarship))
        ->assertForbidden();
});