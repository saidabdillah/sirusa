<?php

use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('applicant', 'flow');

function applicantPayload(Scholarship $scholarship, float $ipk = 3.5): array
{
    return [
        'beasiswa_id' => $scholarship->id,
        'fakultas' => 'Teknik',
        'prodi' => 'Informatika',
        'ipk' => $ipk,
        'semester' => 5,
        'dokumen_ktp' => UploadedFile::fake()->create('ktp.pdf', 10),
        'dokumen_kk' => UploadedFile::fake()->create('kk.pdf', 10),
        'dokumen_surat_permohonan' => UploadedFile::fake()->create('permohonan.pdf', 10),
        'dokumen_transkrip' => UploadedFile::fake()->create('transkrip.pdf', 10),
        'dokumen_surat_aktif' => UploadedFile::fake()->create('aktif.pdf', 10),
        'dokumen_pas_foto' => UploadedFile::fake()->image('foto.jpg'),
    ];
}

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'admin@test.com']);
    $this->user = User::factory()->standardUser()->create(['email' => 'user@test.com']);
});

test('resmi can submit application when ipk meets minimum', function () {
    $scholarship = Scholarship::factory()->create(['ipk_minimal' => 3.0, 'status' => 'aktif']);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship, 3.2))
        ->assertRedirect();

    $this->assertDatabaseHas('pendaftar', [
        'user_id' => $this->user->id,
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);
});

test('application rejected when ipk below minimum', function () {
    $scholarship = Scholarship::factory()->create(['ipk_minimal' => 3.5, 'status' => 'aktif']);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship, 3.0))
        ->assertSessionHasErrors('ipk');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when semester below minimum', function () {
    $scholarship = Scholarship::factory()->create(['semester_minimal' => 6, 'status' => 'aktif']);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship, 3.2))
        ->assertSessionHasErrors('semester');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application accepted when semester meets minimum', function () {
    $scholarship = Scholarship::factory()->create(['semester_minimal' => 4, 'status' => 'aktif']);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship, 3.2))
        ->assertRedirect();

    $this->assertDatabaseHas('pendaftar', [
        'user_id' => $this->user->id,
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);
});

test('application rejected when beasiswa has expired', function () {
    $scholarship = Scholarship::factory()->create([
        'batas_waktu' => now()->subDay(),
        'ipk_minimal' => 0,
        'status' => 'aktif',
    ]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship, 3.5))
        ->assertSessionHasErrors('beasiswa_id');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('verifikasi_akhir only allowed from diterima and requires tahap 2 documents', function () {
    $applicant = Applicant::factory()->create(['status' => 'diterima']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'verifikasi_akhir'])
        ->assertSessionHasErrors('status');

    $applicant->update([
        'dokumen_surat_pernyataan' => 's.pd.f',
        'dokumen_sktm' => 's.pd.f',
        'dokumen_bukti_ukt' => 's.pd.f',
    ]);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'verifikasi_akhir'])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('verifikasi_akhir');
});

test('verifikasi_akhir cannot be set from verifikasi', function () {
    $applicant = Applicant::factory()->create(['status' => 'verifikasi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'verifikasi_akhir'])
        ->assertSessionHasErrors('status');

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('selesai only allowed from verifikasi_akhir and requires tahap 2 documents', function () {
    $applicant = Applicant::factory()->create(['status' => 'verifikasi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'selesai'])
        ->assertSessionHasErrors('status');

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('rejected applicant is a dead-end and cannot change status', function () {
    $applicant = Applicant::factory()->create(['status' => 'ditolak']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertSessionHasErrors('status');

    expect($applicant->refresh()->status)->toBe('ditolak');
});

test('marking selesai sets final status', function () {
    $applicant = Applicant::factory()->create([
        'status' => 'verifikasi_akhir',
        'dokumen_surat_pernyataan' => 's.pd.f',
        'dokumen_sktm' => 's.pd.f',
        'dokumen_bukti_ukt' => 's.pd.f',
    ]);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'selesai'])
        ->assertRedirect();

    $applicant->refresh();
    expect($applicant->status)->toBe('selesai');
});

test('full flow from submission to finalization', function () {
    $scholarship = Scholarship::factory()->create(['ipk_minimal' => 3.0, 'status' => 'aktif']);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship, 3.2))
        ->assertRedirect();

    $applicant = Applicant::where('user_id', $this->user->id)->first();
    expect($applicant->status)->toBe('verifikasi');

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertRedirect();
    expect($applicant->refresh()->status)->toBe('diterima');

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan-melengkapi', $applicant), [
            'dokumen_surat_pernyataan' => UploadedFile::fake()->create('pernyataan.pdf', 10),
            'dokumen_sktm' => UploadedFile::fake()->create('sktm.pdf', 10),
            'dokumen_bukti_ukt' => UploadedFile::fake()->create('ukt.pdf', 10),
        ])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('verifikasi_akhir');

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'selesai'])
        ->assertRedirect();

    $applicant->refresh();
    expect($applicant->status)->toBe('selesai');
});
