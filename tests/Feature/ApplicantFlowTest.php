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
        'dokumen_surat_pernyataan' => UploadedFile::fake()->create('pernyataan.pdf', 10),
        'dokumen_sktm' => UploadedFile::fake()->create('sktm.pdf', 10),
        'dokumen_bukti_ukt' => UploadedFile::fake()->create('ukt.pdf', 10),
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

test('diterima requires hasil_pengumuman', function () {
    $applicant = Applicant::factory()->create(['status' => 'verifikasi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertSessionHasErrors('hasil_pengumuman');

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('diterima can be set from verifikasi with hasil_pengumuman', function () {
    $applicant = Applicant::factory()->create(['status' => 'verifikasi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), [
            'status' => 'diterima',
            'hasil_pengumuman' => 'diterima',
        ])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('diterima');
    expect($applicant->hasil_pengumuman)->toBe('diterima');
});

test('diterima can be set from revisi with hasil_pengumuman', function () {
    $applicant = Applicant::factory()->create(['status' => 'revisi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), [
            'status' => 'diterima',
            'hasil_pengumuman' => 'tidak_diterima',
        ])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('diterima');
    expect($applicant->hasil_pengumuman)->toBe('tidak_diterima');
});

test('diterima cannot be set from verifikasi without hasil_pengumuman', function () {
    $applicant = Applicant::factory()->create(['status' => 'verifikasi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertSessionHasErrors('hasil_pengumuman');

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('rejected applicant is a dead-end and cannot change status', function () {
    $applicant = Applicant::factory()->create(['status' => 'ditolak']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertSessionHasErrors('status');

    expect($applicant->refresh()->status)->toBe('ditolak');
});

test('revisi can be set from verifikasi', function () {
    $applicant = Applicant::factory()->create(['status' => 'verifikasi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'revisi'])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('revisi');
});

test('ditolak can be set from verifikasi', function () {
    $applicant = Applicant::factory()->create(['status' => 'verifikasi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'ditolak'])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('ditolak');
});

test('full flow from submission to diterima with hasil_pengumuman', function () {
    $scholarship = Scholarship::factory()->create(['ipk_minimal' => 3.0, 'status' => 'aktif']);

    // User submits application
    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship, 3.2))
        ->assertRedirect();

    $applicant = Applicant::where('user_id', $this->user->id)->first();
    expect($applicant->status)->toBe('verifikasi');

    // Admin verifies and accepts with hasil_pengumuman
    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), [
            'status' => 'diterima',
            'hasil_pengumuman' => 'diterima',
        ])
        ->assertRedirect();
    expect($applicant->refresh()->status)->toBe('diterima');
    expect($applicant->hasil_pengumuman)->toBe('diterima');

    // Admin announces scholarship
    $scholarship->update([
        'tanggal_pengumuman' => now()->toDateString(),
        'tanggal_pengumuman_selesai' => now()->addDays(7)->toDateString(),
        'durasi_pengumuman' => 7,
    ]);

    expect($scholarship->isPengumumanAktif())->toBeTrue();
    expect($applicant->isPengumumanBerlangsung())->toBeTrue();
});