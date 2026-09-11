<?php

use App\Models\Applicant;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pendaftar', 'parent', 'blok');

dataset('parentStatus', [
    'lengkap' => [
        'status' => 'Lengkap',
        'ayah' => 'Ayah Lengkap',
        'ibu' => 'Ibu Lengkap',
        'wali' => 'Wali Lengkap',
        'shown' => ['Ayah Lengkap', 'Ibu Lengkap'],
        'hidden' => ['Wali Lengkap'],
    ],
    'yatim' => [
        'status' => 'Yatim',
        'ayah' => 'Ayah Yatim',
        'ibu' => 'Ibu Yatim',
        'wali' => 'Wali Yatim',
        'shown' => ['Ibu Yatim'],
        'hidden' => ['Ayah Yatim', 'Wali Yatim'],
    ],
    'piatu' => [
        'status' => 'Piatu',
        'ayah' => 'Ayah Piatu',
        'ibu' => 'Ibu Piatu',
        'wali' => 'Wali Piatu',
        'shown' => ['Ayah Piatu'],
        'hidden' => ['Ibu Piatu', 'Wali Piatu'],
    ],
    'yatim-piatu' => [
        'status' => 'Yatim Piatu',
        'ayah' => 'Ayah Yatim Piatu',
        'ibu' => 'Ibu Yatim Piatu',
        'wali' => 'Wali Yatim Piatu',
        'shown' => ['Wali Yatim Piatu'],
        'hidden' => ['Ayah Yatim Piatu', 'Ibu Yatim Piatu'],
    ],
]);

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'admin@parent.test']);
});

function createProfileWithParentData(User $user, string $status, string $ayah, string $ibu, string $wali): UserProfile
{
    return UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Orang Tua Test',
        'nik' => '6302000000000001',
        'status_orang_tua' => $status,
        'nama_ayah' => $ayah,
        'nama_ibu' => $ibu,
        'nama_wali' => $wali,
    ]);
}

test('admin pendaftar detail shows parent blocks per status', function (string $status, string $ayah, string $ibu, string $wali, array $shown, array $hidden) {
    $user = User::factory()->standardUser()->create();
    createProfileWithParentData($user, $status, $ayah, $ibu, $wali);
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);

    $response = actingAs($this->admin)
        ->get(route('admin.pendaftar.lihat', $applicant))
        ->assertOk();

    foreach ($shown as $name) {
        $response->assertSee($name, false);
    }

    foreach ($hidden as $name) {
        $response->assertDontSee($name, false);
    }
})->with('parentStatus');

test('user pendaftaran detail shows parent blocks per status', function (string $status, string $ayah, string $ibu, string $wali, array $shown, array $hidden) {
    $user = User::factory()->standardUser()->create();
    createProfileWithParentData($user, $status, $ayah, $ibu, $wali);
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);

    $response = actingAs($user)
        ->get(route('user.pendaftaran.lihat', $applicant))
        ->assertOk();

    foreach ($shown as $name) {
        $response->assertSee($name, false);
    }

    foreach ($hidden as $name) {
        $response->assertDontSee($name, false);
    }
})->with('parentStatus');
