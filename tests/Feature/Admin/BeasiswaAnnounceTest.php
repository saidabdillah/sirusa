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

test('admin announcing scholarship sets tanggal_pengumuman and notifies all selesai applicants', function () {
    Notification::fake();

    $scholarship = Scholarship::factory()->create();
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'selesai',
    ]);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.umumkan', $scholarship))
        ->assertRedirect();

    expect($scholarship->refresh()->tanggal_pengumuman)->not->toBeNull();
    expect($applicant->refresh()->isDiumumkan())->toBeTrue();
    Notification::assertSentTo($this->user, PengumumanBeasiswa::class);
});

test('applicant becoming selesai after announcement is automatically diumumkan', function () {
    $scholarship = Scholarship::factory()->create(['tanggal_pengumuman' => now()->subDay()]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'selesai',
    ]);

    expect($applicant->isDiumumkan())->toBeTrue();
});

test('admin can mark announced scholarship as paid and notifies applicants', function () {
    Notification::fake();

    $scholarship = Scholarship::factory()->create(['tanggal_pengumuman' => now()->subDay()]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'selesai',
    ]);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.bayarkan', $scholarship))
        ->assertRedirect();

    expect($scholarship->refresh()->tanggal_pembayaran)->not->toBeNull();
    expect($applicant->refresh()->isDibayarkan())->toBeTrue();
    Notification::assertSentTo($this->user, PengumumanBeasiswa::class);
});

test('admin cannot pay for a scholarship that has not been announced', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->admin)
        ->post(route('admin.beasiswa.bayarkan', $scholarship))
        ->assertRedirect();

    expect($scholarship->refresh()->tanggal_pembayaran)->toBeNull();
});

test('admin can choose announcement date', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->admin)
        ->post(route('admin.beasiswa.umumkan', $scholarship), ['tanggal_pengumuman' => '2026-09-15'])
        ->assertRedirect();

    expect($scholarship->refresh()->tanggal_pengumuman->toDateString())->toBe('2026-09-15');
});

test('admin can choose payment date', function () {
    $scholarship = Scholarship::factory()->create(['tanggal_pengumuman' => now()->subDay()]);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.bayarkan', $scholarship), ['tanggal_pembayaran' => '2026-09-20'])
        ->assertRedirect();

    expect($scholarship->refresh()->tanggal_pembayaran->toDateString())->toBe('2026-09-20');
});

test('admin can edit announcement date after announced', function () {
    $scholarship = Scholarship::factory()->create(['tanggal_pengumuman' => now()->subDay()]);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.umumkan', $scholarship), ['tanggal_pengumuman' => '2026-08-01'])
        ->assertRedirect();

    expect($scholarship->refresh()->tanggal_pengumuman->toDateString())->toBe('2026-08-01');
});

test('admin can edit payment date after paid', function () {
    $scholarship = Scholarship::factory()->create([
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pembayaran' => now()->subDay(),
    ]);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.bayarkan', $scholarship), ['tanggal_pembayaran' => '2026-08-05'])
        ->assertRedirect();

    expect($scholarship->refresh()->tanggal_pembayaran->toDateString())->toBe('2026-08-05');
});

test('editing announcement date does not send duplicate notifications', function () {
    Notification::fake();

    $scholarship = Scholarship::factory()->create(['tanggal_pengumuman' => now()->subDay()]);

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

test('non-admin user cannot announce or pay a scholarship', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->user)
        ->post(route('admin.beasiswa.umumkan', $scholarship))
        ->assertForbidden();

    actingAs($this->user)
        ->post(route('admin.beasiswa.bayarkan', $scholarship))
        ->assertForbidden();
});
