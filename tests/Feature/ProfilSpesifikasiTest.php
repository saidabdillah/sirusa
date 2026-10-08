<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil');

beforeEach(function () {
    seedAkses();

    Storage::fake('public');

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->user = User::factory()->standardUser()->create(['email' => 'user@test.com']);
});

/**
 * Payload profil yang lolos validasi, dengan sebagian field bisa ditimpa.
 *
 * @param  array<string, mixed>  $ubah
 * @return array<string, mixed>
 */
function payloadProfilAjax(Prodi $prodi, array $ubah = []): array
{
    return array_merge([
        'nama_lengkap' => 'Ahmad Fauzi',
        'email' => 'ahmad.fauzi@example.test',
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
        'nama_kampus' => 'Universitas Lambung Mangkurat',
        'fakultas' => 'Fakultas Teknik',
        'prodi_id' => $prodi->id,
        'ipk' => 3.5,
        'semester' => 5,
        'ukt' => 1500000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'Ayah Ahmad',
        'nik_ayah' => '6302000000000003',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Ahmad',
        'nik_ibu' => '6302000000000004',
        'pekerjaan_ibu' => 'Petani',
    ], berkasProfilDummy(), $ubah);
}

/**
 * Profil segar dari database. Relasi `profile` di-cache di instance user, jadi
 * harus dibaca ulang setiap kali form baru saja disimpan.
 */
function profilLokal(User $user): ?UserProfile
{
    return UserProfile::query()->where('user_id', $user->id)->first();
}

/*
|--------------------------------------------------------------------------
| UKT: nominal rupiah tidak boleh dikali sepuluh setiap form dibuka
|--------------------------------------------------------------------------
*/

test('ukt berformat Indonesia disimpan sebagai angka utuh', function (string $dikirim) {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilAjax($this->prodi, ['ukt' => $dikirim]))
        ->assertSessionHasNoErrors();

    expect((int) profilLokal($this->user)->ukt)->toBe(1500000);
})->with([
    'tanpa pemisah' => ['1500000'],
    'titik ribuan' => ['1.500.000'],
    'sisa spasi' => ['1 500 000'],
    'pakai koma' => ['1,500,000'],
]);

test('form profil menampilkan ukt dalam rupiah dan tidak mengalikan sepuluh', function () {
    $this->user->profile()->create([
        'nama_lengkap' => 'Ahmad Fauzi',
        'ukt' => 1500000,
    ]);

    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    // Nilai yang dirender adalah digit murni "1500000", yang nanti diformat
    // Cleave jadi "1.500.000". Kalau kolom decimal(10,2) tidak dipangkas,
    // nilainya jadi "1500000.00"; Cleave membuang titiknya tapi "00"-nya
    // tetap ikut, jadi form mengirim 150000000 dan nominalnya tersimpan
    // sepuluh kali lipat -- inilah sumber bug UKT.
    preg_match('/id="ukt"[^>]*?value="([^"]*)"/', $html, $cocok);

    // Assertion-nya dibaca dari nilai input, bukan dari seluruh halaman, karena
    // komentar penjelas di view sengaja menyebut salah satu angka yang salah.
    expect($cocok)->toHaveCount(2)
        ->and($cocok[1])->toBe('1500000');

    // Cleave memakai pemisah ribuan titik dan tanpa desimal, jadi yang tampil
    // di layar tetap "1.500.000".
    expect($html)->toContain('numeralDecimalScale: 0')
        ->and($html)->not->toContain('numeralDecimalScale: 2');

    // Buka form sekali lagi tidak boleh mengubah nominalnya.
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilAjax($this->prodi, [
            'nama_lengkap' => 'Ahmad Fauzi',
            'email' => 'user@test.com',
            'ukt' => '1.500.000',
        ]))
        ->assertSessionHasNoErrors();

    expect((int) profilLokal($this->user)->ukt)->toBe(1500000);
});

/*
|--------------------------------------------------------------------------
| Email wajib di profil
|--------------------------------------------------------------------------
*/

test('email profil wajib diisi dan harus berupa alamat email', function (string $nilai, string $field) {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilAjax($this->prodi, ['email' => $nilai]))
        ->assertSessionHasErrors($field);

    expect($this->user->refresh()->email)->toBe('user@test.com');
})->with([
    'kosong' => ['', 'email'],
    'bukan email' => ['bukan-email', 'email'],
]);

test('email profil disimpan di akun dan tampil lagi saat form dibuka', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilAjax($this->prodi, [
            'email' => 'email.baru@example.test',
        ]))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'email' => 'email.baru@example.test',
    ]);

    // Tidak ada kolom email ganda di tabel profil.
    $this->assertArrayNotHasKey('email', (new UserProfile)->getAttributes());

    actingAs($this->user)->get(route('profile'))
        ->assertOk()
        ->assertSee('email.baru@example.test');
});

test('email profil tidak boleh sama dengan akun lain', function () {
    User::factory()->standardUser()->create(['email' => 'dipakai@example.test']);

    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilAjax($this->prodi, [
            'email' => 'dipakai@example.test',
        ]))
        ->assertSessionHasErrors('email');

    expect($this->user->refresh()->email)->toBe('user@test.com');
});

test('email sendiri tidak dianggap bentrok saat profil disimpan ulang', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilAjax($this->prodi, ['email' => 'user@test.com']))
        ->assertSessionHasNoErrors();

    expect($this->user->refresh()->email)->toBe('user@test.com');
});

/*
|--------------------------------------------------------------------------
| Bukti UKT menerima gambar dan PDF
|--------------------------------------------------------------------------
*/

test('bukti ukt menerima gambar termasuk webp', function (string $nama, string $mime) {
    $berkas = UploadedFile::fake()->createWithContent($nama, 'bukti-ukt');

    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilAjax($this->prodi, [
            'dokumen_bukti_ukt' => $berkas,
        ]))
        ->assertSessionHasNoErrors();

    $tersimpan = profilLokal($this->user)->dokumen_bukti_ukt;

    expect($tersimpan)->not->toBeNull();

    Storage::disk('public')->assertExists($tersimpan);
})->with([
    'pdf' => ['bukti-ukt.pdf', 'application/pdf'],
    'jpg' => ['bukti-ukt.jpg', 'image/jpeg'],
    'png' => ['bukti-ukt.png', 'image/png'],
    'webp' => ['bukti-ukt.webp', 'image/webp'],
]);

test('bukti ukt menolak berkas yang bukan gambar atau pdf', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilAjax($this->prodi, [
            'dokumen_bukti_ukt' => UploadedFile::fake()->create('bukti-ukt.docx', 50, 'application/msword'),
        ]))
        ->assertSessionHasErrors('dokumen_bukti_ukt');

    expect(profilLokal($this->user)?->dokumen_bukti_ukt)->toBeNull();
});

test('kolom bukti ukt menawarkan gambar dan pdf di form', function () {
    actingAs($this->user)->get(route('profile'))
        ->assertOk()
        // `accept` sudah pdf + webp, tidak sekadar `image/*`.
        ->assertSee('name="dokumen_bukti_ukt"', false)
        ->assertSee('accept="image/*,.pdf,.webp"', false);
});

/*
|--------------------------------------------------------------------------
| Status verifikasi baru mulai "menunggu"
|--------------------------------------------------------------------------
*/

test('profil baru selalu mulai dengan ketiga tahap menunggu', function () {
    $profile = $this->user->profile()->create(['nama_lengkap' => 'Ahmad Fauzi']);

    expect($profile->verif_catpil)->toBe('menunggu')
        ->and($profile->verif_kampus)->toBe('menunggu')
        ->and($profile->verif_kesra)->toBe('menunggu');

    // Nilai default juga berlaku di level database, bukan cuma di model.
    $this->assertDatabaseHas('profil_pengguna', [
        'user_id' => $this->user->id,
        'verif_catpil' => 'menunggu',
        'verif_kampus' => 'menunggu',
        'verif_kesra' => 'menunggu',
    ]);
});

test('menyimpan profil tidak mengubah status verifikasi', function () {
    $this->user->profile()->create(['nama_lengkap' => 'Ahmad Fauzi']);

    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilAjax($this->prodi))
        ->assertSessionHasNoErrors();

    $profile = profilLokal($this->user);

    expect($profile->verif_catpil)->toBe('menunggu')
        ->and($profile->verif_kampus)->toBe('menunggu')
        ->and($profile->verif_kesra)->toBe('menunggu')
        // Belum ada keputusan, jadi belum terverifikasi.
        ->and($profile->isVerified())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Dropdown form wajib punya placeholder
|--------------------------------------------------------------------------
*/

test('form create memakai placeholder "Pilih" di setiap dropdown', function () {
    $admin = User::factory()->superAdmin()->create(['email' => 'admin@test.com']);

    // Placeholder ditulis sebagai entitas HTML supaya tidak bergantung pada
    // encode UTF-8 dari HTTP response.
    actingAs($admin)->get(route('admin.beasiswa.buat'))->assertOk()->assertSee('&mdash; Pilih &mdash;', false);
    actingAs($admin)->get(route('admin.pengguna.buat'))->assertOk()->assertSee('&mdash; Pilih &mdash;', false);
});

test('dropdown form create tidak menandai pilihan apa pun sampai pengguna memilih', function () {
    $admin = User::factory()->superAdmin()->create(['email' => 'admin@test.com']);

    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs($admin)->get(route('admin.beasiswa.buat'))->assertOk()->getContent(),
    );

    // Opsi kosong jadi pilihan pertama dan tidak ditandai selected, jadi
    // pengguna wajib memilih sendiri nilai yang benar.
    expect($html)->toContain('<option value="">&mdash; Pilih &mdash;</option>')
        ->and($html)->not->toContain('<option value="" selected>')
        ->and($html)->not->toContain('<option value="S1" selected>')
        ->and($html)->not->toContain('<option value="aktif" selected>');
});

test('dropdown form edit otomatis mengikuti data tersimpan', function () {
    $admin = User::factory()->superAdmin()->create(['email' => 'admin@test.com']);
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tingkat_gelar' => 'S2',
        'status' => 'aktif',
    ]);

    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs($admin)->get(route('admin.beasiswa.ubah', $beasiswa))->assertOk()->getContent(),
    );

    expect($html)->toContain('<option value="S2" selected>')
        ->and($html)->toContain('<option value="aktif" selected>')
        ->and($html)->toContain('<option value="'.$beasiswa->kampus_id.'" selected>')
        // Placeholder tetap ada supaya daftar pilihannya tidak berubah, cuma
        // memang bukan yang terpilih.
        ->and($html)->toContain('&mdash; Pilih &mdash;');
});

test('dropdown keputusan verifikasi tidak pernah mengikuti keputusan yang tersimpan', function () {
    $kampusAdmin = User::factory()->kampusAdmin()->create([
        'email' => 'kampus@test.com',
        'kampus_id' => $this->kampus->id,
    ]);
    $user = User::factory()->standardUser()->create(['email' => 'mhs@test.com']);

    $user->profile()->create([
        'nama_lengkap' => 'Ahmad Fauzi',
        'prodi_id' => $this->prodi->id,
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'revisi',
    ]);

    // Antrean kampus hanya memuat mahasiswa yang benar-benar punya pendaftaran,
    // jadi satu baris pendaftaran dibuat supaya halaman detailnya bisa dibuka.
    Applicant::factory()->create([
        'user_id' => $user->id,
        'beasiswa_id' => Scholarship::factory()->create(['kampus_id' => $this->kampus->id])->id,
        'status' => 'verifikasi',
    ]);

    // Diuji di halaman Kampus, bukan Kesra: halaman Kesra tidak lagi punya
    // form verdict profil, hanya keputusan pendaftaran.
    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs($kampusAdmin)->get(route('admin.kampusverif.lihat', $user))->assertOk()->getContent(),
    );

    // Berbeda dari form beasiswa, yang terpilih mengikuti nilai tersimpan,
    // dropdown keputusan sengaja tidak pernah ikut terisi. Admin yang cuma
    // membuka halaman lalu menekan simpan akan mengirim ulang keputusan lama
    // sebagai keputusan baru. Keputusan yang tersimpan tetap terbaca di kotak
    // "Keputusan saat ini".
    expect($html)->toContain('<option value="" selected>')
        ->and($html)->not->toContain('<option value="revisi" selected>')
        ->and($html)->toContain('Keputusan saat ini:')
        ->and($html)->toContain('Perlu Perbaikan')
        ->and($html)->toContain('&mdash; Pilih &mdash;');
});
