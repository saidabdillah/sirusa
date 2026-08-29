<?php

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
});

test('user can open profil form with region fields', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Kecamatan')
        ->assertSee('Desa/Kelurahan')
        ->assertSee('Kalimantan Selatan')
        ->assertSee('Balangan')
        ->assertSee('Awayan')
        ->assertSee('Juai');
});

test('user can update profile with region', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    $payload = [
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'alamat' => 'RT 01 RW 02',
    ];

    actingAs($user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('profil_pengguna', [
        'user_id' => $user->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'provinsi' => 'Kalimantan Selatan',
        'kabupaten_kota' => 'Balangan',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'alamat' => 'RT 01 RW 02',
    ]);
});

test('profil update requires kecamatan and desa', function () {
    $user = User::factory()->standardUser()->create(['email' => 'user@test.com']);

    actingAs($user)
        ->put(route('profile.update'), ['nama_lengkap' => 'Budi'])
        ->assertSessionHasErrors(['kecamatan', 'desa_kelurahan']);
});

test('profile is incomplete without region and complete with it', function () {
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
    ]);

    expect($user->refresh()->isProfileComplete())->toBeFalse();
    $missing = $user->getMissingProfileFields();
    $this->assertContains('Kecamatan', $missing);
    $this->assertContains('Desa/Kelurahan', $missing);

    $user->profile->update([
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
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
