<?php

use App\Models\Kampus;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('keamanan', 'rate-limit');

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);

    // Akun staf (bukan mahasiswa): mahasiswa sekarang masuk hanya dengan NIK,
    // sedangkan akun staf masuk dengan username. Batas percobaan masuk tidak
    // bergantung peran, jadi memakai akun staf membuat tes ini tetap sederhana.
    $this->user = User::factory()->catpil()->create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'status' => 'aktif',
    ]);
});

/*
|--------------------------------------------------------------------------
| Percobaan masuk
|--------------------------------------------------------------------------
*/

test('percobaan masuk ditahan setelah lima kali salah', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login.store'), [
            'login' => 'testuser',
            'password' => 'salah-terus',
        ])->assertSessionHasErrors('login');
    }

    // Percobaan keenam tidak lagi memeriksa kata sandi, langsung ditahan.
    $this->post(route('login.store'), [
        'login' => 'testuser',
        'password' => 'password123',
    ])->assertSessionHasErrors('login');

    expect(session('errors')->first('login'))
        ->toContain('Terlalu banyak percobaan masuk')
        ->and(auth()->check())->toBeFalse();
});

test('masuk yang berhasil menghapus hitungan percobaan gagal', function () {
    for ($i = 0; $i < 4; $i++) {
        $this->post(route('login.store'), [
            'login' => 'testuser',
            'password' => 'salah-terus',
        ]);
    }

    $this->post(route('login.store'), [
        'login' => 'testuser',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));

    $this->post(route('logout'));

    // Jatah kembali penuh: empat kesalahan berikutnya belum menahan akun.
    for ($i = 0; $i < 4; $i++) {
        $this->post(route('login.store'), [
            'login' => 'testuser',
            'password' => 'salah-terus',
        ])->assertSessionHasErrors([
            'login' => 'NIK, username, atau kata sandi salah',
        ]);
    }

    $this->post(route('login.store'), [
        'login' => 'testuser',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));
});

test('batas percobaan masuk dihitung per akun, bukan per IP', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login.store'), [
            'login' => 'testuser',
            'password' => 'salah-terus',
        ]);
    }

    // Akun lain dari IP yang sama tetap bisa masuk.
    $lain = User::factory()->create([
        'username' => 'pengguna-lain',
        'password' => bcrypt('password123'),
        'status' => 'aktif',
    ]);

    $this->post(route('login.store'), [
        'login' => 'pengguna-lain',
        'password' => 'password123',
    ])->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($lain->id);
});

/*
|--------------------------------------------------------------------------
| Registrasi
|--------------------------------------------------------------------------
*/

test('pendaftaran akun dibatasi lima per jam per IP', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->post(route('register.store'), [
            'nik' => '63020000000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'kampus_id' => $this->kampus->id,
        ])->assertRedirect(route('login'));
    }

    $this->post(route('register.store'), [
        'nik' => '6302000000000099',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'kampus_id' => $this->kampus->id,
    ])->assertStatus(429)
        ->assertSee('Terlalu banyak permintaan');

    expect(User::where('username', '6302000000000099')->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Aksi yang mengubah data
|--------------------------------------------------------------------------
*/

test('permintaan baca tidak dihitung pada batas aksi tulis', function () {
    $limit = (RateLimiter::limiter('tulis'))(Request::create('/dasbor', 'GET'));

    expect($limit)->toBeInstanceOf(Unlimited::class);
});

test('aksi tulis ditahan setelah batasnya habis, halaman baca tetap jalan', function () {
    actingAs($this->user);

    // Isi jatah aksi tulis pengguna ini sampai penuh. Middleware `throttle`
    // menyimpan kuncinya dalam bentuk md5(nama limiter . kunci), jadi kuncinya
    // disamakan di sini supaya yang diuji benar-benar limiter `tulis`.
    $kunci = md5('tulis'.$this->user->id);

    for ($i = 0; $i < 60; $i++) {
        RateLimiter::hit($kunci, 60);
    }

    $this->post(route('logout'))
        ->assertStatus(429)
        ->assertSee('Terlalu banyak permintaan');

    // Membaca halaman tidak terpengaruh.
    $this->get(route('dashboard'))->assertOk();
});

/*
|--------------------------------------------------------------------------
| Halaman galat
|--------------------------------------------------------------------------
*/

test('halaman 429 memakai tampilan galat aplikasi', function () {
    $this->get(route('login'))->assertOk();

    RateLimiter::for('uji-429', fn () => Limit::perMinute(1)->by('uji'));

    Route::middleware('throttle:uji-429')->get('/_uji-429', fn () => 'ok');

    $this->get('/_uji-429')->assertOk();
    $this->get('/_uji-429')
        ->assertStatus(429)
        ->assertSee('429')
        ->assertSee('Terlalu banyak permintaan. Silakan tunggu sebentar lalu coba lagi.');
});
