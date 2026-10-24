<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('admin', 'verifikasi');

beforeEach(function () {
    seedAkses();
    Storage::fake('public');

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->catpil = User::factory()->catpil()->create(['email' => 'catpil@test.com']);
    $this->kampusAdmin = User::factory()->kampusAdmin()->create(['email' => 'kampus@test.com']);
    $this->kesra = User::factory()->admin()->create(['email' => 'kesra@test.com']);

    $this->beasiswa = Scholarship::factory()->create([
        'kuota' => 10,
        'ipk_minimal' => 0,
        'semester_minimal' => 0,
        'status' => 'aktif',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addMonth(),
    ]);
});

/**
 * Mahasiswa dengan pendaftaran dan status verifikasi yang bisa diatur.
 */
function mahasiswaVerifikasi(Kampus $kampus, int $prodiId, array $verif): User
{
    static $n = 0;
    $n++;

    $user = User::factory()->standardUser()->create(['email' => "verif{$n}@test.com"]);

    UserProfile::create(array_merge([
        'user_id' => $user->id,
        'nama_lengkap' => "Mahasiswa Verifikasi {$n}",
        'nik' => '630200000000'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'no_kk' => '630200000001'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'nim' => '2010'.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => false,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $prodiId,
        'ipk' => 3.5,
        'semester' => 3,
        'ukt' => 2500000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'Ayah',
        'nik_ayah' => '630200000002'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu',
        'nik_ibu' => '630200000003'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
        'pekerjaan_ibu' => 'Petani',
        'foto_profil' => "profil/{$user->id}/foto.jpg",
        'dokumen_ktp' => "profil/{$user->id}/ktp.pdf",
        'dokumen_kk' => "profil/{$user->id}/kk.pdf",
        'dokumen_desil' => "profil/{$user->id}/desil.pdf",
        'dokumen_pddikti' => "profil/{$user->id}/pddikti.pdf",
        'dokumen_surat_aktif' => "profil/{$user->id}/aktif.pdf",
        'dokumen_surat_pernyataan' => "profil/{$user->id}/pernyataan.pdf",
        'dokumen_bukti_ukt' => "profil/{$user->id}/ukt.pdf",
        'ktp_ayah' => "profil/{$user->id}/ktp-ayah.pdf",
        'ktp_ibu' => "profil/{$user->id}/ktp-ibu.pdf",
    ], $verif));

    Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => Scholarship::query()->firstOrFail()->id,
        'status' => Applicant::PENDING_STATUS,
        'fakultas' => 'Fakultas Teknik',
        'prodi' => 'Teknik Informatika',
        'ipk' => 3.5,
        'semester' => 3,
    ]);

    return $user;
}

/*
|--------------------------------------------------------------------------
| Gerbang antrean Kesra: Catpil DAN Kampus harus setuju
|--------------------------------------------------------------------------
*/

test('Catpil belum setuju: tidak muncul di antrean Kesra', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'menunggu',
        'verif_kampus' => 'setuju',
    ]);

    actingAs($this->kesra)->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertDontSee($user->profile->nama_lengkap, false);

    actingAs($this->kesra)->get(route('admin.kesra.lihat', $user->id))->assertForbidden();
});

test('Kampus belum setuju: tidak muncul di antrean Kesra', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'menunggu',
    ]);

    actingAs($this->kesra)->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertDontSee($user->profile->nama_lengkap, false);

    actingAs($this->kesra)->get(route('admin.kesra.lihat', $user->id))->assertForbidden();
});

test('Catpil minta perbaikan: belum masuk antrean Kesra', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'revisi',
        'verif_kampus' => 'setuju',
    ]);

    actingAs($this->kesra)->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertDontSee($user->profile->nama_lengkap, false);
});

test('Kampus menolak: belum masuk antrean Kesra', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'tolak',
    ]);

    actingAs($this->kesra)->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertDontSee($user->profile->nama_lengkap, false);
});

test('keduanya setuju: masuk antrean Kesra dan bisa dibuka', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
    ]);

    actingAs($this->kesra)->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertSee($user->profile->nama_lengkap, false);

    actingAs($this->kesra)->get(route('admin.kesra.lihat', $user->id))->assertOk();
});

test('keputusan Kesra lewat request manual tetap ditolak selama gerbang belum lolos', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'menunggu',
        'verif_kampus' => 'setuju',
    ]);

    $applicant = Applicant::where('user_id', $user->id)->firstOrFail();

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user->id, $applicant->id]), [
            'pendaftaran_status' => 'diterima',
        ])
        ->assertForbidden();

    // Statusnya tidak berubah: permohonan tidak diam-diam diterima.
    expect($applicant->refresh()->status)->toBe(Applicant::PENDING_STATUS);
});

test('keputusan Kesra diterima setelah kedua gerbang lolos', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
    ]);

    $applicant = Applicant::where('user_id', $user->id)->firstOrFail();

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user->id, $applicant->id]), [
            'pendaftaran_status' => 'diterima',
        ])
        ->assertRedirect(route('admin.kesra.lihat', $user->id));

    expect($applicant->refresh()->status)->toBe('diterima');
});

/*
|--------------------------------------------------------------------------
| Tanggal berdomisili diisi di tahap Catpil
|--------------------------------------------------------------------------
*/

test('Setuju di Catpil tanpa tanggal berdomisili ditolak', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, ['verif_catpil' => 'menunggu']);

    actingAs($this->catpil)
        ->put(route('admin.catpil.verifikasi', $user->id), ['status' => 'setuju'])
        ->assertSessionHasErrors([
            'berdomisili_sejak' => 'Tanggal berdomisili harus diisi ketika memilih "Setujui".',
        ]);

    expect($user->refresh()->profile->verif_catpil)->toBe('menunggu');
});

test('tanggal berdomisili di masa depan ditolak', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, ['verif_catpil' => 'menunggu']);

    actingAs($this->catpil)
        ->put(route('admin.catpil.verifikasi', $user->id), [
            'status' => 'setuju',
            'berdomisili_sejak' => now()->addDay()->toDateString(),
        ])
        ->assertSessionHasErrors([
            'berdomisili_sejak' => 'Tanggal berdomisili tidak boleh di masa depan.',
        ]);
});

test('Setuju di Catpil menyimpan tanggal berdomisili', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, ['verif_catpil' => 'menunggu']);

    actingAs($this->catpil)
        ->put(route('admin.catpil.verifikasi', $user->id), [
            'status' => 'setuju',
            'berdomisili_sejak' => '2019-06-15',
        ])
        ->assertRedirect(route('admin.catpil.index'));

    $profil = $user->refresh()->profile;

    expect($profil->verif_catpil)->toBe('setuju')
        ->and($profil->berdomisili_sejak->toDateString())->toBe('2019-06-15');
});

test('keputusan selain Setuju tidak mewajibkan tanggal berdomisili', function (string $status) {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, ['verif_catpil' => 'menunggu']);

    $payload = ['status' => $status];

    if ($status === 'revisi') {
        $payload['catatan'] = 'Perlu diperbaiki';
    }

    actingAs($this->catpil)
        ->put(route('admin.catpil.verifikasi', $user->id), $payload)
        ->assertSessionDoesntHaveErrors(['berdomisili_sejak']);

    expect($user->refresh()->profile->verif_catpil)->toBe($status);
})->with(['revisi', 'tolak']);

test('tahap Kampus tidak pernah meminta tanggal berdomisili', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, ['verif_kampus' => 'menunggu']);

    actingAs($this->kampusAdmin)
        ->put(route('admin.kampusverif.verifikasi', $user->id), ['status' => 'setuju'])
        ->assertSessionDoesntHaveErrors(['berdomisili_sejak']);

    expect($user->refresh()->profile->verif_kampus)->toBe('setuju');
});

test('tanggal berdomisili dikosongkan saat keputusan Catpil tidak lagi Setuju', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, ['verif_catpil' => 'setuju']);

    $user->profile->update(['berdomisili_sejak' => '2019-06-15']);

    actingAs($this->catpil)
        ->put(route('admin.catpil.verifikasi', $user->id), [
            'status' => 'tolak',
            'catatan' => 'Domisili tidak sesuai',
        ])
        ->assertRedirect(route('admin.catpil.index'));

    expect($user->refresh()->profile->berdomisili_sejak)->toBeNull();
});

test('field berdomisili tampil di halaman Catpil saja', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'menunggu',
        'verif_kampus' => 'menunggu',
    ]);

    actingAs($this->catpil)->get(route('admin.catpil.lihat', $user->id))
        ->assertOk()
        ->assertSee('Terhitung sejak tanggal');

    actingAs($this->kampusAdmin)->get(route('admin.kampusverif.lihat', $user->id))
        ->assertOk()
        ->assertDontSee('Terhitung sejak tanggal');
});

/*
|--------------------------------------------------------------------------
| Lama berdomisili di antrean Kesra
|--------------------------------------------------------------------------
*/

test('lama berdomisili dihitung dari tanggal dan ditampilkan di antrean Kesra', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'berdomisili_sejak' => now()->subYears(2)->subMonths(3)->toDateString(),
    ]);

    actingAs($this->kesra)->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertSee('Lama Berdomisili')
        ->assertSee('2 tahun 3 bulan');
});

test('lama berdomisili menampilkan Belum ditentukan tanpa tanggal', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'berdomisili_sejak' => null,
    ]);

    expect($user->profile->lamaBerdomisiliLabel())->toBe('Belum ditentukan');

    actingAs($this->kesra)->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertSee('Belum ditentukan');
});

test('lama berdomisili dihitung kalender, bukan jumlah hari dibagi 30', function () {
    // 1 Januari 2020 -> 1 Maret 2021 = 1 tahun 2 bulan. Cara "selisih hari / 30"
    // menghasilkan angka yang berbeda, jadi tes ini mengunci perhitungannya.
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'berdomisili_sejak' => now()->subMonths(14)->startOfMonth()->toDateString(),
    ]);

    expect($user->profile->lamaBerdomisili())->toBe('1 tahun 2 bulan');
});

test('lama berdomisili tidak pernah negatif walau tanggalnya di masa depan', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, ['verif_catpil' => 'setuju']);

    $user->profile->update(['berdomisili_sejak' => now()->addMonth()->toDateString()]);

    expect($user->refresh()->profile->lamaBerdomisili())->toBeNull()
        ->and($user->profile->lamaBerdomisiliLabel())->toBe('Belum ditentukan');
});

test('tanggal mulai domisili tampil di detail agar sumber hitungan bisa diperiksa', function () {
    $user = mahasiswaVerifikasi($this->kampus, $this->prodi->id, [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'berdomisili_sejak' => '2019-06-15',
    ]);

    actingAs($this->kesra)->get(route('admin.kesra.lihat', $user->id))
        ->assertOk()
        ->assertSee('Terhitung Sejak')
        ->assertSee('15 Juni 2019');
});
