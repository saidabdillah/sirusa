<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pengumuman', 'show');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);
    $this->viewer = User::factory()->standardUser()->create(['email' => 'viewer@test.com']);
});

test('pengumuman page shows accepted applicants during active window', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);
    $kampus = Kampus::create(['nama_kampus' => 'Universitas']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);
    UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Ahmad Penerima',
        'prodi_id' => $prodi->id,
    ]);

    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $user->id,
        'status' => 'diterima',
    ]);

    actingAs($user)
        ->get(route('pengumuman.show', $scholarship))
        ->assertOk()
        ->assertSee('Ahmad Penerima')
        ->assertSee('Informatika');
});

test('pengumuman page returns 404 before the start date', function () {
    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->addDays(2),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'diterima']);

    actingAs($this->viewer)->get(route('pengumuman.show', $scholarship))->assertNotFound();
});

test('pengumuman page returns 404 after the end date', function () {
    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDays(5),
        'tanggal_pengumuman_selesai' => now()->subDay(),
    ]);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'diterima']);

    actingAs($this->viewer)->get(route('pengumuman.show', $scholarship))->assertNotFound();
});

test('pengumuman page returns 404 when no penerima exists', function () {
    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'verifikasi']);

    actingAs($this->viewer)->get(route('pengumuman.show', $scholarship))->assertNotFound();
});

test('pengumuman page returns 404 when window dates not set', function () {
    $scholarship = Scholarship::factory()->create(['status' => 'aktif']);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'diterima']);

    actingAs($this->viewer)->get(route('pengumuman.show', $scholarship))->assertNotFound();
});
