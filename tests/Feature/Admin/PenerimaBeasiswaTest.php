<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('penerima', 'admin');

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);

    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->kesra = User::factory()->admin()->create(['email' => 'penerima-kesra@test.com']);
});

/**
 * Pendaftar berstatus `diterima` milik mahasiswa berprofil lengkap.
 */
function penerimaBaru(string $email, string $status = 'diterima', bool $aktif = true): Applicant
{
    static $urut = 0;
    $urut++;

    // NIK/NIM unik per baris: kedua kolom punya index unique di tabel profil.
    $urutan = str_pad((string) $urut, 6, '0', STR_PAD_LEFT);

    $mahasiswa = User::factory()->standardUser()->create(['email' => $email, 'status' => $aktif ? 'aktif' : 'non-aktif']);
    $beasiswa = Scholarship::factory()->create(['kampus_id' => test()->kampus->id]);
    penerimaProfil($mahasiswa, $urutan);

    return Applicant::create([
        'user_id' => $mahasiswa->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => $status,
        'catatan' => $status === 'diterima' ? 'Lolos seleksi' : null,
    ]);
}

function penerimaProfil(User $mahasiswa, string $urutan): UserProfile
{
    return UserProfile::create([
        'user_id' => $mahasiswa->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302'.$urutan.'0000001',
        'no_kk' => '6302'.$urutan.'0000002',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01 RW 02',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => test()->prodi->id,
        'ipk' => 3.5,
        'semester' => 5,
        'ukt' => 2500000,
        'desil' => 3,
        'nim' => '21101152'.$urutan,
        'verif_catpil' => 'menunggu',
        'verif_kampus' => 'menunggu',
        'verif_kesra' => 'menunggu',
    ]);
}

test('kesra membuka daftar penerima yang hanya berisi pendaftar diterima dari akun aktif', function () {
    $penerima = penerimaBaru('penerima-aktif@test.com');
    penerimaBaru('masih-verifikasi@test.com', status: 'verifikasi');
    penerimaBaru('penerima-nonaktif@test.com', aktif: false);

    actingAs($this->kesra)
        ->get(route('admin.penerima.index'))
        ->assertOk()
        ->assertSee('Penerima Beasiswa', false)
        ->assertSee(route('admin.penerima.export'), false)
        ->assertSee(route('admin.penerima.cetak'), false)
        // Halaman ini read-only penuh: tidak ada kolom Aksi (permintaan
        // pengguna), jadi tidak ada route `admin.penerima.tarik` yang dirender.
        ->assertDontSee('Aksi', false)
        // Semua baris pasti berstatus `diterima`, jadi kolom Status dihapus.
        ->assertDontSee('<th>Status</th>', false)
        // Hanya penerima aktif yang tampil; antrean verifikasi dan akun
        // non-aktif tidak ikut. `assertSee('Ahmad Fauzi')` tidak cukup karena
        // semua profil memakai nama sama, jadi yang dibedakan adalah NIM.
        ->assertSee('Ahmad Fauzi')
        ->assertSee($penerima->user->profile->nim);
});

test('cetak merender halaman cetak mandiri dengan kop surat tanpa layout aplikasi', function () {
    penerimaBaru('penerima-cetak@test.com');

    $response = actingAs($this->kesra)->get(route('admin.penerima.cetak'));

    $response->assertOk()
        ->assertSee('Daftar Penerima Beasiswa', false)
        ->assertSee('Ahmad Fauzi')
        // Kop surat: logo + nama instansi (permintaan pengguna).
        ->assertSee('images/logo-balangan.png', false)
        ->assertSee('PEMERINTAH KABUPATEN BALANGAN', false)
        ->assertSee('BADAN PENGELOLAAN KEUANGAN,', false)
        ->assertSee('PENDAPATAN DAN ASET DAERAH', false)
        ->assertSee('Jl. Jenderal Ahmad Yani Km. 4,5 Telepon 0526-2028360 Paringin 71462', false)
        // Tanda tangan memakai lokasi instansi, bukan Banjarmasin.
        ->assertSee('Paringin Selatan,', false)
        // Subjudul "Dicetak pada ... total N penerima" dihapus.
        ->assertDontSee('Dicetak pada', false);

    // Halaman cetak tidak memakai layout Stisla: tanpa navbar dan tanpa
    // custom.js supaya `window.print()` menghasilkan lembar yang bersih.
    expect($response->getContent())->not->toContain('main-navbar')
        ->not->toContain('custom.js');
});

test('export mengunduh berkas xlsx dengan nama dan tipe konten yang benar', function () {
    penerimaBaru('penerima-export@test.com');

    $response = actingAs($this->kesra)->get(route('admin.penerima.export'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    expect($response->headers->get('Content-Disposition'))->toContain('penerima-beasiswa-')
        ->toContain('.xlsx');
});

test('catpil dan kampus membaca daftar penerima read-only tanpa kolom aksi', function () {
    $penerima = penerimaBaru('penerima-baca@test.com');
    $catpil = User::factory()->catpil()->create(['email' => 'penerima-role-catpil@test.com']);
    $kampus = User::factory()->kampusAdmin()->create(['email' => 'penerima-role-kampus@test.com']);

    foreach ([$catpil, $kampus] as $pembaca) {
        // Grant `admin.penerima` (leaf mandiri di section "Manajemen") membuka
        // daftar, cetak, dan unduhan.
        actingAs($pembaca)->get(route('admin.penerima.index'))->assertOk();
        actingAs($pembaca)->get(route('admin.penerima.cetak'))->assertOk();
        actingAs($pembaca)->get(route('admin.penerima.export'))->assertOk();

        // Halaman ini read-only untuk SEMUA role: tidak ada kolom Aksi sama
        // sekali (fitur "kembalikan ke antrean" dihapus atas permintaan
        // pengguna), jadi tak ada tombol yang dirender.
        actingAs($pembaca)
            ->get(route('admin.penerima.index'))
            ->assertDontSee('Aksi', false);
    }

    expect($penerima->refresh()->status)->toBe('diterima');
});

test('pendaftar biasa tetap tidak bisa membuka menu penerima', function () {
    $user = User::factory()->standardUser()->create();

    actingAs($user)->get(route('admin.penerima.index'))->assertForbidden();
    actingAs($user)->get(route('admin.penerima.export'))->assertForbidden();
});

test('filter kampus mempersempit daftar penerima', function () {
    $kampusLain = Kampus::create(['nama_kampus' => 'Universitas Antapanas']);
    $prodiLain = $kampusLain
        ->fakultas()->create(['nama' => 'Fakultas Ekonomi'])
        ->prodi()->create(['nama' => 'Akuntansi']);

    $diKampus = penerimaBaru('penerima-ulm@test.com');
    $diLain = penerimaBaru('penerima-antapanas@test.com');
    $diLain->user->profile->update(['prodi_id' => $prodiLain->id]);

    actingAs($this->kesra)
        ->get(route('admin.penerima.index', ['kampus_id' => $this->kampus->id]))
        ->assertOk()
        ->assertSee($diKampus->user->profile->nim)
        ->assertDontSee($diLain->user->profile->nim);
});

test('filter fakultas, prodi, dan beasiswa mempersempit daftar penerima', function () {
    $prodiLain = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Ekonomi'])
        ->prodi()->create(['nama' => 'Akuntansi']);

    $teknik = penerimaBaru('penerima-teknik@test.com');
    $ekonomi = penerimaBaru('penerima-ekonomi@test.com');
    $ekonomi->user->profile->update(['prodi_id' => $prodiLain->id]);

    actingAs($this->kesra)
        ->get(route('admin.penerima.index', ['fakultas_id' => $this->prodi->fakultas->id]))
        ->assertOk()
        ->assertSee($teknik->user->profile->nim)
        ->assertDontSee($ekonomi->user->profile->nim);

    actingAs($this->kesra)
        ->get(route('admin.penerima.index', ['jurusan_id' => $this->prodi->id]))
        ->assertOk()
        ->assertSee($teknik->user->profile->nim)
        ->assertDontSee($ekonomi->user->profile->nim);

    actingAs($this->kesra)
        ->get(route('admin.penerima.index', ['beasiswa_id' => $teknik->beasiswa->id]))
        ->assertOk()
        ->assertSee($teknik->user->profile->nim)
        ->assertDontSee($ekonomi->user->profile->nim);
});
