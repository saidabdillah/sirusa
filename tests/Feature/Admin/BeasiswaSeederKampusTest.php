<?php

use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Database\Seeders\KampusSeeder;
use Database\Seeders\ScholarshipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('admin', 'beasiswa');

test('scholarship seeder menyimpan cakupan sebagai id prodi kampus itu', function () {
    seed(KampusSeeder::class);
    seed(ScholarshipSeeder::class);

    $scholarships = Scholarship::all();

    expect($scholarships)->not->toBeEmpty();

    // Cakupan bukan salinan nama: setiap baris menunjuk id prodi yang
    // benar-benar ada, dan fakultas/kampusnya dibaca dari prodi itu.
    foreach ($scholarships as $scholarship) {
        expect($scholarship->cakupan()->count())->toBeGreaterThan(0);

        foreach ($scholarship->cakupan as $cakupan) {
            expect($cakupan->prodi)->not->toBeNull()
                ->and($cakupan->prodi->fakultas)->not->toBeNull();
        }

        // Ringkasan teks kolom `kampus` diturunkan dari cakupan prodinya.
        expect($scholarship->kampus)->toContain(
            $scholarship->cakupan->first()->prodi->fakultas->kampus->nama_kampus
        );
    }
});

test('edit form menyalakan centang prodi yang sudah tercakup', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Test']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Informatika']);

    $scholarship = Scholarship::factory()->create(['kampus' => $kampus->nama_kampus]);
    $scholarship->cakupan()->create(['prodi_id' => $prodi->id]);

    seedAkses();
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get(route('admin.beasiswa.ubah', $scholarship))
        ->assertOk()
        ->assertSee('name="prodi_ids[]" value="'.$prodi->id.'" checked', false);

    $html = actingAs($admin)->get(route('admin.beasiswa.ubah', $scholarship))->getContent();
    expect(preg_match('/id="prodi-'.$prodi->id.'"[^>]*checked/', $html))->toBe(1);
});
