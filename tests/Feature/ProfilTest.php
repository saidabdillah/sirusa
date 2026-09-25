<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil');

function completeProfilePayload($prodi): array
{
    return [
        'nama_lengkap' => 'Budi',
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
        'prodi_id' => $prodi->id,
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
    ];
}

beforeEach(function () {
    seedAkses();

    Http::fake([
        'konoland-api.vercel.app/*' => Http::response([
            'data' => [
                ['code' => '631103', 'regencyCode' => '6311', 'district' => 'Awayan'],
                ['code' => '631101', 'regencyCode' => '6311', 'district' => 'Juai'],
            ],
        ], 200),
    ]);

    $this->kampus = Kampus::create([
        'nama_kampus' => 'Universitas Lambung Mangkurat',
    ]);
    $this->fakultas = $this->kampus->fakultas()->create([
        'nama' => 'Fakultas Teknik',
    ]);
    $this->prodi = $this->fakultas->prodi()->create([
        'nama' => 'Teknik Informatika',
    ]);
    $this->prodi2 = $this->fakultas->prodi()->create([
        'nama' => 'Teknik Elektro',
    ]);
});

test('user can open profil form with region and campus fields', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Kecamatan')
        ->assertSee('Desa/Kelurahan')
        ->assertSee('Kalimantan Selatan')
        ->assertSee('Balangan')
        ->assertSee('Awayan')
        ->assertSee('Juai')
        ->assertSee('Data Kampus')
        ->assertSee('Universitas Lambung Mangkurat')
        ->assertSee('IPK')
        ->assertSee('Semester')
        ->assertSee('name="nim"', false)
        ->assertSeeInOrder(['Data Kampus', 'name="nim"'], false)
        ->assertSee('sk-form-row')
        ->assertSee('Anda harus melengkapi data dan dokumen berikut sebelum bisa mendaftar beasiswa.')
        ->assertDontSee('<li>Status Orang Tua</li>', false)
        ->assertDontSee(' required>', false);
});

test('user can update profile with campus data and parent nik', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = [
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000009999',
        'nim' => '2010998877',
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
        'prodi_id' => $this->prodi->id,
        'ipk' => 3.75,
        'semester' => 5,
        'ukt' => 3500000,
        'desil' => 3,
        'nama_ayah' => 'Ayah Fauzi',
        'nik_ayah' => '6302000000000002',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Fauzi',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
    ];

    actingAs($user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('profil_pengguna', [
        'user_id' => $user->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'nim' => '2010998877',
        'no_kk' => '6302000000009999',
        'nik_ayah' => '6302000000000002',
        'nik_ibu' => '6302000000000003',
        'provinsi' => 'Kalimantan Selatan',
        'kabupaten_kota' => 'Balangan',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'alamat' => 'RT 01 RW 02',
        'prodi_id' => $this->prodi->id,
        'ipk' => 3.75,
        'semester' => 5,
    ]);
});

test('profil hides wali section and drops wali data when kk_ikut_wali is unchecked', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = array_merge(completeProfilePayload($this->prodi), [
        'kk_ikut_wali' => 0,
        'nama_wali' => 'Paman Wawan',
        'nik_wali' => '6302000000000004',
        'hubungan_wali' => 'Paman',
        'pekerjaan_wali' => 'Petani',
    ]);

    actingAs($user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'));

    $this->assertDatabaseHas('profil_pengguna', [
        'user_id' => $user->id,
        'kk_ikut_wali' => false,
        'nama_wali' => null,
        'nik_wali' => null,
    ]);

    $response = actingAs($user->fresh())->get(route('profile'));
    $response->assertOk();

    expect($response->getContent())->toMatch('/id="section-wali"\s+style="display:none;/');
});

test('profil shows wali section and saves wali data when kk_ikut_wali is checked', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = array_merge(completeProfilePayload($this->prodi), [
        'kk_ikut_wali' => 1,
        'nama_wali' => 'Paman Wawan',
        'nik_wali' => '6302000000000004',
        'hubungan_wali' => 'Paman',
        'pekerjaan_wali' => 'Petani',
    ]);

    actingAs($user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'));

    $response = actingAs($user->fresh())->get(route('profile'));
    $response->assertOk();
    $response->assertSee('id="section-wali"', false);
    $response->assertSee('Nama Wali');
});

test('profil wali toggle js follows the ikut_kk radio', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $response = actingAs($user)->get(route('profile'));

    $response->assertOk();
    $response->assertSee('function updateWaliVisibility() {', false);
    $response->assertSee('$(\'#section-wali\').toggle(showWali);', false);
    $response->assertSee('$(\'.wali-doc\').toggle(showWali);', false);
    $response->assertSee('$(\'input[name="ikut_kk"]\').on(\'change\', updateWaliVisibility);', false);
    $response->assertDontSee('statusToIkutKk', false);
    $response->assertDontSee('status_orang_tua', false);
});

test('profil update requires campus data ipk and semester', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    actingAs($user)
        ->put(route('profile.update'), [
            'nama_lengkap' => 'Budi',
            'kecamatan' => 'Awayan',
            'desa_kelurahan' => 'Ambakiang',
        ])
        ->assertSessionHasErrors(['prodi_id', 'ipk', 'semester']);
});

test('profil update accepts NIM as optional', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = array_merge(completeProfilePayload($this->prodi), ['nim' => '']);

    actingAs($user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success');
});

test('profil update validates parent nik to 16 digits', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = array_merge(completeProfilePayload($this->prodi), [
        'nik_ayah' => '123',
        'nik_ibu' => '456',
        'nik_wali' => '789',
    ]);

    actingAs($user)
        ->put(route('profile.update'), $payload)
        ->assertSessionHasErrors(['nik_ayah', 'nik_ibu', 'nik_wali']);
});

test('profil shows validation message under the followed parent nik field', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    actingAs($user)
        ->from(route('profile'))
        ->put(route('profile.update'), array_merge(completeProfilePayload($this->prodi), [
            'ikut_kk' => 'ayah',
            'nik_ayah' => '',
        ]))
        ->assertSessionHasErrors(['nik_ayah'])
        ->assertSessionDoesntHaveErrors(['nik_ibu', 'nik_wali']);

    $response = actingAs($user->fresh())->get(route('profile'));
    $response->assertOk();

    $html = $response->original->withErrors([
        'nik_ayah' => 'NIK ayah harus diisi.',
        'nik_ibu' => 'NIK ibu harus diisi.',
    ])->render();

    expect($html)->toContain('is-invalid')
        ->and($html)->toContain('NIK ayah harus diisi.')
        ->and($html)->toContain('NIK ibu harus diisi.');
});

test('profil update validates ipk and semester range', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    actingAs($user)
        ->put(route('profile.update'), array_merge(completeProfilePayload($this->prodi), [
            'ipk' => 5,
            'semester' => 20,
        ]))
        ->assertSessionHasErrors(['ipk', 'semester']);
});

test('profile is incomplete without campus data and complete with it', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Ahmad',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000009999',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'nama_ayah' => 'Ayah',
        'nik_ayah' => '6302000000000002',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
    ]);

    expect($user->refresh()->isProfileComplete())->toBeFalse();
    $missing = $user->getMissingProfileFields();
    $this->assertContains('Program Studi', $missing);
    $this->assertContains('IPK', $missing);
    $this->assertContains('Semester', $missing);

    $user->profile->update([
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
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp_ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp_ibu.pdf',
    ]);

    expect($user->refresh()->isProfileComplete())->toBeTrue();
});

test('alamat_lengkap accessor composes full address', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Ahmad',
        'alamat' => 'RT 01 RW 02',
        'provinsi' => 'Kalimantan Selatan',
        'kabupaten_kota' => 'Balangan',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
    ]);

    expect($user->profile->alamat_lengkap)->toBe('RT 01 RW 02, Ambakiang, Kec. Awayan, Balangan, Kalimantan Selatan');
});
