<?php

use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function seedAkses()
{
    Role::firstOrCreate(['name' => 'super_admin']);
    Role::firstOrCreate(['name' => 'capil']);
    Role::firstOrCreate(['name' => 'kampus']);
    Role::firstOrCreate(['name' => 'kesra']);
    Role::firstOrCreate(['name' => 'user']);

    (new MenuSeeder)->run();
}

/**
 * Berkas yang form profil tandai dengan `*` dan `UpdateProfilRequest` mewajibkan.
 * Pakai `Storage::fake('public')` di beforeEach supaya file palsu tidak ditulis ke disk.
 */
function berkasProfilDummy(): array
{
    $pdf = fn (string $nama) => UploadedFile::fake()->create($nama, 100, 'application/pdf');

    return [
        'foto_profil' => UploadedFile::fake()->image('foto-profil.jpg'),
        'dokumen_ktp' => $pdf('ktp.pdf'),
        'dokumen_kk' => $pdf('kk.pdf'),
        'dokumen_desil' => $pdf('desil.pdf'),
        'dokumen_sktm' => $pdf('sktm.pdf'),
        'dokumen_transkrip' => $pdf('transkrip.pdf'),
        'dokumen_surat_aktif' => $pdf('surat-aktif.pdf'),
        'dokumen_surat_pernyataan' => $pdf('surat-pernyataan.pdf'),
        'dokumen_bukti_ukt' => $pdf('bukti-ukt.pdf'),
        'ktp_ayah' => $pdf('ktp-ayah.pdf'),
        'ktp_ibu' => $pdf('ktp-ibu.pdf'),
    ];
}

function something()
{
    // ..
}
