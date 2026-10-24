<?php

use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
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
    Role::firstOrCreate(['name' => 'catpil']);
    Role::firstOrCreate(['name' => 'kampus']);
    Role::firstOrCreate(['name' => 'kesra']);
    Role::firstOrCreate(['name' => 'user']);

    (new MenuSeeder)->run();
    (new PermissionSeeder)->run();
}

/**
 * Berkas yang form profil tandai dengan `*` dan `UpdateProfilRequest` mewajibkan.
 * Pakai `Storage::fake('public')` di beforeEach supaya file palsu tidak ditulis ke disk.
 */
function berkasProfilDummy(): array
{
    $pdf = fn (string $nama) => UploadedFile::fake()->create($nama, 100, 'application/pdf');

    // SKTM dan Transkrip tidak lagi ada di sini: SKTM tidak lagi disyaratkan
    // dan Transkrip digantikan PDDIKTI (permintaan pengguna).
    return [
        'foto_profil' => UploadedFile::fake()->image('foto-profil.jpg'),
        'dokumen_ktp' => $pdf('ktp.pdf'),
        'dokumen_kk' => $pdf('kk.pdf'),
        'dokumen_desil' => $pdf('desil.pdf'),
        'dokumen_surat_aktif' => $pdf('surat-aktif.pdf'),
        'dokumen_surat_pernyataan' => $pdf('surat-pernyataan.pdf'),
        'dokumen_pddikti' => $pdf('pddikti.pdf'),
        'dokumen_bukti_ukt' => $pdf('bukti-ukt.pdf'),
        'ktp_ayah' => $pdf('ktp-ayah.pdf'),
        'ktp_ibu' => $pdf('ktp-ibu.pdf'),
    ];
}

/**
 * Berkas KRS/KHS yang sekarang WAJIB dihitung dari semester aktif:
 * KRS semester 1..N, KHS semester 1..(N-1) beserta nilainya.
 *
 * Dipakai payload uji supaya tes profil tidak perlu tahu aturan itu satu per
 * satu; tes yang memang menguji kewajibannya membangun payload sendiri.
 */
function akademikProfilDummy(int $semesterAktif): array
{
    $pdf = fn (string $nama) => UploadedFile::fake()->create($nama, 100, 'application/pdf');

    $akademik = ['krs' => [], 'khs' => []];

    for ($s = 1; $s <= max(1, $semesterAktif); $s++) {
        $akademik['krs'][$s] = ['file' => $pdf("krs-{$s}.pdf")];
    }

    for ($s = 1; $s <= $semesterAktif - 1; $s++) {
        $akademik['khs'][$s] = ['file' => $pdf("khs-{$s}.pdf"), 'nilai' => 3.5];
    }

    return $akademik;
}

/**
 * Field pendidikan yang sekarang WAJIB di form profil.
 *
 * Dipisah supaya payload uji yang dibuat bertahap (mis. `array_merge` di
 * beberapa test) tidak perlu mengulang ketiganya, dan supaya nilai bawaannya
 * konsisten: S1 dengan semester 5 selalu sah.
 *
 * @return array<string, mixed>
 */
function pendidikanProfilDummy(): array
{
    return [
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => 0,
    ];
}

function something()
{
    // ..
}
