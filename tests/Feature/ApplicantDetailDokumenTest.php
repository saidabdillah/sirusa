<?php

use App\Models\Applicant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pendaftar', 'detail', 'dokumen');

function dokumenPaths(): array
{
    return [
        'dokumen_ktp' => 'pendaftaran/1/1/ktp.pdf',
        'dokumen_kk' => 'pendaftaran/1/1/kk.pdf',
        'dokumen_akta' => 'pendaftaran/1/1/akta.pdf',
        'dokumen_surat_permohonan' => 'pendaftaran/1/1/surat_permohonan.pdf',
        'dokumen_transkrip' => 'pendaftaran/1/1/transkrip.pdf',
        'dokumen_surat_aktif' => 'pendaftaran/1/1/surat_aktif.pdf',
        'dokumen_pas_foto' => 'pendaftaran/1/1/pas_foto.jpg',
        'dokumen_surat_pernyataan' => 'pendaftaran/1/1/surat_pernyataan.pdf',
        'dokumen_sktm' => 'pendaftaran/1/1/sktm.pdf',
        'dokumen_bukti_ukt' => 'pendaftaran/1/1/bukti_ukt.pdf',
        'ktp_ayah' => 'pendaftaran/1/1/ktp_ayah.pdf',
        'ktp_ibu' => 'pendaftaran/1/1/ktp_ibu.pdf',
        'ktp_wali' => 'pendaftaran/1/1/ktp_wali.pdf',
        'kk_wali' => 'pendaftaran/1/1/kk_wali.pdf',
        'dokumen_prestasi' => ['pendaftaran/1/1/prestasi_1.pdf', 'pendaftaran/1/1/prestasi_2.pdf'],
    ];
}

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'admin@dokumen.test']);
    $this->user = User::factory()->standardUser()->create(['email' => 'user@dokumen.test']);

    $this->applicant = Applicant::factory()->create(array_merge([
        'user_id' => $this->user->id,
        'status' => 'verifikasi',
    ], dokumenPaths()));
});

test('user pendaftaran detail shows semua berkas sesuai yang diupload', function () {
    $response = actingAs($this->user)
        ->get(route('user.pendaftaran.lihat', $this->applicant))
        ->assertOk();

    $response->assertSee('Dokumen Diri Sendiri', false)
        ->assertSee('Dokumen untuk Kampus', false)
        ->assertSee('Dokumen Orang Tua / Wali', false)
        ->assertSee('KTP Ayah', false)
        ->assertSee('KTP Ibu', false)
        ->assertSee('KTP Wali', false)
        ->assertSee('Kartu Keluarga Wali', false)
        ->assertSee('Sertifikat Prestasi', false);

    foreach (dokumenPaths() as $key => $path) {
        if ($key === 'dokumen_prestasi') {
            foreach ($path as $prestasiPath) {
                $response->assertSee(route('dokumen.show', $prestasiPath), false);
            }

            continue;
        }

        $response->assertSee(route('dokumen.show', $path), false);
    }
});

test('admin pendaftar detail shows berkas sesuai yang diupload user', function () {
    $response = actingAs($this->admin)
        ->get(route('admin.pendaftar.lihat', $this->applicant))
        ->assertOk();

    $response->assertSee('Dokumen Diri Sendiri', false)
        ->assertSee('Dokumen untuk Kampus', false)
        ->assertSee('Dokumen Orang Tua / Wali', false)
        ->assertSee('KTP Ayah', false)
        ->assertSee('KTP Ibu', false)
        ->assertSee('KTP Wali', false)
        ->assertSee('Kartu Keluarga Wali', false)
        ->assertSee('Sertifikat Prestasi', false);

    foreach (dokumenPaths() as $key => $path) {
        if ($key === 'dokumen_prestasi') {
            foreach ($path as $prestasiPath) {
                $response->assertSee(route('dokumen.show', $prestasiPath), false);
            }

            continue;
        }

        $response->assertSee(route('dokumen.show', $path), false);
    }
});
