<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('beasiswa', 'applicant', 'admin');

function mhsBeasiswa(?User $user = null, ?Prodi $prodi = null, array $verif = []): User
{
    $user ??= User::factory()->standardUser()->create();
    $prodi ??= test()->prodi;

    $suffix = str_pad((string) ($user->id + 100), 6, '0', STR_PAD_LEFT);

    UserProfile::create(array_merge([
        'user_id' => $user->id,
        'nama_lengkap' => 'Beasiswa '.($user->id + 1),
        'nik' => '63020000'.$suffix,
        'no_kk' => '63020001'.$suffix,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $prodi?->id,
        'ipk' => 3.5,
        'semester' => 5,
        'ukt' => 2500000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'Ayah '.($user->id + 1),
        'nik_ayah' => '63020002'.$suffix,
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu '.($user->id + 1),
        'nik_ibu' => '63020003'.$suffix,
        'pekerjaan_ibu' => 'Petani',
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        'dokumen_sktm' => 'profil/1/sktm.pdf',
        'dokumen_transkrip' => 'profil/1/transkrip.pdf',
        'dokumen_surat_aktif' => 'profil/1/surat_aktif.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp_ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp_ibu.pdf',
    ], $verif));

    return $user;
}

function beasiswaTersedia(int $kampusId, array $extra = []): Scholarship
{
    return Scholarship::factory()->create(array_merge([
        'kampus_id' => $kampusId,
        'ipk_minimal' => 0,
        'semester_minimal' => 0,
        'status' => 'aktif',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addMonths(2),
    ], $extra));
}

function sudahTerverifikasi(User $user, Prodi $prodi): User
{
    mhsBeasiswa($user, $prodi, [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);

    return $user;
}

/**
 * Beasiswa aktif dengan satu fakultas dan satu prodi di dalamnya.
 *
 * Dipakai oleh test query count: cakupan yang benar-benar terisi memastikan
 * `cakupanLabel()` dan daftar prodi ikut teruji, bukan hanya cabang kosong.
 */
function beasiswaDenganCakupan(string $nama, ?int $kampusId = null): Scholarship
{
    $beasiswa = beasiswaTersedia($kampusId ?? test()->kampus->id, ['nama' => $nama]);

    $beasiswa->fakultas()->create(['nama' => 'Fakultas '.$nama])
        ->prodi()->create(['nama' => 'Prodi '.$nama]);

    return $beasiswa;
}

/**
 * Penghitung query dengan reset manual.
 *
 * Query setup (insert fakultas/prodi) ikut tercatat, padahal yang ingin
 * diukur hanya query saat halaman dirender. Karena itu `reset()` dipanggil
 * setelah data siap dan sebelum GET, supaya yang dibandingkan murni biaya
 * render. Tanpa itu, selisihnya tergeser oleh jumlah baris yang disisipkan.
 */
function penghitungQuery(): object
{
    $hitung = new class
    {
        private int $jumlah = 0;

        public function reset(): self
        {
            $this->jumlah = 0;

            return $this;
        }

        public function jumlah(): int
        {
            return $this->jumlah;
        }

        public function catat(): void
        {
            $this->jumlah++;
        }
    };

    DB::listen(fn () => $hitung->catat());

    return $hitung;
}

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->prodi = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik'])->prodi()->create(['nama' => 'Teknik Informatika']);
    $this->kampusLain = Kampus::create(['nama_kampus' => 'Universitas Ranking Dua']);
    $this->prodiLain = $this->kampusLain->fakultas()->create(['nama' => 'Fakultas Ekonomi'])->prodi()->create(['nama' => 'Akuntansi']);
    $this->mahasiswa = User::factory()->standardUser()->create(['email' => 'mhs@test.com']);
    $this->kesra = User::factory()->admin()->create(['email' => 'kesra@test.com']);
    $this->kapil = User::factory()->catpil()->create(['email' => 'catpil@test.com']);
});

/*
|--------------------------------------------------------------------------
| Batas satu pendaftaran per mahasiswa
|--------------------------------------------------------------------------
*/

test('mahasiswa tidak bisa mendaftar beasiswa kedua saat masih ada yang diproses', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);

    $pertama = beasiswaTersedia($this->kampus->id);
    $kedua = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $pertama->id])
        ->assertRedirect(route('user.pendaftaran.index'));

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $kedua->id])
        ->assertRedirect(route('user.beasiswa.lihat', $kedua))
        ->assertSessionHas('error');

    $this->assertDatabaseCount('pendaftar', 1);
    $this->assertDatabaseMissing('pendaftar', ['beasiswa_id' => $kedua->id]);
});

test('mahasiswa yang sudah ditolak boleh mendaftar lagi', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);

    $pertama = beasiswaTersedia($this->kampus->id);
    $kedua = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $pertama->id]);
    Applicant::where('beasiswa_id', $pertama->id)->update(['status' => 'ditolak']);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $kedua->id])
        ->assertRedirect(route('user.pendaftaran.index'));

    $this->assertDatabaseHas('pendaftar', ['beasiswa_id' => $kedua->id, 'status' => 'verifikasi']);
});

test('mahasiswa tidak bisa mendaftar lagi setelah diterima', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);

    $pertama = beasiswaTersedia($this->kampus->id);
    $kedua = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $pertama->id]);
    Applicant::where('beasiswa_id', $pertama->id)->update(['status' => 'diterima']);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $kedua->id])
        ->assertRedirect(route('user.beasiswa.lihat', $kedua))
        ->assertSessionHas('error');

    $this->assertDatabaseCount('pendaftar', 1);
});

/*
|--------------------------------------------------------------------------
| Pembatalan oleh mahasiswa
|--------------------------------------------------------------------------
*/

test('mahasiswa bisa membatalkan pendaftaran yang masih diproses', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id]);
    $applicant = Applicant::firstOrFail();

    actingAs($this->mahasiswa)->delete(route('user.pendaftaran.batal', $applicant))
        ->assertRedirect(route('user.pendaftaran.index'))
        ->assertSessionHas('success');

    expect($applicant->refresh()->status)->toBe('dibatalkan')
        ->and($this->mahasiswa->refresh()->canRegisterForScholarship())->toBeTrue();
});

test('mahasiswa bisa mendaftar lagi dengan beasiswa yang sama setelah membatalkan', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id]);
    $applicant = Applicant::firstOrFail();
    actingAs($this->mahasiswa)->delete(route('user.pendaftaran.batal', $applicant));

    $beasiswa->update(['tanggal_selesai' => now()->addMonths(2)]);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id])
        ->assertRedirect(route('user.pendaftaran.index'));

    // Baris yang sama dihidupkan ulang, bukan baris baru: index unik
    // [user_id, beasiswa_id] akan menolak kalau dibuat dua.
    $this->assertDatabaseCount('pendaftar', 1);
    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('pendaftaran yang sudah diputuskan tidak bisa dibatalkan', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id]);
    $applicant = Applicant::firstOrFail();
    $applicant->update(['status' => 'diterima']);

    actingAs($this->mahasiswa)->delete(route('user.pendaftaran.batal', $applicant))
        ->assertRedirect(route('user.pendaftaran.index'))
        ->assertSessionHas('error');

    expect($applicant->refresh()->status)->toBe('diterima');
});

test('mahasiswa tidak bisa membatalkan pendaftaran orang lain', function () {
    $orangLain = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    Applicant::create([
        'user_id' => $orangLain->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->mahasiswa)->delete(route('user.pendaftaran.batal', Applicant::firstOrFail()))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Batas pembatalan: masa pendaftaran beasiswa
|--------------------------------------------------------------------------
|
| `canBeCancelled()` memakai `beasiswa.tanggal_selesai`, batas yang sama
| dengan `scopeTersedia()`. Test ini mengunci aturan itu supaya tombol di
| halaman detail dan penolakan endpoint tidak pernah berbeda pendapat.
|
*/

test('pembatalan hanya boleh selama masa pendaftaran beasiswa masih terbuka', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    // Daftar dulu saat masa pendaftaran masih terbuka, baru tutup masanya.
    // Urutan ini penting: jendela ditutup SETELAH pendaftaran. Kalau dibalik,
    // `store()` sendiri yang menolak karena beasiswa sudah tidak menerima
    // pendaftar, sehingga skenario yang diuji tidak pernah terjadi.
    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id]);
    $applicant = Applicant::firstOrFail();
    $beasiswa->update(['tanggal_selesai' => now()->subDay()]);

    expect($applicant->canBeCancelled())->toBeFalse()
        ->and($applicant->cancellationBlockedReason())->toContain('sudah ditutup');

    actingAs($this->mahasiswa)->delete(route('user.pendaftaran.batal', $applicant))
        ->assertRedirect(route('user.pendaftaran.index'))
        ->assertSessionHas('error');

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('pendaftaran yang masa pendaftaran sudah ditutup ditolak dengan alasan yang jelas', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id]);
    $applicant = Applicant::firstOrFail();
    $beasiswa->update(['tanggal_selesai' => now()->subDay()]);

    // Dipanggil langsung ke endpoint, bukan lewat tombol yang disembunyikan.
    // Penyembunyian tombol UI tidak pernah menggantikan validasi server.
    actingAs($this->mahasiswa)
        ->deleteJson(route('user.pendaftaran.batal', $applicant))
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Masa pendaftaran beasiswa ini sudah ditutup sehingga tidak bisa dibatalkan lagi.',
        ]);

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('pembatalan kedua ditolak', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id]);
    $applicant = Applicant::firstOrFail();

    actingAs($this->mahasiswa)->delete(route('user.pendaftaran.batal', $applicant));

    expect($applicant->refresh()->canBeCancelled())->toBeFalse()
        ->and($applicant->cancellationBlockedReason())->toBe('Pendaftaran ini sudah dibatalkan.');

    actingAs($this->mahasiswa)->delete(route('user.pendaftaran.batal', $applicant))
        ->assertRedirect(route('user.pendaftaran.index'))
        ->assertSessionHas('error');

    expect($applicant->refresh()->status)->toBe('dibatalkan');
});

test('beasiswa tanpa tanggal selesai dianggap sudah lewat batas', function () {
    $beasiswa = new Scholarship;
    $beasiswa->tanggal_selesai = null;

    $applicant = Applicant::make(['status' => 'verifikasi']);
    $applicant->setRelation('beasiswa', $beasiswa);

    expect($applicant->canBeCancelled())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Pembatalan hanya di halaman detail
|--------------------------------------------------------------------------
|
| Tombol dibatalkan dari tabel karena tabel memuat banyak baris sekaligus:
| dari sana, satu klik "Batalkan" berarti eyebrow "ya, batalkan ini" untuk
| pendaftaran yang tidak terlihat judulnya. Detail Pendaftaran memberi konteks
| nama beasiswa dan catatan sebelum argparse.
|
*/

test('tabel pendaftaran tidak lagi menawarkan pembatalan', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id]);
    $applicant = Applicant::firstOrFail();

    // Pendaftaran masih aktif DAN masa pendaftaran masih terbuka, jadi di
    // bawah aturan lama tabel ini menampilkan tombolnya.
    expect($applicant->canBeCancelled())->toBeTrue();

    actingAs($this->mahasiswa)->get(route('user.pendaftaran.index'))
        ->assertOk()
        ->assertDontSee('Batalkan Pendaftaran')
        ->assertDontSee('Ya, Batalkan')
        ->assertDontSee('id="batal-'.$applicant->id.'"');
});

test('halaman detail menawarkan pembatalan selama masa pendaftaran masih terbuka', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id]);
    $applicant = Applicant::firstOrFail();

    actingAs($this->mahasiswa)->get(route('user.pendaftaran.lihat', $applicant))
        ->assertOk()
        ->assertSee('Batalkan Pendaftaran')
        ->assertSee(route('user.pendaftaran.batal', $applicant));
});

test('halaman detail menyembunyikan pembatalan setelah masa pendaftaran ditutup', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $beasiswa->id]);
    $applicant = Applicant::firstOrFail();
    $beasiswa->update(['tanggal_selesai' => now()->subDay()]);

    actingAs($this->mahasiswa)->get(route('user.pendaftaran.lihat', $applicant))
        ->assertOk()
        ->assertDontSee('Batalkan Pendaftaran')
        ->assertDontSee('btn-confirm-toggle');
});

/*
|--------------------------------------------------------------------------
| Daftar beasiswa dibatasi kampus profil
|--------------------------------------------------------------------------
*/

test('daftar beasiswa mahasiswa hanya menampilkan kampusnya sendiri', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    $sendiri = beasiswaTersedia($this->kampus->id, ['nama' => 'Beasiswa Kampus Sendiri']);
    $orangLain = beasiswaTersedia($this->kampusLain->id, ['nama' => 'Beasiswa Kampus Lain']);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.index'))
        ->assertOk()
        ->assertSee($sendiri->nama)
        ->assertDontSee($orangLain->nama);
});

test('mahasiswa tanpa prodi melihat peringatan untuk melengkapi profil', function () {
    mhsBeasiswa($this->mahasiswa);
    $this->mahasiswa->profile->update(['prodi_id' => null]);
    beasiswaTersedia($this->kampus->id, ['nama' => 'Beasiswa Tersembunyi']);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.index'))
        ->assertOk()
        ->assertSee('Program Studi pada profil Anda terisi')
        ->assertDontSee('Beasiswa Tersembunyi');
});

/*
|--------------------------------------------------------------------------
| Informasi beasiswa yang harus terbaca mahasiswa
|--------------------------------------------------------------------------
|
| Cakupan fakultas/prodi sudah dipakai `allowsProdi()` untuk menolak
| pendaftar, tapi tidak pernah ditampilkan. Mahasiswa yang prodinya tidak
| termasuk bisa ditolak tanpa sempat melihat daftar itu. Test di bawah
| mengunci tampilan yang menutup celah tersebut.
*/

test('kartu daftar menampilkan deskripsi ringkas, cakupan, dan sisa kuota', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id, [
        'nama' => 'Beasiswa Unggulan Teknik',
        'deskripsi' => 'Beasiswa penuh biaya kuliah untuk mahasiswa berprestasi di Fakultas Teknik.',
        'kuota' => 10,
    ]);
    $beasiswa->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.index'))
        ->assertOk()
        ->assertSee('mahasiswa berprestasi di Fakultas Teknik')
        ->assertSee('1 Fakultas')
        ->assertSee('1 Program Studi')
        ->assertSee('10 / 10');
});

test('kartu daftar memotong deskripsi panjang dan tidak menuliskannya penuh', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    beasiswaTersedia($this->kampus->id, [
        'nama' => 'Beasiswa Deskripsi Panjang',
        'deskripsi' => str_repeat('Uang kuliah gratis setiap semester. ', 20),
    ]);

    $html = actingAs($this->mahasiswa)->get(route('user.beasiswa.index'))->assertOk()->getContent();

    // 20 pengulangan = 720 karakter, jauh di atas batas 120. Yang diuji
    // adalah teksnya terpotong dan ditandai elipsis, bukan panjang persisnya.
    expect($html)
        ->toContain('Uang kuliah gratis setiap semester.')
        ->toContain('...')
        ->not->toContain(str_repeat('Uang kuliah gratis setiap semester. ', 20));
});

test('daftar tanpa daftar prodi menulis semua program studi di kampus itu', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    beasiswaTersedia($this->kampus->id, [
        'kampus' => 'Universitas Lambung Mangkurat',
        'nama' => 'Beasiswa Terbuka',
    ]);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.index'))
        ->assertOk()
        ->assertSee('Semua Program Studi di Universitas Lambung Mangkurat')
        ->assertDontSee('0 Fakultas');
});

test('daftar menandai beasiswa yang kuotanya sudah penuh', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id, ['nama' => 'Beasiswa Penuh', 'kuota' => 1]);
    Applicant::create([
        'user_id' => User::factory()->standardUser()->create()->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'diterima',
    ]);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.index'))
        ->assertOk()
        ->assertSee('Kuota Penuh')
        ->assertSee('0 / 1');
});

test('detail menampilkan cakupan fakultas dan prodi', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    $fakultas = $beasiswa->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $fakultas->prodi()->create(['nama' => 'Teknik Informatika']);
    $fakultas->prodi()->create(['nama' => 'Teknik Elektro']);
    $beasiswa->fakultas()->create(['nama' => 'Fakultas Ekonomi'])->prodi()->create(['nama' => 'Akuntansi']);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))
        ->assertOk()
        ->assertSee('Program Studi yang Bisa Mendaftar')
        ->assertSee('Fakultas Teknik')
        ->assertSee('Teknik Informatika')
        ->assertSee('Teknik Elektro')
        ->assertSee('Akuntansi');
});

test('detail tanpa daftar prodi menjelaskan bahwa semua prodi di kampusnya terbuka', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id, [
        'kampus' => 'Universitas Lambung Mangkurat',
        'nama' => 'Beasiswa Tanpa Pembatasan',
    ]);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))
        ->assertOk()
        ->assertSee('Semua Program Studi di Universitas Lambung Mangkurat')
        ->assertSee('Tidak ada pembatasan program studi');
});

test('detail merubah persyaratan bebas menjadi daftar checklist', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id, [
        'persyaratan' => "- Mahasiswa aktif\n- Tidak pernah punya Beasiswa\nIPK sesuai ketentuan",
    ]);

    $html = actingAs($this->mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))
        ->assertOk()
        ->assertSee('Persyaratan:')
        ->getContent();

    expect($html)
        ->toContain('Mahasiswa aktif')
        ->toContain('Tidak pernah punya Beasiswa')
        ->not->toContain('- Mahasiswa aktif')
        ->not->toContain('&#8226;');
});

test('detail tanpa persyaratan menampilkan kalimat pengganti, bukan kotak kosong', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id, ['persyaratan' => null]);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))
        ->assertOk()
        ->assertSee('Tidak ada persyaratan khusus yang dicantumkan.');
});

/*
| Daftar syarat otomatis sengaja tidak ditampilkan.
|
| Daftar "Syarat Otomatis" pernah dirender di halaman detail. Isinya bukan
| informasi baru: sidebar "Daftar Sekarang" sudah menampilkan satu pesan
| pemblokir yang spesifik lewat `$eligibilityError`, plus alert terpisah untuk
| profil belum lengkap, Capil belum setuju, dan pendaftaran lain yang jalan.
| Daftar per poin hanya mengulang pesan yang sama dalam bentuk yang lebih
| panjang, jadi sudah dihapus dari view.
|
| Test di bawah mengunci keputusan itu dari dua sisi: blok daftar tidak boleh
| muncul, dan pesan pemblokir di sidebar tetap harus ada.
*/

test('detail tidak menampilkan daftar syarat otomatis', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi, [
        'verif_capil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);
    $this->mahasiswa->profile->update(['ipk' => 2.0]);

    $beasiswa = beasiswaTersedia($this->kampus->id, [
        'ipk_minimal' => 3.5,
        'semester_minimal' => 5,
    ]);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))
        ->assertOk()
        ->assertDontSee('Syarat Otomatis')
        ->assertDontSee('diperiksa dari profil Anda')
        ->assertDontSee('IPK Anda minimal 3.5')
        ->assertDontSee('Semester Anda minimal 5')
        ->assertDontSee('Kuota beasiswa masih tersedia')
        ->assertDontSee('Periode pendaftaran masih dibuka')
        ->assertDontSee('Beasiswa berstatus aktif')
        ->assertDontSee('Program Studi Anda termasuk dalam daftar program studi yang dibuka');
});

test('sidebar tetap memberi tahu satu alasan pemblokir yang spesifik', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi, [
        'verif_capil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);
    $this->mahasiswa->profile->update(['ipk' => 2.0]);

    $beasiswa = beasiswaTersedia($this->kampus->id, ['ipk_minimal' => 3.5]);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))
        ->assertOk()
        ->assertSee('IPK minimal untuk beasiswa ini adalah 3.5')
        ->assertDontSee('Ajukan Sekarang');
});

test('jumlah query daftar tidak tumbuh saat kartu bertambah', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);

    $hitung = penghitungQuery();
    $kampusId = $this->kampus->id;
    $mahasiswa = $this->mahasiswa;

    $ukurDaftar = function () use ($hitung, $mahasiswa): int {
        $hitung->reset();

        actingAs($mahasiswa)->get(route('user.beasiswa.index'))->assertOk();

        return $hitung->jumlah();
    };

    // Pemanasan, dengan alasan yang sama seperti di test detail: cache
    // permission Spatie dan relasi profil harus terisi dulu.
    beasiswaDenganCakupan('Pemanasan', $kampusId);
    $ukurDaftar();

    beasiswaDenganCakupan('Satuan', $kampusId);
    $satuKartu = $ukurDaftar();

    // Tambah enam kartu: 8 total, masih di bawah page size 9, jadi tidak ada
    // query paginate tambahan yang mengganggu perbandingan.
    for ($i = 0; $i < 6; $i++) {
        beasiswaDenganCakupan('Banyak '.$i, $kampusId);
    }
    $delapanKartu = $ukurDaftar();

    // Yang diuji adalah selisihnya, bukan jumlah absolut: layout, navbar, dan
    // notifikasi memang menjalankan querynya sendiri. Kalau `sisaKuota()` atau
    // `cakupanLabel()` menembak per kartu, selisihnya ikut bertambah seiring
    // jumlah kartu.
    expect($delapanKartu - $satuKartu)->toBe(0);
});

test('jumlah query detail tidak tumbuh saat prodi cakupan bertambah', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);

    $hitung = penghitungQuery();
    $kampusId = $this->kampus->id;
    $mahasiswa = $this->mahasiswa;

    $ukurDetail = function (Scholarship $beasiswa) use ($hitung, $mahasiswa): int {
        $hitung->reset();

        actingAs($mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))->assertOk();

        return $hitung->jumlah();
    };

    // Render pemanasan dulu. Tanpa ini, render pertama menanggung biaya cache
    // yang baru terisi (cache permission Spatie, relasi profil pada instance
    // user), sehingga render berikutnya terlihat lebih murah dan selisihnya
    // negatif. Yang dibandingkan adalah kondisi stabil, setelah cache terisi.
    $ukurDetail(beasiswaDenganCakupan('Pemanasan', $kampusId));

    $satuProdi = $ukurDetail(beasiswaDenganCakupan('Sedikit', $kampusId));

    $banyak = beasiswaTersedia($kampusId);
    for ($f = 0; $f < 3; $f++) {
        $fakultas = $banyak->fakultas()->create(['nama' => 'Fakultas '.$f]);
        for ($p = 0; $p < 4; $p++) {
            $fakultas->prodi()->create(['nama' => 'Prodi '.$f.'-'.$p]);
        }
    }
    $duaBelasProdi = $ukurDetail($banyak);

    expect($duaBelasProdi - $satuProdi)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Halaman detail beasiswa harus benar-benar bisa dirender
|--------------------------------------------------------------------------
|
| `BeasiswaController::show()` pernah mengirim `compact('profileVerified')`
| untuk nama variabel yang tidak pernah didefinisikan, sekaligus lupa
| mengirim `$capilVerified` yang dipakai view. `compact()` melempar
| E_WARNING di PHP 8.x, `HandleExceptions` mengubahnya jadi ErrorException,
| jadi seluruh halaman detail beasiswa 500. Tidak ada test yang melakukan
| GET ke route ini -- semuanya hanya `assertRedirect` ke sana -- sehingga
| bug itu lolos. Test di bawah menutup celah itu.
*/

test('halaman detail beasiswa terbuka untuk mahasiswa yang profilnya belum disetujui Capil', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))
        ->assertOk()
        ->assertSee($beasiswa->nama)
        ->assertSee('Data dasar belum disetujui Capil')
        ->assertDontSee('Ajukan Sekarang');
});

test('halaman detail beasiswa menampilkan tombol ajukan setelah Capil menyetujui', function () {
    mhsBeasiswa($this->mahasiswa, $this->prodi, ['verif_capil' => 'setuju']);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))
        ->assertOk()
        ->assertSee('Ajukan Sekarang')
        ->assertDontSee('Data dasar belum disetujui Capil');
});

test('halaman detail beasiswa terbuka untuk mahasiswa yang profilnya belum lengkap', function () {
    $this->mahasiswa->profile?->delete();
    $beasiswa = beasiswaTersedia($this->kampus->id);

    actingAs($this->mahasiswa)->get(route('user.beasiswa.lihat', $beasiswa))
        ->assertOk()
        ->assertSee('Profil belum lengkap');
});

/*
|--------------------------------------------------------------------------
| Kuota
|--------------------------------------------------------------------------
*/

test('mahasiswa tidak bisa mendaftar beasiswa yang kuotanya penuh', function () {
    sudahTerverifikasi($this->mahasiswa, $this->prodi);
    $penuh = beasiswaTersedia($this->kampus->id, ['kuota' => 1]);

    Applicant::create([
        'user_id' => User::factory()->standardUser()->create()->id,
        'beasiswa_id' => $penuh->id,
        'status' => 'diterima',
    ]);

    expect($penuh->sisaKuota())->toBe(0);

    actingAs($this->mahasiswa)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $penuh->id])
        ->assertRedirect(route('user.beasiswa.lihat', $penuh))
        ->assertSessionHas('error');

    $this->assertDatabaseCount('pendaftar', 1);
});

/*
|--------------------------------------------------------------------------
| Keputusan pendaftaran oleh Kesra
|--------------------------------------------------------------------------
*/

test('kesra bisa menerima pendaftaran dan menyimpan catatan', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);
    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'diterima',
            'pendaftaran_catatan' => 'Selamat, Anda lolos.',
        ])
        ->assertRedirect(route('admin.kesra.lihat', $user))
        ->assertSessionHas('success');

    expect($applicant->refresh()->status)->toBe('diterima')
        ->and($applicant->catatan)->toBe('Selamat, Anda lolos.');
});

test('kesra tidak bisa menerima pendaftaran yang kuotanya habis', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id, ['kuota' => 1]);

    Applicant::create([
        'user_id' => User::factory()->standardUser()->create()->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'diterima',
    ]);

    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'diterima',
        ])
        ->assertSessionHasErrors('pendaftaran_status');

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('kesra tidak bisa memutuskan pendaftaran tanpa alasan saat menolak', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaTersedia($this->kampus->id)->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'ditolak',
        ])
        ->assertSessionHasErrors('pendaftaran_catatan');

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('kesra bisa memutuskan pendaftaran tanpa langkah verifikasi profil kesra lebih dulu', function () {
    $user = User::factory()->standardUser()->create();
    mhsBeasiswa($user, $this->prodi, [
        'verif_capil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'menunggu',
    ]);

    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaTersedia($this->kampus->id)->id,
        'status' => 'verifikasi',
    ]);

    // Dulu langkah "verifikasi Kesra" harus disetujui dulu, baru pendaftaran
    // bisa diputuskan. Sekarang keduanya satu aksi yang sama.
    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'diterima',
        ])
        ->assertRedirect(route('admin.kesra.lihat', $user));

    expect($applicant->refresh()->status)->toBe('diterima')
        ->and($user->refresh()->profile->verif_kesra)->toBe('setuju');
});

test('keputusan kesra menutup tahap kesra pada profilnya, termasuk saat ditolak', function () {
    $user = User::factory()->standardUser()->create();
    mhsBeasiswa($user, $this->prodi, [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'menunggu',
    ]);

    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaTersedia($this->kampus->id)->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'ditolak',
            'pendaftaran_catatan' => 'Dokumen tidak memenuhi syarat',
        ]);

    // Identitas sudah ditinjau -- itu yang dilakukan keputusan ini -- jadi tahap
    // Kesra selesai meski hasilnya bukan penerimaan. Kalau tahap Kesra tetap
    // `menunggu`, satu pendaftaran bisa menunggu keputusan yang sudah diberikan.
    expect($user->refresh()->profile->verif_kesra)->toBe('setuju')
        ->and($applicant->refresh()->status)->toBe('ditolak');
});

test('keputusan kesra mencatat tanggal dan petugas yang memutuskan', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaTersedia($this->kampus->id)->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'diterima',
        ]);

    $applicant->refresh();

    expect($applicant->diputuskan_oleh)->toBe($this->kesra->id)
        ->and($applicant->decidedByName())->toBe($this->kesra->name)
        ->and($applicant->diputuskan_at)->not->toBeNull();
});

test('keputusan kesra bisa direvisi dari diterima menjadi ditolak tanpa menabrak kuota', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaTersedia($this->kampus->id, ['kuota' => 1])->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'diterima',
        ]);

    // Kuota 1 sudah terpakai oleh pendaftar ini sendiri. Kalau baris ini ikut
    // dihitung saat pemeriksaan kuota, revisi ke `diterima` akan selalu gagal
    // dengan "kuota sudah penuh" padahal slot-nya memang miliknya.
    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'ditolak',
            'pendaftaran_catatan' => 'Terdapat ketidaksesuaian data',
        ])
        ->assertRedirect(route('admin.kesra.lihat', $user));

    expect($applicant->refresh()->status)->toBe('ditolak');
});

test('opsi tarik kembali tidak lagi tersedia pada keputusan pendaftaran', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaTersedia($this->kampus->id)->id,
        'status' => 'diterima',
    ]);

    // `verifikasi` dulu berarti "tarik kembali". Menolak nilainya membuat
    // keputusan tidak bisa dikosongkan tanpa sengaja.
    actingAs($this->kesra)
        ->from(route('admin.kesra.lihat', $user))
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'verifikasi',
        ])
        ->assertSessionHasErrors('pendaftaran_status');

    expect($applicant->refresh()->status)->toBe('diterima');
});

test('kapil tidak bisa memutuskan pendaftaran beasiswa', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaTersedia($this->kampus->id)->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kapil)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'diterima',
        ])
        ->assertForbidden();

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('kesra tidak bisa memutuskan pendaftaran milik mahasiswa lain lewat URL yang dirakit sendiri', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaTersedia($this->kampus->id)->id,
        'status' => 'verifikasi',
    ]);

    $lain = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$lain, $applicant]), [
            'pendaftaran_status' => 'diterima',
        ])
        ->assertNotFound();

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('kesra tidak bisa memutuskan pendaftaran yang sudah dibatalkan mahasiswa', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaTersedia($this->kampus->id)->id,
        'status' => 'dibatalkan',
    ]);

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'diterima',
        ])
        ->assertSessionHasErrors('pendaftaran_status');

    expect($applicant->refresh()->status)->toBe('dibatalkan');
});

/*
|--------------------------------------------------------------------------
| Regresi: dua ronde pendaftaran
|--------------------------------------------------------------------------
*/

test('ditolak di ronde 1 lalu mendaftar ronde 2 tidak otomatis diterima', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);

    $rondeSatu = beasiswaTersedia($this->kampus->id, ['nama' => 'Beasiswa Ronde Satu']);
    $rondeDua = beasiswaTersedia($this->kampus->id, ['nama' => 'Beasiswa Ronde Dua']);

    $pertama = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => $rondeSatu->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kesra)->put(route('admin.kesra.pendaftaran.keputusan', [$user, $pertama]), [
        'pendaftaran_status' => 'ditolak',
        'pendaftaran_catatan' => 'Kuota sudah terisi.',
    ])->assertRedirect(route('admin.kesra.lihat', $user));

    // Ronde 2: profil masih terverifikasi penuh, termasuk tahap Kesra.
    actingAs($user)->post(route('user.pendaftaran.simpan'), ['beasiswa_id' => $rondeDua->id])
        ->assertRedirect(route('user.pendaftaran.index'));

    $kedua = Applicant::where('beasiswa_id', $rondeDua->id)->firstOrFail();
    expect($kedua->status)->toBe('verifikasi')
        ->and($user->profile->verif_kesra)->toBe('setuju');
});

test('penerimaan satu pendaftaran tidak otomatis pendaftaran lain', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);

    $satu = beasiswaTersedia($this->kampus->id, ['nama' => 'Beasiswa Satu']);
    $dua = beasiswaTersedia($this->kampus->id, ['nama' => 'Beasiswa Dua']);

    $pertama = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => $satu->id,
        'status' => 'verifikasi',
    ]);
    $kedua = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => $dua->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kesra)->put(route('admin.kesra.pendaftaran.keputusan', [$user, $pertama]), [
        'pendaftaran_status' => 'diterima',
    ])->assertRedirect(route('admin.kesra.lihat', $user));

    expect($pertama->refresh()->status)->toBe('diterima')
        ->and($kedua->refresh()->status)->toBe('verifikasi');
});

/*
|--------------------------------------------------------------------------
| Snapshot yang sudah tidak sama
|--------------------------------------------------------------------------
*/

test('kesra diberi peringatan saat data profil berubah sejak mendaftar', function () {
    $user = sudahTerverifikasi(User::factory()->standardUser()->create(), $this->prodi);
    $beasiswa = beasiswaTersedia($this->kampus->id);

    $applicant = Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'verifikasi',
        'ipk' => 3.8,
        'semester' => 5,
        'prodi' => 'Teknik Informatika',
    ]);

    $user->profile->update(['ipk' => 2.9]);

    actingAs($this->kesra)->get(route('admin.kesra.lihat', $user))
        ->assertOk()
        ->assertSee('Data sudah berubah sejak mendaftar')
        ->assertSee('IPK saat mendaftar 3.8');
});
