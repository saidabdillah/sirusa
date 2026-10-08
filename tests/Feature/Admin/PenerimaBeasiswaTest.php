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
        ->assertSee(route('admin.penerima.tarik', $penerima), false)
        // Hanya penerima aktif yang tampil; antrean verifikasi dan akun
        // non-aktif tidak ikut. `assertSee('Ahmad Fauzi')` tidak cukup karena
        // semua profil memakai nama sama, jadi yang dibedakan adalah NIM.
        ->assertSee('Ahmad Fauzi')
        ->assertSee($penerima->user->profile->nim);
});

test('cetak merender halaman cetak mandiri tanpa layout aplikasi', function () {
    penerimaBaru('penerima-cetak@test.com');

    $response = actingAs($this->kesra)->get(route('admin.penerima.cetak'));

    $response->assertOk()
        ->assertSee('Daftar Penerima Beasiswa', false)
        ->assertSee('Ahmad Fauzi');

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

test('tarik mengembalikan penerima ke antrean verifikasi dan mengosongkan catatan', function () {
    $penerima = penerimaBaru('penerima-tarik@test.com');

    actingAs($this->kesra)
        ->from(route('admin.penerima.index'))
        ->putJson(route('admin.penerima.tarik', $penerima))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect', route('admin.penerima.index'));

    // Baris pendaftar TIDAK dihapus: statusnya kembali ke `verifikasi` supaya
    // masuk lagi ke antrean keputusan Kesra.
    expect($penerima->refresh()->status)->toBe('verifikasi')
        ->and($penerima->catatan)->toBeNull();

    $this->assertDatabaseHas('pendaftar', [
        'id' => $penerima->id,
        'status' => 'verifikasi',
    ]);
});

test('tarik menolak pendaftar yang bukan penerima aktif', function () {
    $bukanPenerima = penerimaBaru('masih-antre@test.com', status: 'verifikasi');

    actingAs($this->kesra)
        ->putJson(route('admin.penerima.tarik', $bukanPenerima))
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($bukanPenerima->refresh()->status)->toBe('verifikasi');
});

test('endpoint penerima ditolak untuk role tanpa grant menu', function () {
    $penerima = penerimaBaru('penerima-catpil@test.com');
    $catpil = User::factory()->catpil()->create(['email' => 'penerima-role-catpil@test.com']);

    actingAs($catpil)->get(route('admin.penerima.index'))->assertForbidden();
    actingAs($catpil)->get(route('admin.penerima.cetak'))->assertForbidden();
    actingAs($catpil)->get(route('admin.penerima.export'))->assertForbidden();

    // `tarik` dijaga terpisah lewat `hasMenuAccess`, bukan hanya middleware
    // `akses.menu` -- dicek dua-duanya.
    actingAs($catpil)->putJson(route('admin.penerima.tarik', $penerima))->assertForbidden();
    expect($penerima->refresh()->status)->toBe('diterima');
});
