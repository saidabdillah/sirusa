<?php

use App\Exports\PendaftarExport;
use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('export', 'excel', 'pendaftar');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'export-admin@test.com']);
    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'export-sa@test.com']);
    $this->user = User::factory()->standardUser()->create(['email' => 'export-user@test.com']);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->scholarship = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'kampus' => $this->kampus->nama_kampus,
    ]);

    Applicant::factory()->create([
        'user_id' => $this->user->id,
        'beasiswa_id' => $this->scholarship->id,
        'status' => 'diterima',
    ]);
});

test('admin can export pendaftar as excel', function () {
    actingAs($this->admin)
        ->get(route('admin.pendaftar.export'))
        ->assertStatus(200)
        ->assertHeader('content-disposition', 'attachment; filename=daftar-pendaftar.xlsx');
});

test('super admin can export pendaftar as excel', function () {
    actingAs($this->superAdmin)
        ->get(route('admin.pendaftar.export'))
        ->assertStatus(200)
        ->assertHeader('content-disposition', 'attachment; filename=daftar-pendaftar.xlsx');
});

test('regular user cannot export pendaftar as excel', function () {
    actingAs($this->user)
        ->get(route('admin.pendaftar.export'))
        ->assertForbidden();
});

test('export excel respects status filter', function () {
    $response = actingAs($this->admin)
        ->get(route('admin.pendaftar.export', ['status' => 'diterima']));

    $response->assertStatus(200)
        ->assertHeader('content-disposition', 'attachment; filename=daftar-pendaftar.xlsx');
});

test('export excel respects beasiswa_id filter', function () {
    $response = actingAs($this->admin)
        ->get(route('admin.pendaftar.export', ['beasiswa_id' => $this->scholarship->id]));

    $response->assertStatus(200)
        ->assertHeader('content-disposition', 'attachment; filename=daftar-pendaftar.xlsx');
});

test('export excel with invalid beasiswa_id rejects', function () {
    actingAs($this->admin)
        ->get(route('admin.pendaftar.export', ['beasiswa_id' => 99999]))
        ->assertInvalid('beasiswa_id');
});

test('export headings include identity, parent, and guardian columns', function () {
    $headings = (new PendaftarExport(request()))->headings();

    expect($headings)->toContain('Tempat Lahir', 'Tanggal Lahir', 'Alamat')
        ->and($headings)->toContain('Status Orang Tua')
        ->and($headings)->toContain('Nama Ayah', 'Pekerjaan Ayah', 'Penghasilan Ayah', 'NIK Ayah')
        ->and($headings)->toContain('Nama Ibu', 'Pekerjaan Ibu', 'Penghasilan Ibu', 'NIK Ibu')
        ->and($headings)->toContain('Nama Wali', 'Hubungan Wali', 'Pekerjaan Wali', 'NIK Wali');
});

test('export map returns full profile, parent, and guardian data', function () {
    UserProfile::create([
        'user_id' => $this->user->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'status_orang_tua' => 'Lengkap',
        'nama_ayah' => 'Budi Ayah',
        'status_ayah' => 'Hidup',
        'pekerjaan_ayah' => 'Petani',
        'penghasilan_ayah' => '1-3jt',
        'nik_ayah' => 'nIkAyah123',
        'nama_ibu' => 'Siti Ibu',
        'status_ibu' => 'Hidup',
        'pekerjaan_ibu' => 'Wiraswasta',
        'penghasilan_ibu' => '< 1jt',
        'nik_ibu' => 'nikibu456',
        'nama_wali' => 'Paman',
        'hubungan_wali' => 'Paman',
        'pekerjaan_wali' => 'Petani',
        'penghasilan_wali' => '< 1jt',
        'nik_wali' => 'nikwali789',
    ]);

    $applicant = $this->user->applicants()->first();
    $row = (new PendaftarExport(request()))->map($applicant);

    expect($row)->toContain('Balangan', '01 Jan 2000', 'Laki-laki', 'Islam', 'Lengkap')
        ->and($row)->toContain('Budi Ayah', 'Hidup', 'Petani', '1-3jt', 'nIkAyah123')
        ->and($row)->toContain('Siti Ibu', 'Wiraswasta', '< 1jt', 'nikibu456')
        ->and($row)->toContain('Paman', 'nikwali789');
});
