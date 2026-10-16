<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pendaftar', 'detail', 'profil');

beforeEach(function () {
    seedAkses();

    $this->user = User::factory()->standardUser()->create(['email' => 'user@grouping.test']);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->fakultas = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $this->prodi = $this->fakultas->prodi()->create(['nama' => 'Teknik Informatika']);

    UserProfile::create([
        'user_id' => $this->user->id,
        'nama_lengkap' => 'Ahmad Grouping',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000000002',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01 RW 02',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $this->prodi->id,
        'ipk' => 3.5,
        'semester' => 5,
        'ukt' => 2500000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'kk_ikut_wali' => false,
        'nama_ayah' => 'Ayah Grouping',
        'nik_ayah' => '6302000000000003',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Grouping',
        'nik_ibu' => '6302000000000004',
        'pekerjaan_ibu' => 'Petani',
    ]);

    $this->applicant = Applicant::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'verifikasi',
    ]);
});

test('detail page groups data sesuai halaman profil', function () {
    $response = actingAs($this->user)
        ->get(route('user.pendaftaran.lihat', $this->applicant))
        ->assertOk();

    $response->assertSee('Data Diri', false)
        ->assertDontSee('Username', false)
        ->assertSee('Desil 3', false)
        ->assertSee('Data Kampus', false)
        ->assertSee('Universitas Lambung Mangkurat', false)
        ->assertSee('Fakultas Teknik', false)
        ->assertSee('Teknik Informatika', false)
        ->assertSee('Rp 2.500.000', false)
        ->assertSee('Ikut KK', false)
        ->assertSee('KK Ayah', false)
        ->assertSee('KK Ibu', false)
        ->assertSee('KK Wali', false)
        ->assertSee('id="kk-ayah" disabled checked', false)
        ->assertDontSee('id="kk-ibu" disabled checked', false)
        ->assertDontSee('id="kk-wali" disabled checked', false);

    $response->assertDontSee('Data Pendidikan', false)
        ->assertDontSee('Berdomisili dalam KK Wali', false);
});
