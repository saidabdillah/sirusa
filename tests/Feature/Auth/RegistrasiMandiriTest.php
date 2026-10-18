<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('auth', 'registrasi');

beforeEach(function () {
    seedAkses();
    Storage::fake('public');

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->kampusLain = Kampus::create(['nama_kampus' => 'Politeknik Negeri Banjarmasin']);
});

function payloadDaftar(int $kampusId, array $ubah = []): array
{
    return array_merge([
        'nik' => '6302000000000001',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'kampus_id' => $kampusId,
    ], $ubah);
}

test('halaman registrasi terbuka untuk pengunjung', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Daftar Akun Mahasiswa')
        ->assertSee('-- Pilih Kampus --')
        ->assertSee($this->kampus->nama_kampus);
});

test('mahasiswa bisa mendaftar sendiri dengan NIK, kata sandi, dan kampus', function () {
    $this->post(route('register.store'), payloadDaftar($this->kampus->id))
        ->assertRedirect(route('login'))
        ->assertSessionHas('success');

    $user = User::where('username', '6302000000000001')->first();

    expect($user)->not->toBeNull()
        // Role ditentukan server: akun baru selalu mahasiswa.
        ->and($user->getRoleNames()->all())->toBe(['user'])
        ->and($user->kampus_id)->toBe($this->kampus->id)
        // Mekanisme aktivasi yang sudah berlaku dipertahankan: akun baru belum
        // bisa dipakai sampai admin mengaktifkannya.
        ->and($user->status)->toBe('non-aktif');

    // Baris profil dibuat dengan NIK saja; sisanya dilengkapi mahasiswa sendiri.
    $profil = UserProfile::where('user_id', $user->id)->first();

    expect($profil)->not->toBeNull()
        ->and($profil->nik)->toBe('6302000000000001');
});

test('NIK duplikat ditolak', function () {
    $this->post(route('register.store'), payloadDaftar($this->kampus->id))
        ->assertRedirect(route('login'));

    $this->post(route('register.store'), payloadDaftar($this->kampusLain->id))
        ->assertSessionHasErrors([
            'nik' => 'NIK ini sudah terdaftar. Silakan masuk atau hubungi admin.',
        ]);

    expect(User::where('username', '6302000000000001')->count())->toBe(1);
});

test('NIK harus 16 digit angka', function (string $nik) {
    $this->post(route('register.store'), payloadDaftar($this->kampus->id, ['nik' => $nik]))
        ->assertSessionHasErrors('nik');

    expect(User::count())->toBe(0);
})->with([
    'kurang digit' => ['123'],
    'lebih digit' => ['63020000000000011'],
    'ada huruf' => ['63020000000000ab'],
    'kosong' => [''],
]);

test('konfirmasi kata sandi harus cocok', function () {
    $this->post(route('register.store'), payloadDaftar($this->kampus->id, [
        'password_confirmation' => 'beda12345',
    ]))->assertSessionHasErrors('password');

    expect(User::count())->toBe(0);
});

test('kata sandi mengikuti aturan keamanan aplikasi', function () {
    $this->post(route('register.store'), payloadDaftar($this->kampus->id, [
        'password' => 'pendek',
        'password_confirmation' => 'pendek',
    ]))->assertSessionHasErrors('password');

    expect(User::count())->toBe(0);
});

test('kampus harus dipilih dari data yang tersimpan', function () {
    $this->post(route('register.store'), payloadDaftar(999999))
        ->assertSessionHasErrors('kampus_id');

    expect(User::count())->toBe(0);
});

test('pengguna tidak bisa memilih peran sendiri', function () {
    // `peran` dan `role` sengaja dikirim untuk memastikan server mengabaikannya.
    $this->post(route('register.store'), payloadDaftar($this->kampus->id, [
        'peran' => 'super_admin',
        'role' => 'super_admin',
    ]))->assertRedirect(route('login'));

    $user = User::where('username', '6302000000000001')->first();

    expect($user->getRoleNames()->all())->toBe(['user'])
        ->and($user->hasRole('super_admin'))->toBeFalse();
});

test('akun hasil registrasi belum bisa masuk sebelum diaktifkan', function () {
    $this->post(route('register.store'), payloadDaftar($this->kampus->id));

    $this->post(route('login.store'), [
        'login' => '6302000000000001',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors('login');

    expect(auth()->check())->toBeFalse();
});

test('mahasiswa bisa masuk memakai NIK setelah akunnya diaktifkan', function () {
    $this->post(route('register.store'), payloadDaftar($this->kampus->id));

    User::where('username', '6302000000000001')->first()->update(['status' => 'aktif']);

    $this->post(route('login.store'), [
        'login' => '6302000000000001',
        'password' => 'rahasia123',
    ])->assertRedirect(route('dashboard'));

    expect(auth()->user()->username)->toBe('6302000000000001');
});

test('akun internal tetap bisa masuk dengan username seperti sebelumnya', function () {
    $staf = User::factory()->admin()->create([
        'username' => 'kesra_staf',
        'email' => 'kesra@test.com',
        'password' => 'rahasia123',
    ]);

    $this->post(route('login.store'), [
        'login' => 'kesra_staf',
        'password' => 'rahasia123',
    ])->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($staf->id);
});

test('halaman masuk menautkan ke halaman registrasi', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('register'));
});
