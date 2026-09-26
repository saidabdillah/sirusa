<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil');

function payloadProfilLengkap(int $prodiId, array $ubah = []): array
{
    $pdf = fn (string $nama) => UploadedFile::fake()->create($nama, 100, 'application/pdf');

    return array_merge([
        'nama_lengkap' => 'Budi Santoso',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000009999',
        'nim' => '2010123456',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01 RW 02',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'nama_kampus' => 'Universitas Lambung Mangkurat',
        'fakultas' => 'Fakultas Teknik',
        'prodi_id' => $prodiId,
        'ipk' => 3.5,
        'semester' => 4,
        'ukt' => 3500000,
        'desil' => 3,
        'ikut_kk' => 'wali',
        'nama_ayah' => 'Ayah Budi',
        'nik_ayah' => '6302000000000002',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Budi',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
        'nama_wali' => 'Paman Budi',
        'nik_wali' => '6302000000000004',
        'pekerjaan_wali' => 'Wiraswasta',
        'hubungan_wali' => 'Paman',
    ], berkasProfilDummy(), [
        'ktp_wali' => $pdf('ktp-wali.pdf'),
        'kk_wali' => $pdf('kk-wali.pdf'),
    ], $ubah);
}

function profilDenganBerkasLengkap(User $user): UserProfile
{
    return UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Budi Santoso',
        'nik' => '6302000000000001',
        'ikut_kk' => 'wali',
        'kk_ikut_wali' => true,
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        'dokumen_sktm' => 'profil/1/sktm.pdf',
        'dokumen_transkrip' => 'profil/1/transkrip.pdf',
        'dokumen_surat_aktif' => 'profil/1/surat-aktif.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/bukti-ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp-ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp-ibu.pdf',
        'ktp_wali' => 'profil/1/ktp-wali.pdf',
        'kk_wali' => 'profil/1/kk-wali.pdf',
    ]);
}

beforeEach(function () {
    seedAkses();

    Storage::fake('public');

    Http::fake([
        'konoland-api.vercel.app/*' => Http::response([
            'data' => [
                ['code' => '631103', 'regencyCode' => '6311', 'district' => 'Awayan'],
            ],
        ], 200),
    ]);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->fakultas = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $this->prodi = $this->fakultas->prodi()->create(['nama' => 'Teknik Informatika']);
    $this->user = User::factory()->standardUser()->create(['email' => 'user@test.com']);
});

test('payload lengkap tanpa satu field pun kosong lolos validasi', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id))
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors();
});

test('setiap field bertanda bintang di form profil wajib diisi', function (string $field) {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [$field => '']))
        ->assertSessionHasErrors([$field]);

    expect(
        UserProfile::where('user_id', $this->user->id)->exists()
    )->toBeFalse();
})->with([
    // Data Diri
    'nama_lengkap', 'nik', 'no_kk', 'telepon', 'desil', 'tempat_lahir', 'tanggal_lahir',
    'jenis_kelamin', 'agama', 'kecamatan', 'desa_kelurahan', 'alamat',
    // Data Kampus
    'nama_kampus', 'fakultas', 'prodi_id', 'ipk', 'semester', 'ukt',
    // Data Orang Tua & Wali
    'ikut_kk', 'nama_ayah', 'nik_ayah', 'pekerjaan_ayah',
    'nama_ibu', 'nik_ibu', 'pekerjaan_ibu',
    'nama_wali', 'nik_wali', 'pekerjaan_wali', 'hubungan_wali',
    // Dokumen
    'foto_profil', 'dokumen_ktp', 'dokumen_kk', 'dokumen_desil', 'dokumen_sktm',
    'dokumen_transkrip', 'dokumen_surat_aktif', 'dokumen_surat_pernyataan', 'dokumen_bukti_ukt',
    'ktp_ayah', 'ktp_ibu', 'ktp_wali', 'kk_wali',
]);

test('dropdown kampus dan fakultas menolak pilihan kosong', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, ['nama_kampus' => '']))
        ->assertSessionHasErrors(['nama_kampus'])
        ->assertSessionDoesntHaveErrors(['fakultas']);

    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, ['fakultas' => '']))
        ->assertSessionHasErrors(['fakultas'])
        ->assertSessionDoesntHaveErrors(['nama_kampus']);

    // _empty: browser mengirim keduanya kosong karena fakultas mengikuti kampus.
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'nama_kampus' => '',
            'fakultas' => '',
        ]))
        ->assertSessionHasErrors(['nama_kampus', 'fakultas']);
});

test('berkas bertanda bintang wajib diunggah saat profil belum punya dokumen', function () {
    $tanpaBerkas = array_diff_key(payloadProfilLengkap($this->prodi->id), berkasProfilDummy());
    unset($tanpaBerkas['ktp_wali'], $tanpaBerkas['kk_wali']);

    actingAs($this->user)
        ->put(route('profile.update'), $tanpaBerkas)
        ->assertSessionHasErrors([
            'foto_profil', 'dokumen_ktp', 'dokumen_kk', 'dokumen_desil', 'dokumen_sktm',
            'dokumen_transkrip', 'dokumen_surat_aktif', 'dokumen_surat_pernyataan', 'dokumen_bukti_ukt',
            'ktp_ayah', 'ktp_ibu', 'ktp_wali', 'kk_wali',
        ]);
});

test('berkas yang sudah tersimpan tidak wajib diunggah ulang', function () {
    profilDenganBerkasLengkap($this->user);

    $tanpaBerkas = array_diff_key(
        payloadProfilLengkap($this->prodi->id, ['nama_lengkap' => 'Budi Baru']),
        berkasProfilDummy()
    );
    unset($tanpaBerkas['ktp_wali'], $tanpaBerkas['kk_wali']);

    actingAs($this->user)
        ->put(route('profile.update'), $tanpaBerkas)
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors();

    $profile = $this->user->refresh()->profile;

    expect($profile->nama_lengkap)->toBe('Budi Baru')
        ->and($profile->dokumen_ktp)->toBe('profil/1/ktp.pdf')
        ->and($profile->dokumen_surat_aktif)->toBe('profil/1/surat-aktif.pdf')
        ->and($profile->ktp_ayah)->toBe('profil/1/ktp-ayah.pdf')
        ->and($profile->ktp_wali)->toBe('profil/1/ktp-wali.pdf');
});

test('field wali tidak divalidasi saat KK tidak mengikuti wali', function () {
    $payload = payloadProfilLengkap($this->prodi->id, [
        'ikut_kk' => 'ayah',
        'nama_wali' => 'Paman Ngawur',
        'nik_wali' => '123',
        'pekerjaan_wali' => 'Tidak Ada',
        'hubungan_wali' => 'Tidak Ada',
    ]);
    unset($payload['ktp_wali'], $payload['kk_wali']);

    actingAs($this->user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors([
            'nama_wali', 'nik_wali', 'pekerjaan_wali', 'hubungan_wali', 'ktp_wali', 'kk_wali',
        ]);

    $profile = $this->user->refresh()->profile;

    expect($profile->kk_ikut_wali)->toBeFalse()
        ->and($profile->nama_wali)->toBeNull()
        ->and($profile->nik_wali)->toBeNull();
});

test('field wali wajib diisi saat KK mengikuti wali', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'nama_wali' => '',
            'nik_wali' => '',
            'pekerjaan_wali' => '',
            'hubungan_wali' => '',
            'ktp_wali' => '',
            'kk_wali' => '',
        ]))
        ->assertSessionHasErrors([
            'nama_wali', 'nik_wali', 'pekerjaan_wali', 'hubungan_wali', 'ktp_wali', 'kk_wali',
        ]);
});

test('ayah dan ibu wajib diisi tanpa bergantung pada ikut_kk', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'ikut_kk' => 'ibu',
            'nama_ayah' => '',
            'nik_ayah' => '',
            'pekerjaan_ayah' => '',
        ]))
        ->assertSessionHasErrors(['nama_ayah', 'nik_ayah', 'pekerjaan_ayah']);
});

test('dokumen prestasi tetap opsional', function () {
    $payload = payloadProfilLengkap($this->prodi->id);
    unset($payload['dokumen_prestasi']);

    actingAs($this->user)
        ->put(route('profile.update'), $payload)
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors(['dokumen_prestasi']);
});

test('form profil tidak pernah memakai atribut html required', function () {
    actingAs($this->user)
        ->get(route('profile'))
        ->assertOk()
        ->assertDontSee(' required>', false)
        ->assertDontSee(' required />', false);
});

test('setiap field bermasalah menampilkan border merah dan pesan di bawah input', function () {
    // [nama field untuk error bag => [id elemen, pesan yang harus tampil]]
    $kasus = [
        'nama_lengkap' => ['nama_lengkap', 'Nama lengkap harus diisi.'],
        'nama_kampus' => ['nama_kampus', 'Nama kampus harus dipilih.'],
        'fakultas' => ['fakultas', 'Fakultas harus dipilih.'],
        'ukt' => ['ukt', 'UKT harus diisi.'],
        'dokumen_ktp' => ['dokumen_ktp', 'KTP wajib diunggah.'],
        'jenis_kelamin' => ['jenis_kelamin_laki_laki', 'Jenis kelamin harus dipilih.'],
        'ikut_kk' => ['ikut_kk_ayah', 'Ikut KK harus dipilih.'],
    ];

    $response = actingAs($this->user)->get(route('profile'));
    $response->assertOk();

    $html = $response->original
        ->withErrors(array_map(fn ($v) => $v[1], $kasus))
        ->render();

    $gagal = [];

    foreach ($kasus as $field => [$id, $text]) {
        // Pada input radio class="..." diletakkan SEBELUM id, jadi ambil class dari
        // tag pembuka mana pun yang punya id tersebut (lookahead, bukan after).
        preg_match(
            '#<(?:input|select)(?=[^>]*\bid="'.preg_quote($id, '#').'")[^>]*\bclass="([^"]*)"#i',
            $html,
            $tag
        );

        if (! str_contains($tag[1] ?? '', 'is-invalid')) {
            $gagal[] = "{$field}: elemen #{$id} tidak diberi class is-invalid";
        }

        $diBawahInput = preg_match(
            '#\bid="'.preg_quote($id, '#').'".*?invalid-feedback[^>]*>\s*'.preg_quote($text, '#').'#s',
            $html
        );

        if (! $diBawahInput) {
            $gagal[] = "{$field}: pesan \"{$text}\" tidak dirender di bawah input #{$id}";
        }
    }

    expect($gagal)->toBe([]);
});

test('upload berkas wali tetap terlihat setelah validasi gagal', function () {
    $this->withSession(['_old_input' => ['ikut_kk' => 'wali']]);

    $html = actingAs($this->user)->get(route('profile'))->getContent();

    expect($html)->toMatch('/class="form-group wali-doc" style=""/')
        ->and($html)->not->toMatch('/class="form-group wali-doc" style="display:none;"/');

    $this->flushSession();

    $this->withSession(['_old_input' => ['ikut_kk' => 'ayah']]);

    $html = actingAs($this->user)->get(route('profile'))->getContent();

    expect($html)->toMatch('/class="form-group wali-doc" style="display:none;"/');
});
