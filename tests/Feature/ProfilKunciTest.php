<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\VerifikasiSemester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil', 'kunci-profil');

beforeEach(function () {
    seedAkses();
    Storage::fake('public');

    $kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $fakultas = $kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $this->prodi = $fakultas->prodi()->create(['nama' => 'Teknik Informatika']);
    $this->user = User::factory()->standardUser()->create(['email' => 'kunci@test.com']);
    $this->profile = buatProfilTerkunci($this->user, $this->prodi->id);
    $this->beasiswa = Scholarship::factory()->create();
});

test('profil tanpa KRS dan KHS tidak pernah disebut lengkap', function () {
    $this->profile->dokumenAkademik()->delete();

    actingAs($this->user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Profil belum lengkap!')
        ->assertDontSee('Profil Anda sudah lengkap. Anda bisa mendaftar beasiswa.');
});

test('pendaftaran menunggu mengunci seluruh form profil', function () {
    buatPendaftaranAktif($this->user, $this->beasiswa, 'verifikasi');

    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->toContain('Profil dikunci selama pendaftaran beasiswa sedang diverifikasi.')
        ->toMatch('/<fieldset[^>]*id="bagian-catpil-data-diri"[^>]*disabled/')
        ->toMatch('/<select[^>]*id="nama_kampus"[^>]*disabled/')
        ->toMatch('/<button[^>]*type="submit"[^>]*disabled[^>]*>Simpan Profil<\/button>/');

    actingAs($this->user)
        ->put(route('profile.update'), ['nama_lengkap' => 'Nama Disusupi'])
        ->assertForbidden();

    expect($this->profile->fresh()->nama_lengkap)->toBe('Mahasiswa Terkunci');
});

test('revisi Catpil hanya membuka data kependudukan dan mempertahankan data Kampus', function () {
    buatPendaftaranAktif($this->user, $this->beasiswa, 'verifikasi');
    $this->profile->update([
        'verif_catpil' => 'revisi',
        'catatan_catpil' => 'Perbaiki nama.',
        'verif_kampus' => 'setuju',
    ]);

    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->toMatch('/<fieldset[^>]*id="bagian-catpil-data-diri"(?![^>]*disabled)[^>]*>/')
        ->toMatch('/<select[^>]*id="nama_kampus"[^>]*disabled/');

    actingAs($this->user)
        ->put(route('profile.update'), catpilPerbaikanPayload($this->profile, [
            'nama_lengkap' => 'Nama Sudah Diperbaiki',
            'ipk' => 1.00,
            'dokumen_pddikti' => pdfKunci('pddikti-disusupi.pdf'),
        ]))
        ->assertSessionDoesntHaveErrors();

    $profile = $this->profile->fresh();

    expect($profile->nama_lengkap)->toBe('Nama Sudah Diperbaiki')
        ->and((float) $profile->ipk)->toBe(3.50)
        ->and($profile->dokumen_pddikti)->toBe('profil/pddikti.pdf')
        ->and($profile->verif_catpil)->toBe('menunggu')
        ->and($profile->catatan_catpil)->toBeNull()
        ->and($profile->verif_kampus)->toBe('setuju');
});

test('revisi Kampus hanya membuka data akademik dan mempertahankan data Catpil', function () {
    buatPendaftaranAktif($this->user, $this->beasiswa, 'verifikasi');
    $this->profile->update([
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'revisi',
        'catatan_kampus' => 'Perbaiki IPK.',
    ]);

    actingAs($this->user)
        ->put(route('profile.update'), kampusPerbaikanPayload($this->profile, $this->prodi->id, [
            'nama_lengkap' => 'Nama Disusupi',
            'ipk' => 3.75,
        ]))
        ->assertSessionDoesntHaveErrors();

    $profile = $this->profile->fresh();

    expect($profile->nama_lengkap)->toBe('Mahasiswa Terkunci')
        ->and((float) $profile->ipk)->toBe(3.75)
        ->and($profile->verif_catpil)->toBe('setuju')
        ->and($profile->verif_kampus)->toBe('menunggu')
        ->and($profile->catatan_kampus)->toBeNull();
});

test('penerima hanya dapat naik satu semester dan mengisi dokumen baru', function () {
    buatPendaftaranAktif($this->user, $this->beasiswa, 'diterima');
    $krsLama = $this->user->profile->dokumenAkademik()->where('jenis', 'krs')->where('semester', 1)->first();
    $pathLama = $krsLama->file;

    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->toContain('data-semester-maksimal="3"')
        ->toMatch('/id="akademik-krs-1-file"[^>]*data-profile-locked="1"[^>]*disabled/')
        ->toMatch('/id="akademik-krs-3-file"[^>]*data-profile-locked="0"/');

    actingAs($this->user)
        ->put(route('profile.update'), [
            'semester' => 3,
            'dokumen_akademik' => [
                'krs' => [
                    1 => ['file' => pdfKunci('krs-lama-diganti.pdf')],
                    3 => ['file' => pdfKunci('krs-3.pdf')],
                ],
                'khs' => [
                    2 => ['file' => pdfKunci('khs-2.pdf'), 'nilai' => 3.80],
                ],
            ],
        ])
        ->assertSessionDoesntHaveErrors();

    $profile = $this->profile->fresh();

    expect($profile->semester)->toBe(3)
        ->and($profile->dokumenAkademik()->where('jenis', 'krs')->where('semester', 1)->value('file'))->toBe($pathLama)
        ->and($profile->dokumenAkademik()->where('jenis', 'krs')->where('semester', 3)->exists())->toBeTrue()
        ->and((float) $profile->dokumenAkademik()->where('jenis', 'khs')->where('semester', 2)->value('nilai'))->toBe(3.80);

    actingAs($this->user)
        ->put(route('profile.update'), ['semester' => 5])
        ->assertSessionHasErrors('semester');
});

test('request manual tidak dapat mengunggah dokumen di atas semester efektif', function () {
    buatPendaftaranAktif($this->user, $this->beasiswa, 'diterima');

    actingAs($this->user)
        ->put(route('profile.update'), [
            'semester' => 3,
            'dokumen_akademik' => [
                'krs' => [
                    3 => ['file' => pdfKunci('krs-3.pdf')],
                    8 => ['file' => pdfKunci('krs-8-disusupi.pdf')],
                ],
                'khs' => [
                    2 => ['file' => pdfKunci('khs-2.pdf'), 'nilai' => 3.80],
                ],
            ],
        ])
        ->assertSessionHasErrors('dokumen_akademik.krs.8');

    expect($this->profile->dokumenAkademik()->where('semester', 8)->exists())->toBeFalse();
});

test('revisi profil Kampus tidak melonggarkan batas kenaikan semester penerima', function () {
    buatPendaftaranAktif($this->user, $this->beasiswa, 'diterima');
    $this->profile->update(['verif_kampus' => 'revisi']);

    actingAs($this->user)
        ->put(route('profile.update'), kampusPerbaikanPayload($this->profile, $this->prodi->id, [
            'semester' => 5,
        ]))
        ->assertSessionHasErrors('semester');

    expect($this->profile->fresh()->semester)->toBe(2);
});

test('revisi paket semester membuka kembali dokumen semester itu saja', function () {
    $applicant = buatPendaftaranAktif($this->user, $this->beasiswa, 'diterima');
    $paket = VerifikasiSemester::create([
        'pendaftar_id' => $applicant->id,
        'semester' => 1,
        'status_kampus' => VerifikasiSemester::KAMPUS_REVISI,
        'catatan_kampus' => 'KRS buram.',
        'status_kesra' => VerifikasiSemester::KESRA_MENUNGGU,
    ]);

    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->toMatch('/id="akademik-krs-1-file"[^>]*data-profile-locked="0"/')
        ->toMatch('/id="akademik-krs-2-file"[^>]*data-profile-locked="1"[^>]*disabled/');

    actingAs($this->user)
        ->put(route('profile.update'), [
            'semester' => 2,
            'dokumen_akademik' => [
                'khs' => [1 => ['nilai' => 3.501]],
            ],
        ])
        ->assertSessionHasErrors('dokumen_akademik.khs.1.nilai');

    expect($paket->refresh()->status_kampus)->toBe(VerifikasiSemester::KAMPUS_REVISI);

    // Nilai lama ikut terkirim oleh browser walau tidak diubah. Itu tidak boleh
    // dianggap sebagai perbaikan yang menutup permintaan revisi.
    actingAs($this->user)
        ->put(route('profile.update'), [
            'semester' => 2,
            'dokumen_akademik' => [
                'khs' => [1 => ['nilai' => 3.5]],
            ],
        ])
        ->assertSessionDoesntHaveErrors();

    $belumDiperbaiki = VerifikasiSemester::query()
        ->where('pendaftar_id', $applicant->id)
        ->where('semester', 1)
        ->firstOrFail();

    expect($belumDiperbaiki->status_kampus)->toBe(VerifikasiSemester::KAMPUS_REVISI)
        ->and($belumDiperbaiki->catatan_kampus)->toBe('KRS buram.');

    actingAs($this->user)
        ->put(route('profile.update'), [
            'semester' => 2,
            'dokumen_akademik' => [
                'krs' => [1 => ['file' => pdfKunci('krs-1-diperbaiki.pdf')]],
            ],
        ])
        ->assertSessionDoesntHaveErrors();

    $paket = VerifikasiSemester::query()->where('pendaftar_id', $applicant->id)->where('semester', 1)->firstOrFail();

    expect($paket->status_kampus)->toBe(VerifikasiSemester::KAMPUS_MENUNGGU)
        ->and($paket->catatan_kampus)->toBeNull()
        ->and($paket->diputuskan_kampus_at)->toBeNull()
        ->and($paket->diputuskan_kampus_oleh)->toBeNull();

    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)->toMatch('/id="akademik-krs-1-file"[^>]*data-profile-locked="1"[^>]*disabled/');
});

function buatProfilTerkunci(User $user, int $prodiId): UserProfile
{
    $profile = UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Mahasiswa Terkunci',
        'nik' => '6302000000000101',
        'no_kk' => '6302000000000102',
        'nim' => '20260001',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $prodiId,
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => false,
        'ipk' => 3.50,
        'semester' => 2,
        'ukt' => 2500000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'Ayah',
        'nik_ayah' => '6302000000000103',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu',
        'nik_ibu' => '6302000000000104',
        'pekerjaan_ibu' => 'Petani',
        'foto_profil' => 'profil/foto.jpg',
        'dokumen_ktp' => 'profil/ktp.pdf',
        'dokumen_kk' => 'profil/kk.pdf',
        'dokumen_desil' => 'profil/desil.pdf',
        'dokumen_pddikti' => 'profil/pddikti.pdf',
        'dokumen_surat_aktif' => 'profil/surat-aktif.pdf',
        'dokumen_surat_pernyataan' => 'profil/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/ukt.pdf',
        'ktp_ayah' => 'profil/ktp-ayah.pdf',
        'ktp_ibu' => 'profil/ktp-ibu.pdf',
    ]);

    simpanAkademikProfilDummy($user, 2);

    return $profile;
}

function buatPendaftaranAktif(User $user, Scholarship $beasiswa, string $status): Applicant
{
    return Applicant::factory()->create([
        'user_id' => $user->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => $status,
        'semester' => 2,
        'ipk' => 3.50,
    ]);
}

function catpilPerbaikanPayload(UserProfile $profile, array $ubah = []): array
{
    return array_merge([
        'nama_lengkap' => $profile->nama_lengkap,
        'email' => $profile->user->email,
        'nik' => $profile->nik,
        'no_kk' => $profile->no_kk,
        'tempat_lahir' => $profile->tempat_lahir,
        'tanggal_lahir' => $profile->tanggal_lahir->format('Y-m-d'),
        'jenis_kelamin' => $profile->jenis_kelamin,
        'agama' => $profile->agama,
        'telepon' => $profile->telepon,
        'desil' => $profile->desil,
        'kecamatan' => $profile->kecamatan,
        'desa_kelurahan' => $profile->desa_kelurahan,
        'alamat' => $profile->alamat,
        'ikut_kk' => $profile->ikut_kk,
        'nama_ayah' => $profile->nama_ayah,
        'nik_ayah' => $profile->nik_ayah,
        'pekerjaan_ayah' => $profile->pekerjaan_ayah,
        'nama_ibu' => $profile->nama_ibu,
        'nik_ibu' => $profile->nik_ibu,
        'pekerjaan_ibu' => $profile->pekerjaan_ibu,
    ], $ubah);
}

function kampusPerbaikanPayload(UserProfile $profile, int $prodiId, array $ubah = []): array
{
    return array_merge([
        'nama_kampus' => $profile->prodi->fakultas->kampus->nama_kampus,
        'fakultas' => $profile->prodi->fakultas->nama,
        'prodi_id' => $prodiId,
        'jenjang' => $profile->jenjang,
        'tahun_masuk' => $profile->tahun_masuk,
        'ipk' => $profile->ipk,
        'semester' => $profile->semester,
        'nim' => $profile->nim,
        'ukt' => $profile->ukt,
    ], $ubah);
}

function pdfKunci(string $nama): UploadedFile
{
    return UploadedFile::fake()->create($nama, 100, 'application/pdf');
}
