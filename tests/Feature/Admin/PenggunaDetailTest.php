<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('admin', 'pengguna');

beforeEach(function () {
    seedAkses();

    $this->superAdmin = User::factory()->superAdmin()->create([
        'email' => 'superadmin@test.com',
    ]);

    $this->admin = User::factory()->admin()->create([
        'email' => 'kesra@test.com',
    ]);

    // Role tanpa grant `admin.pengguna`.
    $this->capil = User::factory()->capil()->create([
        'email' => 'capil@test.com',
    ]);

    $this->mahasiswa = User::factory()->standardUser()->create([
        'email' => 'mahasiswa@test.com',
    ]);

    UserProfile::create([
        'user_id' => $this->mahasiswa->id,
        'nama_lengkap' => 'Budi Santoso',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000009999',
        'jenis_kelamin' => 'Laki-laki',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01 RW 02',
        'desil' => 3,
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
    ]);
});

/**
 * Halaman detail read-only: info akun + Data Diri profil. Gated oleh grant menu
 * `admin.pengguna`, jadi hanya pengelola akun (kesra + super_admin) yang bisa.
 */
test('kesra bisa membuka detail pengguna mahasiswa', function () {
    actingAs($this->admin)
        ->get(route('admin.pengguna.lihat', $this->mahasiswa))
        ->assertOk()
        // Info akun
        ->assertSee('Detail Pengguna')
        ->assertSee('Informasi Akun')
        ->assertSee($this->mahasiswa->username)
        ->assertSee('mahasiswa@test.com')
        ->assertSee('Aktif')
        ->assertSee('Budi Santoso')
        // Data Diri dari partial profil-detail
        ->assertSee('Data Diri')
        ->assertSee('NIK')
        ->assertSee('6302000000000001')
        ->assertSee('No. Kartu Keluarga')
        ->assertSee('6302000000009999')
        ->assertSee('Laki-laki')
        ->assertSee('Agama')
        ->assertSee('Islam')
        ->assertSee('Ambakiang');
});

test('halaman detail tidak menampilkan Data Kampus, Data Orang Tua, maupun dokumen', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->mahasiswa->profile->update([
        'prodi_id' => $kampus->fakultas()->create(['nama' => 'Fakultas Teknik'])
            ->prodi()->create(['nama' => 'Teknik Informatika'])->id,
        'ipk' => 3.5,
        'semester' => 4,
        'ukt' => 3500000,
        'nama_ayah' => 'Ayah',
        'nik_ayah' => '6302000000000002',
    ]);

    actingAs($this->admin)
        ->get(route('admin.pengguna.lihat', $this->mahasiswa))
        ->assertOk()
        ->assertSee('Data Diri')
        ->assertDontSee('Data Kampus')
        ->assertDontSee('Data Orang Tua')
        ->assertDontSee('Dokumen Diri Sendiri')
        ->assertDontSee('Dokumen untuk Kampus')
        ->assertDontSee('Teknik Informatika')
        ->assertDontSee('6302000000000002');
});

test('super admin boleh membuka detail akun super admin lain tanpa tombol ubah', function () {
    $otherSuper = User::factory()->superAdmin()->create([
        'email' => 'super2@test.com',
    ]);

    actingAs($this->superAdmin)
        ->get(route('admin.pengguna.lihat', $otherSuper))
        ->assertOk()
        ->assertSee('Informasi Akun')
        ->assertSee('Super Admin')
        // Tombol Ubah tidak pernah muncul untuk target super_admin.
        ->assertDontSee('admin.pengguna.ubah');
});

test('kesra melihat tombol ubah untuk pengguna biasa', function () {
    actingAs($this->admin)
        ->get(route('admin.pengguna.lihat', $this->mahasiswa))
        ->assertOk()
        ->assertSee(route('admin.pengguna.ubah', $this->mahasiswa), false);
});

test('role tanpa grant admin.pengguna ditolak', function (string $role) {
    $viewer = match ($role) {
        'capil' => $this->capil,
        'user' => $this->mahasiswa,
    };

    actingAs($viewer)
        ->get(route('admin.pengguna.lihat', $this->admin))
        ->assertForbidden();
})->with(['capil', 'user']);

test('akun staf tanpa profil menampilkan catatan, bukan Data Diri kosong', function () {
    expect($this->admin->profile)->toBeNull();

    actingAs($this->admin)
        ->get(route('admin.pengguna.lihat', $this->admin))
        ->assertOk()
        ->assertSee('Informasi Akun')
        ->assertSee('tidak membuat data profil')
        ->assertDontSee('Nama Lengkap')
        ->assertDontSee('Data Diri')
        // Kartu Status Verifikasi juga tidak ada tanpa profil.
        ->assertDontSee('Status Verifikasi');
});

test('kolom kampus tampil untuk akun berperan kampus', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Kalimantan Selatan']);

    $userKampus = User::factory()->kampusAdmin()->create([
        'email' => 'kampus@test.com',
        'kampus_id' => $kampus->id,
    ]);

    actingAs($this->admin)
        ->get(route('admin.pengguna.lihat', $userKampus))
        ->assertOk()
        ->assertSee('Kampus')
        ->assertSee('Universitas Kalimantan Selatan');
});

test('daftar pengguna memuat aksi Lihat untuk setiap baris termasuk super admin', function () {
    $otherSuper = User::factory()->superAdmin()->create(['email' => 'super2@test.com']);

    actingAs($this->admin)
        ->get(route('admin.pengguna.index'))
        ->assertOk()
        ->assertSee(route('admin.pengguna.lihat', $this->mahasiswa), false)
        ->assertSee(route('admin.pengguna.lihat', $this->capil), false)
        ->assertSee(route('admin.pengguna.lihat', $otherSuper), false);
});
