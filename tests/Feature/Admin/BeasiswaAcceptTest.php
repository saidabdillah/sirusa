<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('accept', 'beasiswa');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'admin@accept.test']);
    $this->user = User::factory()->standardUser()->create(['email' => 'user@accept.test']);
});

test('admin accepting applicant directly awards the scholarship without announcement step', function () {
    $scholarship = Scholarship::factory()->create(['kuota' => 1]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('diterima');
    expect($scholarship->refresh()->sisaKuota())->toBe(0);
});

test('accepted applicants cannot exceed quota', function () {
    $scholarship = Scholarship::factory()->create(['kuota' => 1]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'diterima',
    ]);
    $second = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $second), ['status' => 'diterima'])
        ->assertSessionHasErrors('status');

    expect($second->refresh()->status)->toBe('verifikasi');
});

test('accepted within remaining quota succeeds', function () {
    $scholarship = Scholarship::factory()->create(['kuota' => 2]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'diterima',
    ]);
    $second = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $second), ['status' => 'diterima'])
        ->assertRedirect();

    expect($second->refresh()->status)->toBe('diterima');
});

test('quota zero allows unlimited accepted applicants', function () {
    $scholarship = Scholarship::factory()->create(['kuota' => 0]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('diterima');
    expect($scholarship->refresh()->sisaKuota())->toBe(0);
});

test('non-admin user cannot update applicant status', function () {
    $applicant = Applicant::factory()->create(['status' => 'verifikasi']);

    actingAs($this->user)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertForbidden();
});

test('admin creating scholarship gets error feedback for invalid required fields', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), [
            'nama' => 'Beasiswa Tanpa Kuota',
            'kampus_id' => $kampus->id,
            'kuota' => '',
            'tingkat_gelar' => 'S1',
            'cakupan' => 'penuh',
            'batas_waktu' => '',
            'ipk_minimal' => '',
            'semester_minimal' => '',
            'deskripsi' => '',
            'persyaratan' => '',
            'status' => 'aktif',
            'prodi_ids' => [$prodi->id],
        ])
        ->assertSessionHasErrors([
            'kuota' => 'Kuota harus diisi',
            'batas_waktu' => 'Batas waktu harus diisi',
            'ipk_minimal' => 'IPK minimal harus diisi',
            'semester_minimal' => 'Semester minimal harus diisi',
            'deskripsi' => 'Deskripsi harus diisi',
            'persyaratan' => 'Persyaratan harus diisi',
        ]);

    $this->assertDatabaseMissing('beasiswa', ['nama' => 'Beasiswa Tanpa Kuota']);
});

test('admin creating scholarship with out-of-range semester gets red error feedback', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), [
            'nama' => 'Beasiswa Semester 0',
            'kampus_id' => $kampus->id,
            'kuota' => 10,
            'tingkat_gelar' => 'S1',
            'cakupan' => 'penuh',
            'batas_waktu' => now()->addMonth()->format('Y-m-d'),
            'ipk_minimal' => 3.0,
            'semester_minimal' => 0,
            'deskripsi' => 'Deskripsi',
            'status' => 'aktif',
            'prodi_ids' => [$prodi->id],
        ])
        ->assertSessionHasErrors(['semester_minimal' => 'Semester minimal harus antara 1 hingga 14']);

    $this->assertDatabaseMissing('beasiswa', ['nama' => 'Beasiswa Semester 0']);
});
