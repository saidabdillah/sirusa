<?php

use App\Models\Scholarship;
use Database\Seeders\KampusSeeder;
use Database\Seeders\ScholarshipSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('seeder', 'penerima');

beforeEach(function () {
    seedAkses();
});

test('user seeder creates 50 accepted applicants for Pendidikan scholarship', function () {
    seed(KampusSeeder::class);
    seed(ScholarshipSeeder::class);
    seed(UserSeeder::class);

    $scholarship = Scholarship::where('nama', 'Beasiswa Pendidikan Kab. Balangan')->firstOrFail();
    $penerima = $scholarship->penerima()->get();

    expect($penerima)->toHaveCount(50);

    foreach ($penerima as $applicant) {
        expect($applicant->user->profile)->not->toBeNull()
            ->and($applicant->user->profile->nim)->not->toBeNull()
            ->and($applicant->user->profile->prodi?->fakultas?->kampus?->nama_kampus)
            ->toBe('Universitas Lambung Mangkurat')
            ->and($applicant->fakultas)->not->toBeNull()
            ->and($applicant->prodi)->not->toBeNull();
    }
});

test('user seeder is idempotent for the bulk penerima block', function () {
    seed(KampusSeeder::class);
    seed(ScholarshipSeeder::class);

    seed(UserSeeder::class);
    seed(UserSeeder::class);

    $scholarship = Scholarship::where('nama', 'Beasiswa Pendidikan Kab. Balangan')->firstOrFail();

    expect($scholarship->penerima()->count())->toBe(50);
});
