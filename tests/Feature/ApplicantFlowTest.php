<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('applicant', 'flow');

function createCompleteProfile(User $user, Prodi $prodi, float $ipk = 3.5, int $semester = 5): UserProfile
{
    return UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01 RW 02',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'status_orang_tua' => 'Wali',
        'nama_wali' => 'Paman',
        'hubungan_wali' => 'Paman',
        'pekerjaan_wali' => 'Petani',
        'penghasilan_wali' => '< 1jt',
        'prodi_id' => $prodi->id,
        'ipk' => $ipk,
        'semester' => $semester,
    ]);
}

function createEligibleScholarship(int $kampusId, array $overrides = []): Scholarship
{
    return Scholarship::factory()->create(array_merge([
        'kampus_id' => $kampusId,
        'ipk_minimal' => 0,
        'semester_minimal' => 0,
    ], $overrides));
}

function applicantPayload(Scholarship $scholarship): array
{
    return [
        'beasiswa_id' => $scholarship->id,
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

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->fakultas = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $this->prodi = $this->fakultas->prodi()->create(['nama' => 'Teknik Informatika']);
});

test('resmi can submit application when ipk meets minimum', function () {
    createCompleteProfile($this->user, $this->prodi, 3.2, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['ipk_minimal' => 3.0]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertRedirect();

    $this->assertDatabaseHas('pendaftar', [
        'user_id' => $this->user->id,
        'beasiswa_id' => $scholarship->id,
        'fakultas' => 'Fakultas Teknik',
        'prodi' => 'Teknik Informatika',
        'ipk' => 3.2,
        'semester' => 5,
        'status' => 'verifikasi',
    ]);
});

test('application rejected when ipk below minimum', function () {
    createCompleteProfile($this->user, $this->prodi, 3.0, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['ipk_minimal' => 3.5]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertSessionHasErrors('ipk');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when semester below minimum', function () {
    createCompleteProfile($this->user, $this->prodi, 3.2, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['semester_minimal' => 6]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertSessionHasErrors('semester');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application accepted when semester meets minimum', function () {
    createCompleteProfile($this->user, $this->prodi, 3.2, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['semester_minimal' => 4]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertRedirect();

    $this->assertDatabaseHas('pendaftar', [
        'user_id' => $this->user->id,
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);
});

test('application rejected when beasiswa has expired', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['batas_waktu' => now()->subDay()]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertSessionHasErrors('beasiswa_id');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when prodi not included in scholarship', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $kampusLain = Kampus::create(['nama_kampus' => 'Universitas Gadjah Mada']);
    $scholarship = createEligibleScholarship($this->kampus->id, ['kampus_id' => $kampusLain->id]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertSessionHasErrors('beasiswa_id');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when already applied', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertRedirect();

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertSessionHasErrors('beasiswa_id');

    $this->assertDatabaseCount('pendaftar', 1);
});

test('application rejected when profile has no prodi', function () {
    UserProfile::create([
        'user_id' => $this->user->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'ipk' => 3.5,
        'semester' => 5,
    ]);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertSessionHasErrors('beasiswa_id');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('diterima can be set from verifikasi', function () {
    $applicant = Applicant::factory()->create(['status' => 'verifikasi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('diterima');
});

test('diterima can be set from revisi', function () {
    $applicant = Applicant::factory()->create(['status' => 'revisi']);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('diterima');
});

test('diterima rejected when quota is full', function () {
    $scholarship = createEligibleScholarship($this->kampus->id, ['kuota' => 1]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'diterima',
    ]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertSessionHasErrors('status');

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('diterima accepted when quota still available', function () {
    $scholarship = createEligibleScholarship($this->kampus->id, ['kuota' => 2]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'diterima',
    ]);
    $applicant = Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('diterima');
});

test('diterima rejected from ditolak (dead-end status)', function () {
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

test('full flow from submission to diterima', function () {
    createCompleteProfile($this->user, $this->prodi, 3.2, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['ipk_minimal' => 3.0]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicantPayload($scholarship))
        ->assertRedirect();

    $applicant = Applicant::where('user_id', $this->user->id)->first();
    expect($applicant->status)->toBe('verifikasi');

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), ['status' => 'diterima'])
        ->assertRedirect();

    expect($applicant->refresh()->status)->toBe('diterima');
});
