<?php

use App\Models\DokumenAkademik;
use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\JenjangPendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil', 'akademik');

beforeEach(function () {
    seedAkses();
    Storage::fake('public');

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->user = User::factory()->standardUser()->create(['email' => 'akademik@test.com']);
});

/*
|--------------------------------------------------------------------------
| Data pendidikan: jenjang, tahun masuk, kepala keluarga
|--------------------------------------------------------------------------
*/

test('form profil menampilkan dropdown jenjang dan semester dengan placeholder kosong', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->toContain('-- Pilih Jenjang --')
        ->toContain('-- Pilih Semester --')
        // Semester adalah dropdown, BUKAN input angka bebas.
        ->toMatch('/<select[^>]*id="semester"[^>]*>/')
        ->not->toMatch('/<input[^>]*id="semester"[^>]*type="number"/');

    // Semua jenjang D1..S3 tersedia sebagai pilihan.
    foreach (JenjangPendidikan::JENJANG as $jenjang) {
        expect($html)->toContain('>'.$jenjang.'</option>');
    }
});

test('baris KRS dan KHS mengikuti semester aktif yang dipilih', function () {
    actingAs($this->user)
        ->get(route('profile'))
        ->assertOk()
        // Tanpa semester terpilih, petunjuknya menunggu dan SEMUA baris
        // KRS/KHS nonaktif (input disabled) di render awal.
        ->assertSee('id="petunjuk-akademik"', false)
        ->assertSee('name="dokumen_akademik[krs][8][file]"', false)
        ->assertSee('disabled', false);
});

test('baris tersembunyi yang sudah diisi tetap terkirim, yang kosong tetap nonaktif', function () {
    // Isian user tidak boleh hilang diam-diam saat semester diturunkan:
    // input di luar batas yang SUDAH berisi berkas atau nilai tidak ikut
    // dinonaktifkan. Input kosong tetap nonaktif supaya semester di luar
    // batas tidak pernah sampai ke backend.
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->toContain('function aturInput($input, tampil) {')
        ->toContain("var adaIsi = (this.files && this.files.length > 0) || String(this.value).trim() !== '';")
        ->toContain("$(this).prop('disabled', !tampil && !adaIsi);");
});

test('KRS dan KHS dirender sebagai satu tabel per semester', function () {
    // Satu baris tabel memuat KRS dan KHS sekaligus, jadi halaman tidak
    // memanjang dua kali per semester (permintaan pengguna).
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->toContain('id="tabel-akademik"')
        ->toContain('Berkas KRS</th>')
        ->toContain('Berkas KHS</th>')
        ->toContain('Nilai KHS</th>')
        // Delapan baris semester, bukan enam belas blok.
        ->and(substr_count($html, 'class="baris-akademik"'))->toBe(8);
});

test('baris KRS hanya berisi unggah berkas, nilai hanya ada di baris KHS', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    // KRS adalah rencana studi dan tidak memuat nilai, jadi form TIDAK boleh
    // punya input nilai untuk KRS sama sekali (permintaan pengguna).
    expect($html)
        ->toContain('name="dokumen_akademik[krs][1][file]"')
        ->not->toContain('name="dokumen_akademik[krs][1][nilai]"')
        ->toContain('name="dokumen_akademik[khs][1][nilai]"')
        ->not->toContain('Nilai KRS Semester');
});

test('status kepala keluarga adalah opsi keempat pada radio Ikut KK', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    // Radio "Kepala Keluarga" bukan input boolean terpisah lagi, melainkan
    // OPSI KEEMPAT pada grup radio Ikut KK (Ayah/Ibu/Famili Lain/Kepala
    // Keluarga) di bagian Data Orang Tua & Famili Lain (permintaan pengguna).
    // Grupnya di bagian orang tua, setelah Data Kampus.
    expect($html)
        ->toContain('Data Orang Tua &amp; Famili Lain')
        ->toContain('name="ikut_kk"')
        ->toContain('value="kepala_keluarga"')
        // Radio terpisah yang lama sudah tidak ada lagi.
        ->not->toContain('kepala_keluarga_display');

    $bagianOrtu = substr($html, (int) strpos($html, 'Data Orang Tua &amp; Famili Lain'));
    $pilihan = ['ikut_kk_ayah', 'ikut_kk_ibu', 'ikut_kk_famili_lain', 'ikut_kk_kepala_keluarga'];

    foreach ($pilihan as $id) {
        expect(strpos($bagianOrtu, $id))->toBeGreaterThan(0);
    }

    // Empat radio satu grup (Ayah/Ibu/Famili Lain/Kepala Keluarga). Dihitung
    // dari id-nya supaya acuan `ikut_kk` di blok skrip bawah halaman tidak
    // ikut terhitung sebagai input.
    expect(substr_count($bagianOrtu, 'id="ikut_kk_'))->toBe(4);
});

test('memilih Kepala Keluarga pada Ikut KK menyimpan boolean kepala_keluarga', function () {
    // Opsi "Kepala Keluarga" di grup Ikut KK = status kepala keluarga YA.
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, ['ikut_kk' => 'kepala_keluarga']))
        ->assertSessionDoesntHaveErrors();

    $profil = UserProfile::where('user_id', $this->user->id)->first();

    expect($profil->ikut_kk)->toBe('kepala_keluarga')
        ->and($profil->kepala_keluarga)->toBeTrue();

    // Pilihan lain mengembalikan statusnya ke TIDAK -- nilai boolean ini
    // dihitung server dari `ikut_kk`, input klien `kepala_keluarga` ditimpa.
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'ikut_kk' => 'ayah',
            // Coba selundupkan nilai berlawanan: server WAJIB menimpanya.
            'kepala_keluarga' => 1,
        ]))
        ->assertSessionDoesntHaveErrors();

    $profil->refresh();

    expect($profil->ikut_kk)->toBe('ayah')
        ->and($profil->kepala_keluarga)->toBeFalse();
});

test('rentang semester mengikuti jenjang sesuai ketentuan', function () {
    $harapan = [
        'D1' => 2, 'D2' => 4, 'D3' => 6, 'D4' => 8,
        'S1' => 8, 'S2' => 4, 'S3' => 6,
    ];

    foreach ($harapan as $jenjang => $jumlah) {
        expect(JenjangPendidikan::jumlahSemester($jenjang))->toBe($jumlah)
            ->and(JenjangPendidikan::semesterOptions($jenjang))->toBe(range(1, $jumlah));
    }

    // Jenjang yang belum dipilih tidak punya semester sama sekali.
    expect(JenjangPendidikan::semesterOptions(null))->toBe([]);
});

test('semester yang tidak sah untuk jenjangnya ditolak backend', function (string $jenjang, int $semester) {
    // Dropdown di form sudah membatasi, tapi form bukan penjaga: request
    // manual harus tetap ditolak.
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'jenjang' => $jenjang,
            'semester' => $semester,
        ]))
        ->assertSessionHasErrors('semester');

    expect(UserProfile::where('user_id', $this->user->id)->exists())->toBeFalse();
})->with([
    'D1 semester 3' => ['D1', 3],
    'D2 semester 5' => ['D2', 5],
    'D3 semester 7' => ['D3', 7],
    'D4 semester 9' => ['D4', 9],
    'S1 semester 9' => ['S1', 9],
    'S2 semester 5' => ['S2', 5],
    'S3 semester 7' => ['S3', 7],
]);

test('semester batas atas tiap jenjang tetap diterima', function (string $jenjang, int $semester) {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'jenjang' => $jenjang,
            'semester' => $semester,
        ]))
        ->assertSessionDoesntHaveErrors();
})->with([
    'D1 semester 2' => ['D1', 2],
    'D3 semester 6' => ['D3', 6],
    'S1 semester 8' => ['S1', 8],
    'S2 semester 4' => ['S2', 4],
    'S3 semester 6' => ['S3', 6],
]);

test('jenjang dan tahun masuk divalidasi', function (array $ubah, string $field) {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, $ubah))
        ->assertSessionHasErrors($field);
})->with([
    'jenjang tidak dikenal' => [['jenjang' => 'D9'], 'jenjang'],
    'jenjang kosong' => [['jenjang' => ''], 'jenjang'],
    // Tahun masuk tidak boleh di masa depan.
    'tahun depan' => [['tahun_masuk' => (int) now()->addYear()->year], 'tahun_masuk'],
    'tahun kuno' => [['tahun_masuk' => 1800], 'tahun_masuk'],
    'tahun bukan angka' => [['tahun_masuk' => 'dua ribu'], 'tahun_masuk'],
    // `kepala_keluarga` tidak lagi divalidasi sebagai input: nilainya dihitung
    // server dari opsi `ikut_kk` (ditimpa di prepareForValidation), jadi
    // mengirim nilai sampah tidak menghasilkan pesan apa pun. Yang masih
    // divalidasi adalah pilihan grup radio-nya sendiri.
    'ikut kk tidak dikenal' => [['ikut_kk' => 'teman'], 'ikut_kk'],
]);

test('tahun masuk tahun berjalan tetap diterima', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'tahun_masuk' => (int) now()->year,
        ]))
        ->assertSessionDoesntHaveErrors();
});

test('jawaban Tidak pada kepala keluarga tersimpan sebagai false', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, ['kepala_keluarga' => 0]))
        ->assertSessionDoesntHaveErrors();

    $profil = UserProfile::where('user_id', $this->user->id)->first();

    expect($profil->kepala_keluarga)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| KRS & KHS per semester: KRS hanya berkas, KHS berkas + nilai
|--------------------------------------------------------------------------
*/

test('KRS dan KHS tersimpan sebagai baris terpisah, nilai hanya pada KHS', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => [
                'krs' => [3 => ['file' => pdfAkademik('krs-3.pdf')]],
                'khs' => [3 => ['file' => pdfAkademik('khs-3.pdf'), 'nilai' => 3.55]],
            ],
        ]))
        ->assertSessionDoesntHaveErrors();

    $krs = DokumenAkademik::where('user_id', $this->user->id)
        ->where('jenis', 'krs')->where('semester', 3)->first();
    $khs = DokumenAkademik::where('user_id', $this->user->id)
        ->where('jenis', 'khs')->where('semester', 3)->first();

    // Dua baris berbeda: nilai KHS tidak menempel ke baris KRS.
    expect($krs)->not->toBeNull()
        ->and($khs)->not->toBeNull()
        ->and($krs->id)->not->toBe($khs->id)
        ->and($krs->nilai)->toBeNull()
        ->and((float) $khs->nilai)->toBe(3.55)
        ->and($krs->semester)->toBe(3)
        ->and($khs->semester)->toBe(3);

    Storage::disk('public')->assertExists($krs->file);
    Storage::disk('public')->assertExists($khs->file);
});

test('nilai yang dikirim pada baris KRS diabaikan, bukan disimpan', function () {
    // KRS adalah rencana studi: tidak memuat nilai. Angka yang tetap dikirim
    // lewat request manual tidak boleh tersimpan, supaya halaman verifikasi
    // tidak menampilkan nilai palsu untuk KRS.
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => [
                'krs' => [2 => ['file' => pdfAkademik('krs-2.pdf'), 'nilai' => 3.90]],
            ],
        ]))
        ->assertSessionDoesntHaveErrors();

    $krs = DokumenAkademik::where('user_id', $this->user->id)->where('jenis', 'krs')->first();

    expect($krs)->not->toBeNull()
        ->and($krs->nilai)->toBeNull();
});

test('baris KRS tanpa berkas ditolak walau ada nilai', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => ['krs' => [2 => ['file' => null, 'nilai' => 3.25]]],
        ]))
        ->assertSessionHasErrors('dokumen_akademik.krs.2.file');

    expect(DokumenAkademik::where('user_id', $this->user->id)->exists())->toBeFalse();
});

test('unggahan semester baru tidak menimpa dokumen semester sebelumnya', function () {
    // Simpan seluruh kewajiban semester aktif beserta KHS semester 3.
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => ['khs' => [3 => ['file' => pdfAkademik('khs-3.pdf'), 'nilai' => 3.10]]],
        ]))
        ->assertSessionDoesntHaveErrors();

    $lama = DokumenAkademik::where('user_id', $this->user->id)
        ->where('jenis', 'khs')->where('semester', 3)->first();

    // Pada simpan berikutnya, kewajiban semester 1–2 dipenuhi oleh berkas yang
    // sudah tersimpan. Request hanya mengirim KHS semester 4, sehingga KHS
    // semester 3 tidak boleh ikut tertimpa.
    $payload = payloadAkademik($this->prodi->id);
    $payload['dokumen_akademik'] = [
        'khs' => [4 => ['file' => pdfAkademik('khs-4.pdf'), 'nilai' => 3.80]],
    ];

    actingAs($this->user)
        ->put(route('profile.update'), $payload)
        ->assertSessionDoesntHaveErrors();

    $semester3 = DokumenAkademik::where('user_id', $this->user->id)
        ->where('jenis', 'khs')->where('semester', 3)->first();
    $semester4 = DokumenAkademik::where('user_id', $this->user->id)
        ->where('jenis', 'khs')->where('semester', 4)->first();

    expect((float) $semester3->nilai)->toBe(3.10)
        ->and($semester3->file)->toBe($lama->file)
        ->and((float) $semester4->nilai)->toBe(3.80)
        ->and($semester4->file)->not->toBe($lama->file);
});

test('unggahan ulang semester yang sama memperbarui baris itu, bukan menambah', function () {
    // Unggahan pertama membuat berkas wajib. Unggahan kedua hanya mengubah
    // nilai semester yang sama; berkas tersimpan tetap memenuhi required.
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => ['khs' => [2 => ['nilai' => 3.10]]],
        ]))
        ->assertSessionDoesntHaveErrors();

    $payload = payloadAkademik($this->prodi->id);
    $payload['dokumen_akademik'] = ['khs' => [2 => ['nilai' => 3.90]]];

    actingAs($this->user)
        ->put(route('profile.update'), $payload)
        ->assertSessionDoesntHaveErrors();

    $baris = DokumenAkademik::where('user_id', $this->user->id)
        ->where('jenis', 'khs')->where('semester', 2)->get();

    expect($baris)->toHaveCount(1)
        ->and((float) $baris->first()->nilai)->toBe(3.90);
});

test('nilai KHS tanpa berkas wajib ditolak', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'semester' => 2,
            'dokumen_akademik' => [
                'khs' => [1 => ['file' => null, 'nilai' => 3.25]],
            ],
        ]))
        ->assertSessionHasErrors('dokumen_akademik.khs.1.file');

    expect(DokumenAkademik::where('user_id', $this->user->id)->exists())->toBeFalse();
});

test('semester di luar jenjang pada baris KRS/KHS ditolak', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'jenjang' => 'D1',
            'semester' => 2,
            'dokumen_akademik' => ['krs' => [5 => ['nilai' => 3.00]]],
        ]))
        ->assertSessionHasErrors('dokumen_akademik.krs.5');

    expect(DokumenAkademik::where('user_id', $this->user->id)->exists())->toBeFalse();
});

test('jenis dokumen akademik yang tidak dikenal ditolak', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => ['ijazah' => [1 => ['nilai' => 3.00]]],
        ]))
        ->assertSessionHasErrors('dokumen_akademik.ijazah');
});

test('nilai KHS di luar skala akademik ditolak', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => ['khs' => [1 => ['nilai' => 5]]],
        ]))
        ->assertSessionHasErrors('dokumen_akademik.khs.1.nilai');
});

test('berkas KRS/KHS yang bukan PDF ditolak', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => ['krs' => [1 => ['file' => UploadedFile::fake()->image('krs.jpg')]]],
        ]))
        ->assertSessionHasErrors('dokumen_akademik.krs.1.file');

    expect(DokumenAkademik::where('user_id', $this->user->id)->exists())->toBeFalse();
});

test('dokumen akademik tidak bisa dibaca mahasiswa lain', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => ['krs' => [1 => ['file' => pdfAkademik('krs-1.pdf'), 'nilai' => 3.00]]],
        ]))
        ->assertSessionDoesntHaveErrors();

    $path = DokumenAkademik::where('user_id', $this->user->id)->first()->file;

    $lain = User::factory()->standardUser()->create(['email' => 'lain@test.com']);

    actingAs($lain)->get(route('dokumen.show', $path))->assertForbidden();
});

test('dokumen akademik sendiri bisa dibaca pemiliknya', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadAkademik($this->prodi->id, [
            'dokumen_akademik' => ['krs' => [1 => ['file' => pdfAkademik('krs-1.pdf'), 'nilai' => 3.00]]],
        ]))
        ->assertSessionDoesntHaveErrors();

    $path = DokumenAkademik::where('user_id', $this->user->id)->first()->file;

    actingAs($this->user)->get(route('dokumen.show', $path))->assertOk();
});

/*
|--------------------------------------------------------------------------
| Helper khusus berkas uji di file ini
|--------------------------------------------------------------------------
*/

function pdfAkademik(string $nama): UploadedFile
{
    return UploadedFile::fake()->create($nama, 100, 'application/pdf');
}

/**
 * Payload profil lengkap dengan data pendidikan, siap ditimpa per kasus.
 */
function payloadAkademik(int $prodiId, array $ubah = []): array
{
    $semester = (int) ($ubah['semester'] ?? 3);
    $akademik = akademikProfilDummy($semester);

    // Tes yang menimpa satu semester tidak boleh menghapus berkas wajib
    // semester lain. `array_replace_recursive` mengganti hanya kunci yang
    // disebut tes, sehingga fixture tetap memenuhi kontrak KRS/KHS per
    // semester aktif. Tes kekosongan membuat payload mentah sendiri.
    if (isset($ubah['dokumen_akademik'])) {
        $akademik = array_replace_recursive($akademik, $ubah['dokumen_akademik']);
        unset($ubah['dokumen_akademik']);
    }

    return array_merge([
        'nama_lengkap' => 'Budi Akademik',
        'email' => 'akademik@test.com',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000000002',
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => 0,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01 RW 02',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'nama_kampus' => 'Universitas Lambung Mangkurat',
        'fakultas' => 'Fakultas Teknik',
        'prodi_id' => $prodiId,
        'ipk' => 3.5,
        'semester' => 3,
        'ukt' => 2500000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'Ayah Budi',
        'nik_ayah' => '6302000000000003',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Budi',
        'nik_ibu' => '6302000000000004',
        'pekerjaan_ibu' => 'Petani',
        'dokumen_akademik' => $akademik,
    ], berkasProfilDummy(), $ubah);
}
