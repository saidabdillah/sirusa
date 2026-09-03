<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil');

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
        ->assertSee('Semester');
});

test('user can update profile with campus data and parent nik', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = [
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'nik_ayah' => '6302000000000002',
        'nik_ibu' => '6302000000000003',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'alamat' => 'RT 01 RW 02',
        'prodi_id' => $this->prodi->id,
        'ipk' => 3.75,
        'semester' => 5,
        'status_orang_tua' => 'Lengkap',
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

    $base = [
        'nama_lengkap' => 'Budi',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $this->prodi->id,
        'ipk' => 3.5,
        'semester' => 4,
    ];

    actingAs($user)
        ->put(route('profile.update'), $base + [
            'nik_ayah' => '123',
            'nik_ibu' => '456',
            'nik_wali' => '789',
        ])
        ->assertSessionHasErrors(['nik_ayah', 'nik_ibu', 'nik_wali']);
});

test('profil update validates ipk and semester range', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    actingAs($user)
        ->put(route('profile.update'), [
            'nama_lengkap' => 'Budi',
            'kecamatan' => 'Awayan',
            'desa_kelurahan' => 'Ambakiang',
            'prodi_id' => $this->prodi->id,
            'ipk' => 5,
            'semester' => 20,
        ])
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
        'status_orang_tua' => 'Wali',
        'nama_wali' => 'Paman',
        'hubungan_wali' => 'Paman',
        'pekerjaan_wali' => 'Petani',
        'penghasilan_wali' => '< 1jt',
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
