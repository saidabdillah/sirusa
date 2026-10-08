<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('dashboard', 'admin');

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->catpilAdmin = User::factory()->catpil()->create(['email' => 'catpil@test.com']);
    $this->kampusAdmin = User::factory()->kampusAdmin()->create(['email' => 'kampus@test.com']);
    $this->kesraAdmin = User::factory()->admin()->create(['email' => 'kesra@test.com']);
    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'super@test.com']);
});

/**
 * Mahasiswa dengan profil pada tahap verifikasi tertentu.
 */
function mahasiswaTahap(string $tahap, string $nama): User
{
    $stages = [
        'catpil' => ['verif_catpil' => 'menunggu', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kampus' => ['verif_catpil' => 'setuju', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kesra' => ['verif_catpil' => 'setuju', 'verif_kampus' => 'setuju', 'verif_kesra' => 'menunggu'],
    ];

    $user = User::factory()->standardUser()->create();
    $user->profile()->create(array_merge([
        'nama_lengkap' => $nama,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => test()->prodi->id,
        'ipk' => 3.5,
        'semester' => 4,
    ], $stages[$tahap]));

    // Tahap kampus baru masuk antrean setelah mahasiswa mendaftar beasiswa, jadi
    // fixture-nya perlu satu pendaftaran. Beasisanya dibuat sekali lalu dipakai
    // ulang supaya indeks unik `[user_id, beasiswa_id]` tidak bermasalah.
    if ($tahap === 'kampus') {
        $beasiswa = Scholarship::query()->where('nama', 'Beasiswa Antrean')->first()
            ?: Scholarship::factory()->create([
                'nama' => 'Beasiswa Antrean',
                'kampus_id' => test()->kampus->id,
                'kampus' => test()->kampus->nama_kampus,
                'ipk_minimal' => 0,
                'semester_minimal' => 0,
                'status' => 'aktif',
                'tanggal_mulai' => now()->subDay(),
                'tanggal_selesai' => now()->addMonth(),
            ]);

        Applicant::create([
            'user_id' => $user->id,
            'beasiswa_id' => $beasiswa->id,
            'status' => 'verifikasi',
        ]);
    }

    return $user;
}

test('catpil sees the catpil queue dashboard, not the student dashboard', function () {
    mahasiswaTahap('catpil', 'Ani Catpil');

    actingAs($this->catpilAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dasbor Catpil')
        ->assertSee('Menunggu Tindakan')
        ->assertSee('Ani Catpil');
});

test('kampus admin sees the kampus queue dashboard', function () {
    mahasiswaTahap('kampus', 'Budi Kampus');

    actingAs($this->kampusAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dasbor Kampus')
        ->assertSee('Budi Kampus');
});

test('kesra sees the kesra dashboard with the pending registration queue', function () {
    $user = mahasiswaTahap('kesra', 'Citra Kesra');
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_selesai' => now()->addMonth(),
    ]);
    Applicant::factory()->create(['user_id' => $user->id, 'beasiswa_id' => $beasiswa->id]);

    actingAs($this->kesraAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dasbor Kesra')
        ->assertSee('Pendaftaran Menunggu Putusan')
        ->assertSee('Citra Kesra');
});

test('super admin sees the cross-stage overview', function () {
    mahasiswaTahap('catpil', 'Ani Catpil');
    mahasiswaTahap('kampus', 'Budi Kampus');
    mahasiswaTahap('kesra', 'Citra Kesra');

    actingAs($this->superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Sebaran Antrean per Tahap')
        ->assertSee('Alur Penerimaan')
        ->assertSee('Verifikasi Catpil')
        ->assertSee('Verifikasi Kampus')
        ->assertSee('Verifikasi Kesra');
});

test('student sees the student dashboard', function () {
    $user = mahasiswaTahap('catpil', 'Dewi Mahasiswa');

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Status Profil Saya')
        ->assertSee('Dewi Mahasiswa')
        ->assertSee('Beasiswa Tersedia');
});

test('stage dashboard count matches the stage queue the admin actually opens', function () {
    mahasiswaTahap('catpil', 'Ani Catpil');
    mahasiswaTahap('catpil', 'Budi Catpil');
    // Sudah terverifikasi penuh, tidak ada yang perlu ditinjau di tahap manapun.
    $selesai = mahasiswaTahap('kesra', 'Citra Sudah Selesai');
    $selesai->profile->update(['verif_kesra' => 'setuju']);

    $response = actingAs($this->catpilAdmin)->get(route('dashboard'))->assertOk();

    expect($response->viewData('ringkasan')['menunggu'])->toBe(2);

    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.index'))
        ->assertOk()
        ->assertSee('Ani Catpil')
        ->assertSee('Budi Catpil')
        ->assertDontSee('Citra Sudah Selesai');
});

test('campus admin dashboard is scoped to its own campus', function () {
    $user = mahasiswaTahap('kampus', 'Ani Kampus A');
    $user->profile->update(['prodi_id' => null]);

    $prodiLain = Kampus::create(['nama_kampus' => 'Kampus B'])
        ->fakultas()->create(['nama' => 'Fakultas B'])
        ->prodi()->create(['nama' => 'Akuntansi']);
    $user->profile->update(['prodi_id' => $prodiLain->id]);

    $this->kampusAdmin->update(['kampus_id' => $this->kampus->id]);

    actingAs($this->kampusAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Ani Kampus A');
});

test('mahasiswa dashboard shows the blocking application', function () {
    $user = mahasiswaTahap('kesra', 'Dewi Pendaftar');
    $user->profile->update([
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);

    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_selesai' => now()->addMonth(),
    ]);
    Applicant::factory()->create(['user_id' => $user->id, 'beasiswa_id' => $beasiswa->id]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pendaftaran Berjalan')
        ->assertSee($beasiswa->nama);
});
