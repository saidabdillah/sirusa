<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil');

function completeProfilePayload($prodi): array
{
    return [
        'nama_lengkap' => 'Budi',
        'nik' => '6302000000000001',
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
        'status_orang_tua' => 'Lengkap',
    ];
}

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

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
        ->assertSee('Anda harus melengkapi data berikut sebelum bisa mendaftar beasiswa.')
        ->assertDontSee('<li>Status Orang Tua</li>', false)
        ->assertDontSee(' required>', false);
});

test('user can update profile with campus data and parent nik', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = [
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
        'nama_kampus' => 'Universitas Lambung Mangkurat',
        'fakultas' => 'Fakultas Teknik',
        'prodi_id' => $this->prodi->id,
        'ipk' => 3.75,
        'semester' => 5,
        'status_orang_tua' => 'Lengkap',
        'nama_ayah' => 'Ayah Fauzi',
        'nik_ayah' => '6302000000000002',
        'pekerjaan_ayah' => 'Petani',
        'penghasilan_ayah' => '< 1jt',
        'nama_ibu' => 'Ibu Fauzi',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
        'penghasilan_ibu' => '< 1jt',
    ];

    actingAs($user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('profil_pengguna', [
        'user_id' => $user->id,
        'nama_lengkap' => 'Ahmad Fauzi',
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

test('profil hides parent sections that do not apply to the status', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = array_merge(completeProfilePayload($this->prodi), [
        'status_orang_tua' => 'Yatim',
        'nama_ibu' => 'Ibu Yatim',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
        'penghasilan_ibu' => '< 1jt',
    ]);

    actingAs($user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'));

    $response = actingAs($user->fresh())->get(route('profile'));
    $response->assertOk();
    $response->assertSee('id="section-ayah" style="display:none;"', false);
    $response->assertSee('id="section-wali" style="display:none;"', false);
    $response->assertDontSee('id="section-ibu" style="display:none;"', false);
});

test('profil shows wali section only for yatim piatu status', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = array_merge(completeProfilePayload($this->prodi), [
        'status_orang_tua' => 'Yatim Piatu',
        'nama_wali' => 'Paman Wawan',
        'nik_wali' => '6302000000000004',
        'hubungan_wali' => 'Paman',
        'pekerjaan_wali' => 'Petani',
        'penghasilan_wali' => '< 1jt',
    ]);

    actingAs($user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'));

    $response = actingAs($user->fresh())->get(route('profile'));
    $response->assertOk();
    $response->assertSee('id="section-wali"', false);
    $response->assertSee('Nama Wali');
});

test('profil parent section toggle js matches the server side status mapping', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $response = actingAs($user)->get(route('profile'));

    $response->assertOk();
    $response->assertSee("\$('#section-ayah').toggle(status === 'Lengkap' || status === 'Piatu');", false);
    $response->assertSee("\$('#section-ibu').toggle(status === 'Lengkap' || status === 'Yatim');", false);
    $response->assertSee("\$('#section-wali').toggle(status === 'Yatim Piatu');", false);
    $response->assertDontSee('parentSectionSelectors', false);
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

test('profil update validates parent nik to 16 digits', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $base = array_merge(completeProfilePayload($this->prodi), [
        'nik_wali' => '789',
    ]);

    actingAs($user)
        ->put(route('profile.update'), $base + [
            'nik_ayah' => '123',
            'nik_ibu' => '456',
        ])
        ->assertSessionHasErrors(['nik_ayah', 'nik_ibu', 'nik_wali']);
});

test('profil shows validation messages under campus fakultas and nik ibu', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    actingAs($user)
        ->from(route('profile'))
        ->put(route('profile.update'), array_merge(completeProfilePayload($this->prodi), [
            'nama_kampus' => '',
            'fakultas' => '',
            'nik_ibu' => '',
        ]))
        ->assertSessionHasErrors(['nama_kampus', 'fakultas', 'nik_ibu']);

    $response = actingAs($user->fresh())->get(route('profile'));
    $response->assertOk();

    $html = $response->original->withErrors([
        'nama_kampus' => 'Nama kampus harus dipilih.',
        'fakultas' => 'Fakultas harus dipilih.',
        'nik_ibu' => 'NIK ibu harus diisi.',
    ])->render();

    expect($html)->toContain('is-invalid')
        ->and($html)->toContain('Nama kampus harus dipilih.')
        ->and($html)->toContain('Fakultas harus dipilih.')
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
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'status_orang_tua' => 'Lengkap',
        'nama_ayah' => 'Ayah',
        'nik_ayah' => '6302000000000002',
        'pekerjaan_ayah' => 'Petani',
        'penghasilan_ayah' => '< 1jt',
        'nama_ibu' => 'Ibu',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
        'penghasilan_ibu' => '< 1jt',
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
