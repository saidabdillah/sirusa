<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\DataVerificationChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('verifikasi', 'admin', 'profil');

function verifikasiProfilePayload(int $prodiId): array
{
    return [
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000000002',
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
        'ukt' => 2500000,
        'desil' => 3,
        'nama_ayah' => 'Ayah Ahmad',
        'nik_ayah' => '6302000000000003',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Ahmad',
        'nik_ibu' => '6302000000000004',
        'pekerjaan_ibu' => 'Petani',
    ];
}

function createPendingVerifikasiProfile(User $user, string $stage): UserProfile
{
    $stages = [
        'capil' => ['verif_capil' => 'menunggu', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kampus' => ['verif_capil' => 'setuju', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kesra' => ['verif_capil' => 'setuju', 'verif_kampus' => 'setuju', 'verif_kesra' => 'menunggu'],
    ];

    $suffix = str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);

    return UserProfile::create(array_merge([
        'user_id' => $user->id,
        'nama_lengkap' => "Ahmad Fauzi #{$user->id}",
        'nik' => '6302000000'.$suffix,
        'no_kk' => '6302000001'.$suffix,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
    ], $stages[$stage]));
}

beforeEach(function () {
    seedAkses();

    $this->capilAdmin = User::factory()->capil()->create(['email' => 'capil@test.com']);
    $this->kampusAdmin = User::factory()->kampusAdmin()->create(['email' => 'kampus@test.com']);
    $this->kesraAdmin = User::factory()->admin()->create(['email' => 'kesra@test.com']);

    $this->prodi = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat'])
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->applicantUser = User::factory()->standardUser()->create(['email' => 'user@test.com']);
});

test('capil index lists only profiles pending capil verification', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'capil');
    $ready = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($ready, 'kampus');

    actingAs($this->capilAdmin)
        ->get(route('admin.capil.index'))
        ->assertOk()
        ->assertSee($this->applicantUser->profile->nama_lengkap)
        ->assertDontSee($ready->profile->nama_lengkap);
});

test('kampus index lists only profiles whose capil stage was approved', function () {
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'kampus');

    $waitingCapil = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($waitingCapil, 'capil');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.index'))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertDontSee($waitingCapil->profile->nama_lengkap);
});

test('kesra index lists only profiles approved by previous stages', function () {
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'kesra');

    $waitingKampus = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($waitingKampus, 'kampus');

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertDontSee($waitingKampus->profile->nama_lengkap);
});

test('verification stage detail is forbidden when the stage is not reachable yet', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertForbidden();
});

test('capil approving a profile persists the status and notifies the owner', function () {
    Notification::fake();

    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), [
            'status' => 'setuju',
            'catatan' => 'Data sesuai',
        ])
        ->assertRedirect(route('admin.capil.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_capil)->toBe('setuju');
    expect($profile->catatan_capil)->toBe('Data sesuai');

    Notification::assertSentTo($this->applicantUser, DataVerificationChanged::class);
});

test('kesra approval marks the profile as fully verified', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra');

    actingAs($this->kesraAdmin)
        ->put(route('admin.kesra.verifikasi', $this->applicantUser), ['status' => 'setuju'])
        ->assertRedirect(route('admin.kesra.index'));

    expect($this->applicantUser->refresh()->profile->isVerified())->toBeTrue();
});

test('revisi at a stage blocks downstream stages until fixed', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->kampusAdmin)
        ->put(route('admin.kampusverif.verifikasi', $this->applicantUser), [
            'status' => 'revisi',
            'catatan' => 'Dokumen tidak terbaca',
        ])
        ->assertRedirect(route('admin.kampusverif.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_kampus)->toBe('revisi');
    expect($profile->verifStatus())->toBe('revisi');
    expect($profile->canVerifStage('kesra'))->toBeFalse();
});

test('tolak at a stage persists the status and blocks downstream stages', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->kampusAdmin)
        ->put(route('admin.kampusverif.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'Dokumen tidak valid',
        ])
        ->assertRedirect(route('admin.kampusverif.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_kampus)->toBe('tolak');
    expect($profile->verifStatus())->toBe('tolak');
    expect($profile->canVerifStage('kesra'))->toBeFalse();
});

test('tolak keputusan option is rendered on the verification decision page', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('value="tolak"', false)
        ->assertSee('>Tolak</option>', false);
});

test('regular user cannot access verification pages', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->applicantUser)
        ->get(route('admin.capil.index'))
        ->assertForbidden();
});

test('updating profile resets verification stages', function () {
    $user = $this->applicantUser;
    createPendingVerifikasiProfile($user, 'kesra')->update([
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);
    expect($user->refresh()->profile->isVerified())->toBeTrue();

    actingAs($user)
        ->put(route('profile.update'), array_merge(verifikasiProfilePayload($this->prodi->id), [
            'nama_lengkap' => 'Ahmad Baru',
        ]))
        ->assertRedirect(route('profile'));

    $profile = $user->refresh()->profile;
    expect($profile->nama_lengkap)->toBe('Ahmad Baru');
    expect($profile->verif_capil)->toBe('menunggu');
    expect($profile->verif_kampus)->toBe('menunggu');
    expect($profile->verif_kesra)->toBe('menunggu');
    expect($profile->catatan_capil)->toBeNull();
});
