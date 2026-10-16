<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil');

/**
 * Payload profil lengkap yang LULUS aturan nomor HP Indonesia (08 + 10 digit).
 * Berkas tidak disertakan supaya tes tidak bergantung pada unggahan.
 */
function payloadTeleponProfil(int $prodiId, array $override = []): array
{
    return array_merge([
        'nama_lengkap' => 'Budi',
        'email' => 'user@test.com',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000009999',
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
        'nama_ayah' => 'Ayah Budi',
        'nik_ayah' => '6302000000000002',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Budi',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
    ], $override);
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

// ─── Nomor HP Indonesia ───────────────────────────────────────────────────

test('nomor HP yang sah diterima dan tersimpan', function (string $telepon) {
    actingAs($this->user)
        ->put(route('profile.update'), array_merge(
            payloadTeleponProfil($this->prodi->id, ['telepon' => $telepon]),
            berkasProfilDummy(),
        ))
        ->assertSessionDoesntHaveErrors();

    expect($this->user->fresh()->profile->telepon)->toBe($telepon);
})->with([
    '10 digit' => ['0812345678'],
    '12 digit' => ['081234567890'],
    '13 digit' => ['0812345678901'],
]);

test('nomor HP yang tidak sah ditolak', function (string $telepon) {
    actingAs($this->user)
        ->put(route('profile.update'), payloadTeleponProfil($this->prodi->id, ['telepon' => $telepon]))
        ->assertSessionHasErrors('telepon');
})->with([
    'ada huruf' => ['0812abc7890'],
    'tanpa awalan 08' => ['1234567890'],
    'awalan 07' => ['071234567890'],
    'kurang dari 10 digit' => ['081234567'],
    'lebih dari 13 digit' => ['08123456789012'],
    'pakai tanda hubung' => ['0812-3456-7890'],
    'pakai spasi' => ['0812 3456 7890'],
    'kosong' => [''],
]);

test('pesan galat nomor HP menyebut aturannya', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadTeleponProfil($this->prodi->id, ['telepon' => '123']))
        ->assertSessionHasErrors(['telepon' => 'Nomor HP harus berupa angka, diawali 08, dan panjang 10 sampai 13 digit.']);
});

// ─── Form: bantuan format berkas ──────────────────────────────────────────

test('setiap input berkas punya keterangan format dan ukuran', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    // Semua dokumen PDF; pas foto satu-satunya yang berupa gambar. Keterangan
    // di layar harus mencerminkan aturan server, jadi jumlahnya diperiksa:
    // 13 dokumen PDF (12 di grup + dokumen prestasi) dan 1 pas foto.
    expect(substr_count($html, 'Format: PDF. Ukuran maksimal 2 MB.'))
        ->toBe(12)
        ->and($html)->toContain('Ukuran maksimal 2 MB per file.')
        ->and(substr_count($html, 'Format: JPG, JPEG, atau PNG. Ukuran maksimal 2 MB.'))
        ->toBe(1)
        // Tidak boleh ada lagi sisa teks format lama yang menyebut gambar
        // untuk dokumen.
        ->and($html)->not->toContain('Format: PDF, JPG, JPEG, PNG, atau WEBP.');
});

test('input dokumen memakai accept pdf, pas foto memakai accept gambar', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    // Semua input dokumen: PDF saja.
    preg_match_all('#<input[^>]*type="file"[^>]*>#i', $html, $semua);

    $pdf = 0;
    $gambar = 0;

    foreach ($semua[0] as $tag) {
        if (str_contains($tag, 'accept="application/pdf"')) {
            $pdf++;

            continue;
        }

        if (str_contains($tag, 'accept="image/jpeg,image/png"')) {
            $gambar++;

            continue;
        }

        $this->fail('Ada input berkas dengan accept yang tidak sesuai: '.$tag);
    }

    // 12 dokumen wajib + dokumen prestasi = 13 PDF, dan 1 pas foto.
    expect($pdf)->toBe(13)
        ->and($gambar)->toBe(1);
});

test('input NIK dan nomor HP memakai inputmode angka dan dibatasi panjangnya', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->toMatch('/id="nik"[^>]*maxlength="16"/')
        ->toMatch('/id="no_kk"[^>]*maxlength="16"/')
        ->toMatch('/id="telepon"[^>]*maxlength="13"/')
        ->toContain('Hanya angka, tepat 16 digit.')
        // Teks bantuan nomor HP sengaja dihapus atas permintaan pengguna.
        ->not->toContain('Nomor HP Indonesia: diawali 08')
        // No. KK tidak lagi disyaratkan sama dengan NIK.
        ->not->toContain('harus sama dengan NIK');
});

test('NIK, No. KK, dan nomor HP diinisialisasi dengan Cleave angka murni', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->toContain("new Cleave('#nik'")
        ->toContain("new Cleave('#no_kk'")
        ->toContain("new Cleave('#telepon'")
        ->toContain('blocks: [16]')
        ->toContain('blocks: [13]')
        ->toContain('numericOnly: true');
});

// ─── No. KK: 16 digit angka, tidak harus sama dengan NIK ──────────────────

test('No. KK boleh berbeda dari NIK asal 16 digit angka', function () {
    actingAs($this->user)
        ->put(route('profile.update'), array_merge(
            payloadTeleponProfil($this->prodi->id, [
                'nik' => '6302000000000001',
                'no_kk' => '6302000000008888',
            ]),
            berkasProfilDummy(),
        ))
        ->assertSessionDoesntHaveErrors();

    $profile = $this->user->fresh()->profile;

    expect($profile->no_kk)->toBe('6302000000008888')
        ->and($profile->nik)->toBe('6302000000000001');
});

test('No. KK yang bukan 16 digit ditolak', function (string $noKk) {
    actingAs($this->user)
        ->put(route('profile.update'), array_merge(
            payloadTeleponProfil($this->prodi->id, ['no_kk' => $noKk]),
            berkasProfilDummy(),
        ))
        ->assertSessionHasErrors(['no_kk' => 'Nomor Kartu Keluarga harus terdiri dari 16 digit angka.']);
})->with([
    'kurang digit' => ['123'],
    'lebih digit' => ['63020000000000011'],
    'ada huruf' => ['63020000000000ab'],
    'pakai tanda hubung' => ['6302-0000-0000-0001'],
]);

test('No. KK yang kosong dilaporkan wajib diisi', function () {
    actingAs($this->user)
        ->put(route('profile.update'), array_merge(
            payloadTeleponProfil($this->prodi->id, ['no_kk' => '']),
            berkasProfilDummy(),
        ))
        ->assertSessionHasErrors(['no_kk' => 'Nomor Kartu Keluarga harus diisi.']);
});

// ─── Alert dasbor ─────────────────────────────────────────────────────────

test('dasbor menampilkan alert lengkapi profil saat profil belum lengkap', function () {
    // Profil ada tapi belum lengkap: hanya nama dan NIK.
    UserProfile::create([
        'user_id' => $this->user->id,
        'nama_lengkap' => 'Budi',
        'nik' => '6302000000000001',
    ]);

    actingAs($this->user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Data profil Anda belum lengkap.')
        ->assertSee(route('profile'));
});

test('dasbor tidak menampilkan alert lengkapi profil saat profil sudah lengkap', function () {
    $profile = UserProfile::create([
        'user_id' => $this->user->id,
        'nama_lengkap' => 'Budi',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000009999',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'nama_ayah' => 'Ayah',
        'nik_ayah' => '6302000000000002',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
    ]);

    $profile->update([
        'prodi_id' => $this->prodi->id,
        'ipk' => 3.5,
        'semester' => 4,
        'ukt' => 3500000,
        'desil' => 3,
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        'dokumen_sktm' => 'profil/1/sktm.pdf',
        'dokumen_transkrip' => 'profil/1/transkrip.pdf',
        'dokumen_surat_aktif' => 'profil/1/surat_aktif.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp_ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp_ibu.pdf',
    ]);

    expect($this->user->fresh()->isProfileComplete())->toBeTrue();

    actingAs($this->user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Data profil Anda belum lengkap.');
});

test('akun staf tidak melihat alert lengkapi profil di dasbor', function () {
    $staf = User::factory()->catpil()->create(['email' => 'staf-alert@test.com']);

    actingAs($staf)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Data profil Anda belum lengkap.');
});
