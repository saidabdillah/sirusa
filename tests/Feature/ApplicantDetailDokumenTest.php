<?php

use App\Models\Applicant;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pendaftar', 'detail', 'dokumen');

function profilDokumenPaths(): array
{
    return [
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        'dokumen_sktm' => 'profil/1/sktm.pdf',
        'dokumen_transkrip' => 'profil/1/transkrip.pdf',
        'dokumen_surat_aktif' => 'profil/1/surat_aktif.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/surat_pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/bukti_ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp_ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp_ibu.pdf',
        'ktp_wali' => 'profil/1/ktp_wali.pdf',
        'kk_wali' => 'profil/1/kk_wali.pdf',
    ];
}

function createProfileWithAllDocuments(User $user): UserProfile
{
    return UserProfile::create(array_merge([
        'user_id' => $user->id,
        'nama_lengkap' => 'Ahmad Detail',
        'nik' => '6302000000000001',
        'dokumen_prestasi' => ['profil/1/prestasi_1.pdf', 'profil/1/prestasi_2.pdf'],
    ], profilDokumenPaths()));
}

beforeEach(function () {
    seedAkses();

    $this->user = User::factory()->standardUser()->create(['email' => 'user@dokumen.test']);

    $this->applicant = Applicant::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'verifikasi',
    ]);

    createProfileWithAllDocuments($this->user);
});

test('user pendaftaran detail shows berkas dari profil', function () {
    $response = actingAs($this->user)
        ->get(route('user.pendaftaran.lihat', $this->applicant))
        ->assertOk();

    $response->assertSee('Dokumen Pendukung', false)
        ->assertSee('Sertifikat Prestasi', false);

    foreach (profilDokumenPaths() as $path) {
        $response->assertSee(route('dokumen.show', $path), false);
    }

    foreach (['profil/1/prestasi_1.pdf', 'profil/1/prestasi_2.pdf'] as $path) {
        $response->assertSee(route('dokumen.show', $path), false);
    }
});
