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
        'nim' => '2010123456',
        'jenis_kelamin' => 'Laki-laki',
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
        ->assertSee('Informatika')
        ->assertSee('2010123456')
        ->assertSee('Laki-laki')
        ->assertSee('Universitas')
        ->assertSee('target="_blank"', false)
        ->assertSee(route('pengumuman.export-pdf', $scholarship), false);
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

test('pengumuman page returns 404 for user who is not an applicant', function () {
    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'diterima']);

    actingAs($this->viewer)
        ->get(route('pengumuman.show', $scholarship))
        ->assertNotFound();
});

test('pengumuman page visible to applicant with non-accepted status', function () {
    $user = User::factory()->standardUser()->create(['email' => 'verifikasi@test.com']);
    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $user->id,
        'status' => 'verifikasi',
    ]);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'diterima']);

    actingAs($user)
        ->get(route('pengumuman.show', $scholarship))
        ->assertOk()
        ->assertSee('id="penerimaTable"', false)
        ->assertSee("$('#penerimaTable').DataTable({", false)
        ->assertSee('sk-table')
        ->assertSee('--cols: 7', false);
});

test('pengumuman page visible to admin who is not an applicant', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@test.com']);
    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'diterima']);

    actingAs($admin)
        ->get(route('pengumuman.show', $scholarship))
        ->assertOk()
        ->assertSee(route('pengumuman.export-pdf', $scholarship), false);
});

test('admin sidebar shows active announcement link on dashboard', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin-sidebar@test.com']);
    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'tanggal_pengumuman' => now()->subDay(),
        'tanggal_pengumuman_selesai' => now()->addDays(5),
    ]);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'diterima']);

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('pengumuman.show', $scholarship), false);
});

test('admin sidebar does not show announcement link when none active', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin-sidebar-none@test.com']);
    $scholarship = Scholarship::factory()->create(['status' => 'aktif']);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'diterima']);

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('pengumuman.show', $scholarship), false);
});
