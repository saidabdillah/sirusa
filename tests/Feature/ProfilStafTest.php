<?php

use App\Models\Kampus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil');

beforeEach(function () {
    seedAkses();

    Http::fake([
        'konoland-api.vercel.app/*' => Http::response(['data' => []]),
    ]);

    $kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->prodi = $kampus->fakultas()
        ->create(['nama' => 'Fakultas Teknik'])
        ->prodi()
        ->create(['nama' => 'Teknik Informatika']);
});

/**
 * Hanya akun `user` (mahasiswa) yang punya baris profil isian + dokumen.
 * `super_admin`/`kesra`/`kampus`/`capil` tidak punya profil sama sekali, jadi
 * halaman profil untuk mereka harus berhenti di Informasi Akun.
 */
function stafFactory(string $peran): User
{
    $factory = match ($peran) {
        'super_admin' => User::factory()->superAdmin(),
        'kesra' => User::factory()->admin(),
        'kampus' => User::factory()->kampusAdmin(),
        'capil' => User::factory()->capil(),
    };

    return $factory->create(['email' => $peran.'@test.com']);
}

dataset('peran staf', ['super_admin', 'kesra', 'kampus', 'capil']);

test('halaman profil akun staf hanya menampilkan informasi akun', function (string $peran) {
    $user = stafFactory($peran);

    actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Informasi Akun')
        ->assertSee($user->username)
        ->assertSee('Akun staf tidak punya data profil')
        // Tidak ada form isian maupun unggah berkas.
        ->assertDontSee('Edit Profil')
        ->assertDontSee('Simpan Profil')
        ->assertDontSee('type="file"', false)
        ->assertDontSee('name="nama_lengkap"', false)
        ->assertDontSee('name="nik"', false)
        ->assertDontSee('Berkas Data Diri')
        ->assertDontSee('Data Orang Tua &amp; Wali', false)
        ->assertDontSee('Unggah dokumen sekali di profil.')
        // Alert kelengkapan/verifikasi hanya relevan untuk mahasiswa.
        ->assertDontSee('Profil belum lengkap')
        ->assertDontSee('Anda harus melengkapi data dan dokumen berikut');
})->with('peran staf');

test('halaman profil akun staf tidak memanggil API wilayah', function (string $peran) {
    $user = stafFactory($peran);

    Http::fake();

    actingAs($user)
        ->get(route('profile'))
        ->assertOk();

    Http::assertNothingSent();
})->with('peran staf');

test('akun staf tidak bisa menyimpan profil', function (string $peran) {
    $user = stafFactory($peran);

    actingAs($user)
        ->put(route('profile.update'), ['nama_lengkap' => 'Percobaan'])
        ->assertForbidden();
})->with('peran staf');

test('akun staf tidak punya baris profil yang tersentuh percobaan simpan', function (string $peran) {
    $user = stafFactory($peran);

    expect($user->profile)->toBeNull();

    actingAs($user)
        ->put(route('profile.update'), ['nama_lengkap' => 'Percobaan'])
        ->assertForbidden();

    expect($user->fresh()->profile)->toBeNull();
})->with('peran staf');

test('mahasiswa tetap melihat form profil lengkap dengan unggah berkas', function () {
    $user = User::factory()->standardUser()->create(['email' => 'mahasiswa@test.com']);

    $html = actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Edit Profil')
        ->assertSee('Simpan Profil')
        ->assertSee('Informasi Akun')
        ->assertSee('type="file"', false)
        ->assertSee('name="nama_lengkap"', false)
        ->assertSee('Berkas Data Diri')
        ->assertSee('Anda harus melengkapi data dan dokumen berikut sebelum bisa mendaftar beasiswa.')
        ->assertDontSee('justify-content-center', false);

    // Grid 8/4 untuk mahasiswa, tanpa offset.
    expect($html->getContent())->toContain('col-lg-8')->toContain('col-12 col-lg-4');
});

/**
 * Kartu Informasi Akun untuk akun staf harus di tengah di semua viewport.
 * `offset-lg-*` hanya aktif di >=992px; di bawah itu kolom jadi flex item
 * bersebelahan, jadi lebih baik row-nya yang di-center.
 */
test('kartu informasi akun akun staf di center di semua viewport', function (string $peran) {
    $user = stafFactory($peran);

    $html = actingAs($user)
        ->get(route('profile'))
        ->assertOk();

    expect($html->getContent())
        ->toContain('<div class="row justify-content-center">')
        ->toContain('col-12 col-lg-6')
        ->not->toContain('offset-lg-3');
})->with('peran staf');

test('mahasiswa tetap boleh menyimpan profil seperti biasa', function () {
    $user = User::factory()->standardUser()->create(['email' => 'mahasiswa@test.com']);

    actingAs($user)
        ->put(route('profile.update'), [])
        ->assertSessionHasErrors('nama_lengkap')
        ->assertSessionHasErrors('nik');
});

test('user yang punya role mahasiswa dan role staf tetap melihat form profil', function () {
    $user = User::factory()->standardUser()->admin()->create(['email' => 'ganda@test.com']);

    expect($user->isMahasiswa())->toBeTrue();

    actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Edit Profil')
        ->assertSee('type="file"', false);
});
