<?php

use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Database\Seeders\KampusSeeder;
use Database\Seeders\ScholarshipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('admin', 'beasiswa');

test('scholarship seeder links every beasiswa to a catalog kampus', function () {
    seed(KampusSeeder::class);
    seed(ScholarshipSeeder::class);

    $scholarships = Scholarship::all();

    expect($scholarships)->not->toBeEmpty()
        ->and($scholarships->every(fn ($scholarship) => $scholarship->kampus_id !== null))->toBeTrue();

    foreach ($scholarships as $scholarship) {
        expect(Kampus::find($scholarship->kampus_id)->nama_kampus)->toBe($scholarship->kampus);
    }
});

test('edit form preselects kampus and checks matching prodi when kampus_id is null', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Test']);
    $fakultas = $kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $prodi = $fakultas->prodi()->create(['nama' => 'Informatika']);

    $scholarship = Scholarship::factory()->create(['kampus_id' => null, 'kampus' => $kampus->nama_kampus]);
    $snapshot = $scholarship->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $snapshot->prodi()->create(['nama' => 'Informatika']);

    Role::create(['name' => 'admin']);
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get(route('admin.beasiswa.ubah', $scholarship))
        ->assertOk()
        ->assertSee('value="'.$kampus->id.'" selected', false);

    $html = actingAs($admin)->get(route('admin.beasiswa.ubah', $scholarship))->getContent();
    expect(preg_match('/id="prodi-'.$prodi->id.'"[^>]*checked/', $html))->toBe(1);
});
