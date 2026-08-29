<?php

use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class)->group('admin', 'beasiswa');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'sa@test.com']);
    $this->admin = User::factory()->admin()->create(['email' => 'admin@test.com']);
});

// ─── Scholarship: index & detail visible to both ────────────────

test('super admin can view scholarship index and detail', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->superAdmin)->get(route('admin.beasiswa.index'))->assertOk();
    actingAs($this->superAdmin)->get(route('admin.beasiswa.lihat', $scholarship))->assertOk();
});

test('admin can view scholarship index and detail', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->admin)->get(route('admin.beasiswa.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.beasiswa.lihat', $scholarship))->assertOk();
});

// ─── Scholarship: only admin can manage ─────────────────────────

test('admin can access scholarship create form and create one', function () {
    actingAs($this->admin)->get(route('admin.beasiswa.buat'))->assertOk();

    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Prestasi',
        'kampus' => 'Universitas Indonesia',
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'cakupan' => 'penuh',
        'batas_waktu' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'IPK >= 3.0',
        'status' => 'aktif',
        'fakultas' => [
            ['nama' => 'Teknik', 'prodi' => [['nama' => 'Informatika']]],
        ],
    ])->assertRedirect(route('admin.beasiswa.index'));

    $this->assertDatabaseHas('beasiswa', ['nama' => 'Beasiswa Prestasi']);
});

test('super admin cannot access scholarship create form', function () {
    actingAs($this->superAdmin)->get(route('admin.beasiswa.buat'))->assertForbidden();
});

test('super admin cannot create scholarship', function () {
    actingAs($this->superAdmin)->post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Terlarang',
        'kampus' => 'Kampus',
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'cakupan' => 'penuh',
        'batas_waktu' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
        'fakultas' => [
            ['nama' => 'Teknik', 'prodi' => [['nama' => 'Informatika']]],
        ],
    ])->assertForbidden();

    $this->assertDatabaseMissing('beasiswa', ['nama' => 'Beasiswa Terlarang']);
});

test('super admin cannot edit or delete scholarship', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->superAdmin)->get(route('admin.beasiswa.ubah', $scholarship))->assertForbidden();
    actingAs($this->superAdmin)->put(route('admin.beasiswa.perbarui', $scholarship), ['nama' => 'X'])->assertForbidden();
    actingAs($this->superAdmin)->delete(route('admin.beasiswa.hapus', $scholarship))->assertForbidden();

    $this->assertDatabaseHas('beasiswa', ['id' => $scholarship->id]);
});

// ─── Applicant: only admin deletes ──────────────────────────────

test('admin can delete applicant', function () {
    $applicant = Applicant::factory()->create();

    actingAs($this->admin)->delete(route('admin.pendaftar.hapus', $applicant))->assertRedirect();

    $this->assertDatabaseMissing('pendaftar', ['id' => $applicant->id]);
});

test('super admin cannot delete applicant', function () {
    $applicant = Applicant::factory()->create();

    actingAs($this->superAdmin)->delete(route('admin.pendaftar.hapus', $applicant))->assertForbidden();

    $this->assertDatabaseHas('pendaftar', ['id' => $applicant->id]);
});

// ─── Applicant: only admin verifies status ──────────────────────

test('admin updating applicant only changes status and catatan', function () {
    $applicant = Applicant::factory()->create([
        'fakultas' => 'Fakultas Awal',
        'prodi' => 'Prodi Awal',
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)->put(route('admin.pendaftar.perbarui', $applicant), [
        'status' => 'revisi',
        'catatan' => 'Lengkapi IPK',
        'fakultas' => 'Fakultas Baru',
        'prodi' => 'Prodi Baru',
        'ipk' => 3.9,
    ])->assertRedirect();

    $applicant->refresh();
    expect($applicant->status)->toBe('revisi');
    expect($applicant->catatan)->toBe('Lengkapi IPK');
    expect($applicant->fakultas)->toBe('Fakultas Awal');
    expect($applicant->prodi)->toBe('Prodi Awal');
});

test('super admin cannot verify or change applicant status', function () {
    $applicant = Applicant::factory()->create([
        'fakultas' => 'Fakultas Awal',
        'prodi' => 'Prodi Awal',
    ]);

    actingAs($this->superAdmin)->put(route('admin.pendaftar.perbarui', $applicant), [
        'status' => 'diterima',
        'fakultas' => 'Fakultas Baru',
        'prodi' => 'Prodi Baru',
        'ipk' => 3.8,
        'semester' => 6,
    ])->assertForbidden();

    $applicant->refresh();
    expect($applicant->status)->toBe('verifikasi');
    expect($applicant->fakultas)->toBe('Fakultas Awal');
});

// ─── Template: only admin ───────────────────────────────────────

test('admin can access template page', function () {
    actingAs($this->admin)->get(route('admin.template.index'))->assertOk();
});

test('super admin cannot access template page', function () {
    actingAs($this->superAdmin)->get(route('admin.template.index'))->assertForbidden();
});
