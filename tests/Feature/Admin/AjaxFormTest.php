<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('ajax');

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);

    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->admin = User::factory()->superAdmin()->create(['email' => 'admin@test.com']);
});

/**
 * @param  array<string, mixed>  $ubah
 * @return array<string, mixed>
 */
function payloadBeasiswa(Kampus $kampus, Prodi $prodi, array $ubah = []): array
{
    return array_merge([
        'nama' => 'Beasiswa Unggulan',
        'deskripsi' => 'Beasiswa untuk mahasiswa berprestasi.',
        'persyaratan' => 'IPK minimal 3.00 dan semester 3.',
        'kampus_id' => $kampus->id,
        'prodi_ids' => [$prodi->id],
        'tingkat_gelar' => 'S1',
        'kuota' => 30,
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'status' => 'aktif',
    ], $ubah);
}

/*
|--------------------------------------------------------------------------
| Bentuk balasan sukses
|--------------------------------------------------------------------------
*/

test('permintaan ajax menerima json sukses dengan tujuan navigasi', function () {
    $response = actingAs($this->admin)
        ->postJson(route('admin.beasiswa.simpan'), payloadBeasiswa($this->kampus, $this->prodi));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect', route('admin.beasiswa.index'))
        ->assertJsonStructure(['success', 'message', 'redirect']);

    expect($response->json('message'))->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('beasiswa', ['nama' => 'Beasiswa Unggulan']);
});

test('permintaan biasa tetap dialihkan dengan flash sukses', function () {
    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), payloadBeasiswa($this->kampus, $this->prodi))
        ->assertRedirect(route('admin.beasiswa.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('beasiswa', ['nama' => 'Beasiswa Unggulan']);
});

/*
|--------------------------------------------------------------------------
| Bentuk balasan gagal
|--------------------------------------------------------------------------
*/

test('validasi gagal lewat ajax dibalas 422 dengan peta per field', function () {
    $response = actingAs($this->admin)
        ->postJson(route('admin.beasiswa.simpan'), payloadBeasiswa($this->kampus, $this->prodi, [
            'nama' => '',
            'tanggal_selesai' => now()->subDay()->format('Y-m-d'),
        ]));

    $response->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['nama', 'tanggal_selesai']]);

    // Pesan per field tetap utuh supaya bisa ditempel persis di bawah inputnya.
    expect($response->json('errors.nama.0'))->toBeString()->not->toBeEmpty()
        ->and($response->json('errors.tanggal_selesai.0'))->toBe('Tanggal selesai harus sama atau setelah tanggal mulai');

    $this->assertDatabaseCount('beasiswa', 0);
});

test('validasi gagal lewat permintaan biasa kembali ke form dengan error', function () {
    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), payloadBeasiswa($this->kampus, $this->prodi, [
            'nama' => '',
            'kuota' => '',
        ]))
        ->assertSessionHasErrors(['nama', 'kuota']);
});

/*
|--------------------------------------------------------------------------
| Kegagalan dari logika bisnis
|--------------------------------------------------------------------------
*/

test('kegagalan bisnis lewat ajax dibalas success false dengan status 422', function () {
    $mahasiswa = User::factory()->standardUser()->create(['email' => 'mhs@test.com']);

    $beasiswa = Scholarship::factory()->create(['kampus_id' => $this->kampus->id]);

    // Pendaftaran yang sudah diputuskan Kesra tidak bisa dibatalkan mahasiswa.
    $applicant = Applicant::create([
        'user_id' => $mahasiswa->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'diterima',
    ]);

    $response = actingAs($mahasiswa)
        ->deleteJson(route('user.pendaftaran.batal', $applicant));

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('redirect', route('user.pendaftaran.index'))
        ->assertJsonStructure(['success', 'message', 'redirect']);

    expect($response->json('message'))->toBeString()->not->toBeEmpty();

    // Statusnya benar-benar tidak berubah.
    expect($applicant->refresh()->status)->toBe('diterima');
});

/*
|--------------------------------------------------------------------------
| Fallback Referer untuk `back()`
|--------------------------------------------------------------------------
*/

test('endpoint tanpa tujuan eksplisit memakai header referer sebagai tujuan', function () {
    $applicant = penerimaDiterima();

    $response = actingAs($this->admin)
        ->from(route('admin.penerima.index'))
        ->putJson(route('admin.penerima.tarik', $applicant));

    // Kalau tujuan tidak diberikan, `back()` diterjemahkan dari `Referer`.
    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect', route('admin.penerima.index'));
});

test('endpoint tanpa tujuan eksplisit dan tanpa referer mengembalikan null', function () {
    $applicant = penerimaDiterima();

    actingAs($this->admin)
        ->putJson(route('admin.penerima.tarik', $applicant))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect', null);
});

/**
 * Pendaftar berstatus `diterima` milik satu mahasiswa standar.
 */
function penerimaDiterima(): Applicant
{
    $mahasiswa = User::factory()->standardUser()->create(['email' => 'mhs-penerima@test.com']);
    $beasiswa = Scholarship::factory()->create(['kampus_id' => test()->kampus->id]);

    return Applicant::create([
        'user_id' => $mahasiswa->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'diterima',
    ]);
}

/*
|--------------------------------------------------------------------------
| Otorisasi tetap ditegakkan di backend
|--------------------------------------------------------------------------
*/

test('endpoint ajax tetap menolak akun yang tidak berhak', function () {
    $mahasiswa = User::factory()->standardUser()->create(['email' => 'mhs@test.com']);

    actingAs($mahasiswa)
        ->postJson(route('admin.beasiswa.simpan'), payloadBeasiswa($this->kampus, $this->prodi))
        ->assertForbidden();

    $this->assertDatabaseCount('beasiswa', 0);
});

/*
|--------------------------------------------------------------------------
| Penanda AJAX di halaman
|--------------------------------------------------------------------------
*/

test('form admin memakai penanda ajax agar submit tanpa reload', function (string $route) {
    actingAs($this->admin)->get($route)
        ->assertOk()
        ->assertSee('data-ajax-form', false);
})->with([
    'buat beasiswa' => [fn () => route('admin.beasiswa.buat')],
    // Halaman index hanya punya `data-ajax-form` di baris aksi per data
    // (`_aksi.blade.php`). Dulunya form "tandai notifikasi" di navbar
    // menutupi halaman kosong, tapi lonceng notifikasi sudah dihapus -- jadi
    // buat satu baris data agar penanda ajax tetap teruji di halaman ini.
    'daftar beasiswa' => [function () {
        Scholarship::factory()->create(['kampus_id' => test()->kampus->id]);

        return route('admin.beasiswa.index');
    }],
    'buat pengguna' => [fn () => route('admin.pengguna.buat')],
    'kelola role' => [fn () => route('admin.role.index')],
    'kelola menu' => [fn () => route('admin.menukelola.index')],
]);

test('halaman memuat helper ajax dan konfirmasi sweetalert', function () {
    $html = actingAs($this->admin)->get(route('admin.beasiswa.buat'))->assertOk()->getContent();

    expect($html)->toContain('custom.js')
        ->and($html)->toContain('Swal.fire');
});
