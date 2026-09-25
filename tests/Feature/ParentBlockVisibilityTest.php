<?php

use App\Models\Applicant;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pendaftar', 'parent', 'blok');

dataset('waliData', [
    'kk ikut wali' => [
        'kkIkutWali' => true,
        'shown' => ['Ayah Lengkap', 'Ibu Lengkap', 'Wali Lengkap'],
        'hidden' => [],
    ],
    'kk tidak ikut wali' => [
        'kkIkutWali' => false,
        'shown' => ['Ayah Lengkap', 'Ibu Lengkap'],
        'hidden' => ['Wali Lengkap'],
    ],
]);

beforeEach(function () {
    seedAkses();
});

function createProfileWithParentData(User $user, bool $kkIkutWali): UserProfile
{
    return UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Orang Tua Test',
        'nik' => '6302000000000001',
        'kk_ikut_wali' => $kkIkutWali,
        'nama_ayah' => 'Ayah Lengkap',
        'nama_ibu' => 'Ibu Lengkap',
        'nama_wali' => 'Wali Lengkap',
    ]);
}

test('user pendaftaran detail shows parent blocks per wali', function (bool $kkIkutWali, array $shown, array $hidden) {
    $user = User::factory()->standardUser()->create();
    createProfileWithParentData($user, $kkIkutWali);
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)
        ->get(route('user.pendaftaran.lihat', $applicant))
        ->assertOk();

    $response->assertSee('Data Orang Tua &amp; Wali', false);

    foreach ($shown as $name) {
        $response->assertSee($name, false);
    }

    foreach ($hidden as $name) {
        $response->assertDontSee($name, false);
    }
})->with('waliData');
