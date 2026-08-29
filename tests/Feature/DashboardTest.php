<?php

use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('dashboard', 'admin');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'admin@test.com']);
});

test('admin dashboard shows all applicant status counts', function () {
    Scholarship::factory()->create(['status' => 'aktif']);
    Scholarship::factory()->create(['status' => 'non-aktif']);

    Applicant::factory()->create(['status' => 'verifikasi']);
    Applicant::factory()->create(['status' => 'diterima', 'hasil_pengumuman' => 'diterima']);
    Applicant::factory()->create(['status' => 'revisi']);
    Applicant::factory()->create(['status' => 'ditolak']);
    Applicant::factory()->create(['status' => 'diterima', 'hasil_pengumuman' => 'tidak_diterima']);

    actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Total Pendaftar')
        ->assertSee('Verifikasi')
        ->assertSee('Diterima')
        ->assertSee('Revisi')
        ->assertSee('Ditolak')
        ->assertSee('Total Beasiswa');
});

test('super admin dashboard shows all applicant status counts', function () {
    $superAdmin = User::factory()->superAdmin()->create(['email' => 'sa@test.com']);

    Applicant::factory()->create(['status' => 'diterima', 'hasil_pengumuman' => 'diterima']);

    actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Total Pendaftar')
        ->assertSee('Diterima');
});

test('user dashboard counts diterima (all diterima status) as accepted', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    Applicant::factory()->create(['user_id' => $user->id, 'status' => 'diterima', 'hasil_pengumuman' => 'diterima']);
    Applicant::factory()->create(['user_id' => $user->id, 'status' => 'diterima', 'hasil_pengumuman' => 'tidak_diterima']);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('totalApplications', 2)
        ->assertViewHas('acceptedApplications', 2) // All diterima status
        ->assertViewHas('pendingApplications', 0)
        ->assertViewHas('rejectedApplications', 0) // Only ditolak status
        ->assertSee('Total Pendaftaran')
        ->assertSee('Verifikasi')
        ->assertSee('Diterima')
        ->assertSee('Ditolak');
});

test('user accepted count does not include rejected or pending', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user2@test.com']);

    Applicant::factory()->create(['user_id' => $user->id, 'status' => 'verifikasi']);
    Applicant::factory()->create(['user_id' => $user->id, 'status' => 'ditolak']);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('totalApplications', 2)
        ->assertViewHas('acceptedApplications', 0)
        ->assertViewHas('pendingApplications', 1)
        ->assertViewHas('rejectedApplications', 1)
        ->assertSee('Total Pendaftaran')
        ->assertSee('Verifikasi')
        ->assertSee('Ditolak');
});