<?php

use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('applicant', 'flow');

function createCompleteProfile(User $user, Prodi $prodi, float $ipk = 3.5, int $semester = 5, bool $verified = true): UserProfile
{
    return UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000000002',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01 RW 02',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $prodi->id,
        'ipk' => $ipk,
        'semester' => $semester,
        'ukt' => 2500000,
        'desil' => 3,
        'nama_ayah' => 'Ayah Ahmad',
        'nik_ayah' => '6302000000000003',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Ahmad',
        'nik_ibu' => '6302000000000004',
        'pekerjaan_ibu' => 'Petani',
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        'dokumen_sktm' => 'profil/1/sktm.pdf',
        'dokumen_transkrip' => 'profil/1/transkrip.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp_ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp_ibu.pdf',
        'verif_capil' => $verified ? 'setuju' : 'menunggu',
        'verif_kampus' => $verified ? 'setuju' : 'menunggu',
        'verif_kesra' => $verified ? 'setuju' : 'menunggu',
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

function applicationPayload(Scholarship $scholarship): array
{
    return ['beasiswa_id' => $scholarship->id];
}

beforeEach(function () {
    seedAkses();

    $this->user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->fakultas = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $this->prodi = $this->fakultas->prodi()->create(['nama' => 'Teknik Informatika']);
});

test('user can submit application when profile complete and verified', function () {
    createCompleteProfile($this->user, $this->prodi, 3.2, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['ipk_minimal' => 3.0]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('user.pendaftaran.index'));

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
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('user.beasiswa.lihat', $scholarship))
        ->assertSessionHas('error', 'IPK minimal untuk beasiswa ini adalah 3.5.');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when semester below minimum', function () {
    createCompleteProfile($this->user, $this->prodi, 3.2, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['semester_minimal' => 6]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('user.beasiswa.lihat', $scholarship))
        ->assertSessionHas('error', 'Semester minimal untuk beasiswa ini adalah 6.');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application accepted when semester meets minimum', function () {
    createCompleteProfile($this->user, $this->prodi, 3.2, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['semester_minimal' => 4]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('user.pendaftaran.index'));

    $this->assertDatabaseHas('pendaftar', [
        'user_id' => $this->user->id,
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);
});

test('application rejected when beasiswa has expired', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $scholarship = createEligibleScholarship($this->kampus->id, ['tanggal_selesai' => now()->subDay()]);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('user.beasiswa.lihat', $scholarship))
        ->assertSessionHas('error', 'Pendaftaran beasiswa sudah ditutup.');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when prodi not included in scholarship', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $kampusLain = Kampus::create(['nama_kampus' => 'Universitas Gadjah Mada']);
    $scholarship = createEligibleScholarship($kampusLain->id);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('user.beasiswa.lihat', $scholarship))
        ->assertSessionHas('error', 'Program Studi Anda tidak termasuk dalam beasiswa ini.');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when already applied', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('user.pendaftaran.index'));

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('user.beasiswa.lihat', $scholarship))
        ->assertSessionHas('error', 'Anda sudah mendaftar beasiswa ini.');

    $this->assertDatabaseCount('pendaftar', 1);
});

test('application rejected when profile has no prodi', function () {
    UserProfile::create([
        'user_id' => $this->user->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'verif_capil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('profile'))
        ->assertSessionHas('error');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when profile incomplete', function () {
    UserProfile::create([
        'user_id' => $this->user->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'prodi_id' => $this->prodi->id,
    ]);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('profile'))
        ->assertSessionHas('error', 'Profil belum lengkap. Silakan lengkapi profil terlebih dahulu.');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when profile not yet verified', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5, verified: false);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('profile'))
        ->assertSessionHas('error', 'Profil belum terverifikasi. Silakan tunggu verifikasi dari pihak kami terlebih dahulu.');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application rejected when wali data incomplete and kk ikut wali', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $this->user->profile->update(['kk_ikut_wali' => true]);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('profile'))
        ->assertSessionHas('error');

    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $scholarship->id]);
});

test('application accepted when kk ikut wali and wali data complete', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $this->user->profile->update([
        'kk_ikut_wali' => true,
        'nama_wali' => 'Wali Ahmad',
        'nik_wali' => '6302000000000005',
        'pekerjaan_wali' => 'Swasta',
        'hubungan_wali' => 'Paman',
        'ktp_wali' => 'profil/1/ktp_wali.pdf',
        'kk_wali' => 'profil/1/kk_wali.pdf',
    ]);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship))
        ->assertRedirect(route('user.pendaftaran.index'));

    $this->assertDatabaseHas('pendaftar', [
        'user_id' => $this->user->id,
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);
});

// ─── Confirmation page (user.pendaftaran.buat) ──────────────────

test('confirmation page shows profile summary without upload fields', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->get(route('user.pendaftaran.buat', ['beasiswa_id' => $scholarship->id]))
        ->assertOk()
        ->assertSee('Konfirmasi Pendaftaran', false)
        ->assertSee($scholarship->nama, false)
        ->assertSee('Fakultas Teknik', false)
        ->assertSee('Teknik Informatika', false)
        ->assertSee('Kirim Pendaftaran')
        ->assertSee(route('user.pendaftaran.simpan'), false)
        ->assertDontSee('dokumen_akta', false)
        ->assertDontSee('Upload', false);
});

test('confirmation page redirects to profile when profile unverified', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5, verified: false);
    $scholarship = createEligibleScholarship($this->kampus->id);

    actingAs($this->user)
        ->get(route('user.pendaftaran.buat', ['beasiswa_id' => $scholarship->id]))
        ->assertRedirect(route('profile'))
        ->assertSessionHas('error');
});

test('confirmation page redirects when application already submitted', function () {
    createCompleteProfile($this->user, $this->prodi, 3.5, 5);
    $scholarship = createEligibleScholarship($this->kampus->id);
    actingAs($this->user)->post(route('user.pendaftaran.simpan'), applicationPayload($scholarship));

    actingAs($this->user)
        ->get(route('user.pendaftaran.buat', ['beasiswa_id' => $scholarship->id]))
        ->assertRedirect(route('user.beasiswa.lihat', $scholarship))
        ->assertSessionHas('error', 'Anda sudah mendaftar beasiswa ini.');
});
