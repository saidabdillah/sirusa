<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('profil');

function payloadProfilLengkap(int $prodiId, array $ubah = []): array
{
    $pdf = fn (string $nama) => UploadedFile::fake()->create($nama, 100, 'application/pdf');

    return array_merge([
        'nama_lengkap' => 'Budi Santoso',
        'email' => 'budi.santoso@example.test',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000009999',
        'nim' => '2010123456',
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
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => 0,
        'ipk' => 3.5,
        'semester' => 4,
        'ukt' => 3500000,
        'desil' => 3,
        'ikut_kk' => 'famili_lain',
        'nama_ayah' => 'Ayah Budi',
        'nik_ayah' => '6302000000000002',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Budi',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
        'nama_wali' => 'Paman Budi',
        'nik_wali' => '6302000000000004',
        'pekerjaan_wali' => 'Wiraswasta',
        'hubungan_wali' => 'Paman',
    ], berkasProfilDummy(), [
        'ktp_wali' => $pdf('ktp-wali.pdf'),
        'kk_wali' => $pdf('kk-wali.pdf'),
    ], $ubah);
}

function profilDenganBerkasLengkap(User $user): UserProfile
{
    return UserProfile::create([
        'user_id' => $user->id,
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => false,
        'nama_lengkap' => 'Budi Santoso',
        'nik' => '6302000000000001',
        'ikut_kk' => 'famili_lain',
        'kk_ikut_famili_lain' => true,
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        // SKTM tidak lagi disyaratkan; PDDIKTI menggantikan Transkrip.
        'dokumen_pddikti' => 'profil/1/pddikti.pdf',
        'dokumen_surat_aktif' => 'profil/1/surat-aktif.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/bukti-ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp-ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp-ibu.pdf',
        'ktp_wali' => 'profil/1/ktp-wali.pdf',
        'kk_wali' => 'profil/1/kk-wali.pdf',
    ]);
}

beforeEach(function () {
    seedAkses();

    Storage::fake('public');

    Http::fake([
        'konoland-api.vercel.app/*' => Http::response([
            'data' => [
                ['code' => '631103', 'regencyCode' => '6311', 'district' => 'Awayan'],
            ],
        ], 200),
    ]);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->fakultas = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $this->prodi = $this->fakultas->prodi()->create(['nama' => 'Teknik Informatika']);
    $this->user = User::factory()->standardUser()->create(['email' => 'user@test.com']);
});

test('payload lengkap tanpa satu field pun kosong lolos validasi', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id))
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors();
});

test('setiap field bertanda bintang di form profil wajib diisi', function (string $field) {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [$field => '']))
        ->assertSessionHasErrors([$field]);

    expect(
        UserProfile::where('user_id', $this->user->id)->exists()
    )->toBeFalse();
})->with([
    // Data Diri
    'nama_lengkap', 'email', 'nik', 'no_kk', 'telepon', 'desil', 'tempat_lahir', 'tanggal_lahir',
    'jenis_kelamin', 'agama', 'kecamatan', 'desa_kelurahan', 'alamat',
    // Data Kampus (termasuk data pendidikan baru)
    'nama_kampus', 'fakultas', 'prodi_id', 'jenjang', 'tahun_masuk', 'ipk', 'semester', 'ukt',
    // Catatan: `kepala_keluarga` TIDAK ada di daftar ini lagi -- kolomnya
    // dihitung server dari opsi radio `ikut_kk`, bukan input mahasiswa, jadi
    // "dihapus dari payload" tidak mungkin menghasilkan pesan validasi.
    // Data Orang Tua & Famili Lain
    'ikut_kk', 'nama_ayah', 'nik_ayah', 'pekerjaan_ayah',
    'nama_ibu', 'nik_ibu', 'pekerjaan_ibu',
    'nama_wali', 'nik_wali', 'pekerjaan_wali', 'hubungan_wali',
    // Dokumen (SKTM tidak lagi disyaratkan; PDDIKTI menggantikan Transkrip)
    'foto_profil', 'dokumen_ktp', 'dokumen_kk', 'dokumen_desil',
    'dokumen_pddikti', 'dokumen_surat_aktif', 'dokumen_surat_pernyataan', 'dokumen_bukti_ukt',
    'ktp_ayah', 'ktp_ibu', 'ktp_wali', 'kk_wali',
]);

test('SKTM tidak lagi diwajibkan di form profil', function () {
    // Permintaan pengguna: SKTM dihapus dari proses beasiswa ini. Formnya tidak
    // boleh lagi meminta berkas itu, dan profil tanpa SKTM harus tetap lengkap.
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    expect($html)
        ->not->toContain('dokumen_sktm')
        ->not->toContain('SKTM');

    // Payload lengkap TANPA SKTM harus lolos.
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id))
        ->assertSessionDoesntHaveErrors();
});

test('cascade kampus, fakultas, dan prodi harus konsisten satu sama lain', function () {
    $kampusLain = Kampus::create(['nama_kampus' => 'Universitas Ranking Dua']);
    $fakultasLain = $kampusLain->fakultas()->create(['nama' => 'Fakultas Ekonomi']);
    $prodiLain = $fakultasLain->prodi()->create(['nama' => 'Akuntansi']);

    // Prodi dari kampus A, tapi nama kampus dikirim dari kampus B.
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($prodiLain->id, [
            'nama_kampus' => $this->kampus->nama_kampus,
            'fakultas' => $fakultasLain->nama,
        ]))
        ->assertSessionHasErrors('prodi_id');

    // Prodi dan kampus benar, tapi fakultas milik kampus lain.
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($prodiLain->id, [
            'nama_kampus' => $kampusLain->nama_kampus,
            'fakultas' => $this->fakultas->nama,
        ]))
        ->assertSessionHasErrors('prodi_id');

    // Kombinasi yang konsisten tetap lolos.
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($prodiLain->id, [
            'nama_kampus' => $kampusLain->nama_kampus,
            'fakultas' => $fakultasLain->nama,
        ]))
        ->assertSessionDoesntHaveErrors();

    expect(UserProfile::where('user_id', $this->user->id)->value('prodi_id'))
        ->toBe($prodiLain->id);
});

test('dropdown kampus dan fakultas menolak pilihan kosong', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, ['nama_kampus' => '']))
        ->assertSessionHasErrors(['nama_kampus'])
        ->assertSessionDoesntHaveErrors(['fakultas']);

    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, ['fakultas' => '']))
        ->assertSessionHasErrors(['fakultas'])
        ->assertSessionDoesntHaveErrors(['nama_kampus']);

    // _empty: browser mengirim keduanya kosong karena fakultas mengikuti kampus.
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'nama_kampus' => '',
            'fakultas' => '',
        ]))
        ->assertSessionHasErrors(['nama_kampus', 'fakultas']);
});

test('berkas bertanda bintang wajib diunggah saat profil belum punya dokumen', function () {
    $tanpaBerkas = array_diff_key(payloadProfilLengkap($this->prodi->id), berkasProfilDummy());
    unset($tanpaBerkas['ktp_wali'], $tanpaBerkas['kk_wali']);

    actingAs($this->user)
        ->put(route('profile.update'), $tanpaBerkas)
        ->assertSessionHasErrors([
            'foto_profil', 'dokumen_ktp', 'dokumen_kk', 'dokumen_desil',
            'dokumen_pddikti', 'dokumen_surat_aktif', 'dokumen_surat_pernyataan', 'dokumen_bukti_ukt',
            'ktp_ayah', 'ktp_ibu', 'ktp_wali', 'kk_wali',
        ])
        // SKTM dan Transkrip sudah tidak ada di form, jadi tidak boleh muncul.
        ->assertSessionDoesntHaveErrors(['dokumen_sktm', 'dokumen_transkrip']);
});

test('berkas yang sudah tersimpan tidak wajib diunggah ulang', function () {
    profilDenganBerkasLengkap($this->user);

    $tanpaBerkas = array_diff_key(
        payloadProfilLengkap($this->prodi->id, ['nama_lengkap' => 'Budi Baru']),
        berkasProfilDummy()
    );
    unset($tanpaBerkas['ktp_wali'], $tanpaBerkas['kk_wali']);

    actingAs($this->user)
        ->put(route('profile.update'), $tanpaBerkas)
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors();

    $profile = $this->user->refresh()->profile;

    expect($profile->nama_lengkap)->toBe('Budi Baru')
        ->and($profile->dokumen_ktp)->toBe('profil/1/ktp.pdf')
        ->and($profile->dokumen_surat_aktif)->toBe('profil/1/surat-aktif.pdf')
        ->and($profile->ktp_ayah)->toBe('profil/1/ktp-ayah.pdf')
        ->and($profile->ktp_wali)->toBe('profil/1/ktp-wali.pdf');
});

test('field wali tidak divalidasi saat KK tidak mengikuti wali', function () {
    $payload = payloadProfilLengkap($this->prodi->id, [
        'ikut_kk' => 'ayah',
        'nama_wali' => 'Paman Ngawur',
        'nik_wali' => '123',
        'pekerjaan_wali' => 'Tidak Ada',
        'hubungan_wali' => 'Tidak Ada',
    ]);
    unset($payload['ktp_wali'], $payload['kk_wali']);

    actingAs($this->user)
        ->put(route('profile.update'), $payload)
        ->assertRedirect(route('profile'))
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors([
            'nama_wali', 'nik_wali', 'pekerjaan_wali', 'hubungan_wali', 'ktp_wali', 'kk_wali',
        ]);

    $profile = $this->user->refresh()->profile;

    expect($profile->kk_ikut_famili_lain)->toBeFalse()
        ->and($profile->nama_wali)->toBeNull()
        ->and($profile->nik_wali)->toBeNull();
});

test('field wali wajib diisi saat KK mengikuti wali', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'nama_wali' => '',
            'nik_wali' => '',
            'pekerjaan_wali' => '',
            'hubungan_wali' => '',
            'ktp_wali' => '',
            'kk_wali' => '',
        ]))
        ->assertSessionHasErrors([
            'nama_wali', 'nik_wali', 'pekerjaan_wali', 'hubungan_wali', 'ktp_wali', 'kk_wali',
        ]);
});

test('ayah dan ibu wajib diisi tanpa bergantung pada ikut_kk', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'ikut_kk' => 'ibu',
            'nama_ayah' => '',
            'nik_ayah' => '',
            'pekerjaan_ayah' => '',
        ]))
        ->assertSessionHasErrors(['nama_ayah', 'nik_ayah', 'pekerjaan_ayah']);
});

test('dokumen prestasi tetap opsional', function () {
    $payload = payloadProfilLengkap($this->prodi->id);
    unset($payload['dokumen_prestasi']);

    actingAs($this->user)
        ->put(route('profile.update'), $payload)
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors(['dokumen_prestasi']);
});

test('form profil tidak pernah memakai atribut html required', function () {
    actingAs($this->user)
        ->get(route('profile'))
        ->assertOk()
        ->assertDontSee(' required>', false)
        ->assertDontSee(' required />', false);
});

test('setiap field bermasalah menampilkan border merah dan pesan di bawah input', function () {
    // [nama field untuk error bag => [id elemen, pesan yang harus tampil]]
    $kasus = [
        'nama_lengkap' => ['nama_lengkap', 'Nama lengkap harus diisi.'],
        'nama_kampus' => ['nama_kampus', 'Nama kampus harus dipilih.'],
        'fakultas' => ['fakultas', 'Fakultas harus dipilih.'],
        'ukt' => ['ukt', 'UKT harus diisi.'],
        'dokumen_ktp' => ['dokumen_ktp', 'KTP wajib diunggah.'],
        'jenis_kelamin' => ['jenis_kelamin_laki_laki', 'Jenis kelamin harus dipilih.'],
        'ikut_kk' => ['ikut_kk_ayah', 'Ikut KK harus dipilih.'],
    ];

    $response = actingAs($this->user)->get(route('profile'));
    $response->assertOk();

    $html = $response->original
        ->withErrors(array_map(fn ($v) => $v[1], $kasus))
        ->render();

    $gagal = [];

    foreach ($kasus as $field => [$id, $text]) {
        // Pada input radio class="..." diletakkan SEBELUM id, jadi ambil class dari
        // tag pembuka mana pun yang punya id tersebut (lookahead, bukan after).
        preg_match(
            '#<(?:input|select)(?=[^>]*\bid="'.preg_quote($id, '#').'")[^>]*\bclass="([^"]*)"#i',
            $html,
            $tag
        );

        if (! str_contains($tag[1] ?? '', 'is-invalid')) {
            $gagal[] = "{$field}: elemen #{$id} tidak diberi class is-invalid";
        }

        $diBawahInput = preg_match(
            '#\bid="'.preg_quote($id, '#').'".*?invalid-feedback[^>]*>\s*'.preg_quote($text, '#').'#s',
            $html
        );

        if (! $diBawahInput) {
            $gagal[] = "{$field}: pesan \"{$text}\" tidak dirender di bawah input #{$id}";
        }
    }

    expect($gagal)->toBe([]);
});

test('upload berkas wali tetap terlihat setelah validasi gagal', function () {
    $this->withSession(['_old_input' => ['ikut_kk' => 'famili_lain']]);

    $html = preg_replace('/\s+/', ' ', actingAs($this->user)->get(route('profile'))->getContent());

    expect($html)->toContain('class="form-group wali-doc" style=""')
        ->and($html)->not->toContain('class="form-group wali-doc" style="display:none;"');

    $this->flushSession();

    $this->withSession(['_old_input' => ['ikut_kk' => 'ayah']]);

    $html = preg_replace('/\s+/', ' ', actingAs($this->user)->get(route('profile'))->getContent());

    expect($html)->toContain('class="form-group wali-doc" style="display:none;"');
});

/*
|--------------------------------------------------------------------------
| NIK dan NIM unik antar mahasiswa
|--------------------------------------------------------------------------
| Kedua kolom punya index unique di `profil_pengguna`, tapi sebelumnya TIDAK
| ada rule `unique` di `UpdateProfilRequest`. Pelanggarannya karena itu baru
| ketahuan di database, dan pesan exception-nya (yang memuat seluruh perintah
| INSERT beserta NIK, No. KK, nomor HP, dan data pribadi lain) tampil ke layar
| lewat catch-all di `ProfileController`. Tes di bawah mengunci dua hal
| sekaligus: pelanggarannya dicegah sebelum menyentuh database, dan pesan yang
| muncul bisa dimengerti.
*/

/** Mahasiswa lain yang sudah mengisi profilnya lebih dulu. */
function mahasiswaLainDenganProfil(string $email, array $profil = []): User
{
    $lain = User::factory()->standardUser()->create(['email' => $email]);

    UserProfile::create(array_merge([
        'user_id' => $lain->id,
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => false,
        'nama_lengkap' => 'Mahasiswa Lain',
        'nik' => '6302000000007777',
        'nim' => '2010999999',
        'ikut_kk' => 'ayah',
        'kk_ikut_famili_lain' => false,
    ], $profil));

    return $lain;
}

test('NIK yang sudah dipakai akun lain ditolak dengan pesan yang bisa dimengerti', function () {
    mahasiswaLainDenganProfil('lain@test.com', ['nik' => '6302000000000001']);

    $response = actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id))
        ->assertSessionHasErrors([
            'nik' => 'NIK ini sudah terdaftar pada akun lain. Periksa kembali NIK Anda.',
        ]);

    // Ditahan di validasi, jadi tidak ada baris profil yang setengah jadi.
    expect(UserProfile::where('user_id', $this->user->id)->exists())->toBeFalse();
});

test('NIM yang sudah dipakai akun lain ditolak dengan pesan yang bisa dimengerti', function () {
    mahasiswaLainDenganProfil('lain@test.com', ['nim' => '2010123456']);

    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id))
        ->assertSessionHasErrors([
            'nim' => 'NIM ini sudah terdaftar pada akun lain. Periksa kembali NIM Anda.',
        ]);

    expect(UserProfile::where('user_id', $this->user->id)->exists())->toBeFalse();
});

test('NIK dan NIM sendiri tidak dianggap bentrok saat profil disimpan ulang', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id))
        ->assertSessionHasNoErrors();

    // Simpan ulang dengan nilai yang sama: `Rule::unique(...)->ignore($profilId)`
    // harus mengecualikan baris profil milik sendiri, kalau tidak setiap
    // penyimpanan ulang akan gagal.
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id))
        ->assertSessionHasNoErrors();

    $profil = UserProfile::where('user_id', $this->user->id)->first();

    expect($profil->nik)->toBe('6302000000000001')
        ->and($profil->nim)->toBe('2010123456');
});

test('No. KK dan NIK orang tua yang sama dengan mahasiswa lain tetap diterima', function () {
    // Dua mahasiswa bersaudara memang berbagi nomor KK dan NIK orang tua.
    // Kolom-kolom ini SENGAJA tidak unik -- menguncinya akan menolak
    // pendaftaran yang sah.
    mahasiswaLainDenganProfil('saudara@test.com', [
        'no_kk' => '6302000000009999',
        'nik_ayah' => '6302000000000002',
        'nik_ibu' => '6302000000000003',
    ]);

    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id))
        ->assertSessionHasNoErrors();

    $profil = UserProfile::where('user_id', $this->user->id)->first();

    expect($profil->no_kk)->toBe('6302000000009999')
        ->and($profil->nik_ayah)->toBe('6302000000000002')
        ->and($profil->nik_ibu)->toBe('6302000000000003');
});

test('pesan galat penyimpanan profil tidak membocorkan isi exception', function () {
    // Pelanggaran unique di database menghasilkan pesan yang memuat SELURUH
    // perintah INSERT beserta nilai tiap kolom. Pesan itu tidak boleh pernah
    // sampai ke layar: yang tampil hanya keterangan umum, detailnya ke log.
    $sumber = file_get_contents(app_path('Http/Controllers/Profile/ProfileController.php'));

    expect($sumber)
        ->not->toContain("'Terjadi kesalahan saat menyimpan profil: '.\$e->getMessage()")
        ->and($sumber)->toContain('Log::error')
        ->and($sumber)->toContain('Profil gagal disimpan karena terjadi kesalahan pada sistem.');
});

/*
|--------------------------------------------------------------------------
| NIK, No. KK, NIK Ayah/Ibu/Wali tidak boleh saling sama
|--------------------------------------------------------------------------
| Kelimanya nomor identitas orang (dan kartu) yang berbeda-beda. Satu nomor di
| dua kolom adalah tanda salah isi, bukan variasi data yang sah. `no_kk` juga
| tidak boleh sama dengan NIK mana pun karena nomor KK bukan NIK.
*/

test('semua kolom NIK diisi nomor yang sama ditolak', function () {
    $nomor = '6302000000000001';

    $response = actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'nik' => $nomor,
            'no_kk' => $nomor,
            'nik_ayah' => $nomor,
            'nik_ibu' => $nomor,
            'nik_wali' => $nomor,
        ]))
        ->assertSessionHasErrors(['nik', 'no_kk', 'nik_ayah', 'nik_ibu', 'nik_wali']);

    expect($response->getSession()->get('errors')->get('nik')[0])
        ->toBe('NIK tidak boleh sama dengan No. Kartu Keluarga, NIK Ayah, NIK Ibu, atau NIK Wali.')
        ->and(UserProfile::where('user_id', $this->user->id)->exists())->toBeFalse();
});

test('No. KK yang sama dengan NIK ditolak', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'nik' => '6302000000000001',
            'no_kk' => '6302000000000001',
        ]))
        ->assertSessionHasErrors([
            'no_kk' => 'No. Kartu Keluarga tidak boleh sama dengan NIK, NIK Ayah, NIK Ibu, atau NIK Wali.',
        ]);
});

test('NIK ayah yang sama dengan NIK ibu ditolak', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'nik_ayah' => '6302000000000002',
            'nik_ibu' => '6302000000000002',
        ]))
        ->assertSessionHasErrors(['nik_ayah', 'nik_ibu']);
});

test('NIK wali yang sama dengan NIK ayah ditolak', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'ikut_kk' => 'famili_lain',
            'nik_ayah' => '6302000000000002',
            'nik_wali' => '6302000000000002',
        ]))
        ->assertSessionHasErrors([
            'nik_wali' => 'NIK Wali tidak boleh sama dengan NIK, No. Kartu Keluarga, NIK Ayah, atau NIK Ibu.',
        ]);
});

test('kelima nomor yang berbeda diterima', function () {
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'ikut_kk' => 'famili_lain',
            'nik' => '6302000000000001',
            'no_kk' => '6302000000000011',
            'nik_ayah' => '6302000000000021',
            'nik_ibu' => '6302000000000031',
            'nik_wali' => '6302000000000041',
        ]))
        ->assertSessionHasNoErrors();

    $profil = UserProfile::where('user_id', $this->user->id)->first();

    expect($profil->nik)->toBe('6302000000000001')
        ->and($profil->no_kk)->toBe('6302000000000011')
        ->and($profil->nik_wali)->toBe('6302000000000041');
});

test('saat wali tidak dipakai, nik_wali kosong tidak memicu pesan bentrok', function () {
    // `different` dilewati Laravel untuk nilai string kosong, jadi kolom yang
    // sedang tidak diisi tidak menambah pesan galat yang membingungkan.
    actingAs($this->user)
        ->put(route('profile.update'), payloadProfilLengkap($this->prodi->id, [
            'ikut_kk' => 'ayah',
            'nik_wali' => '',
        ]))
        ->assertSessionDoesntHaveErrors(['nik_wali']);
});

test('kolom nomor identitas tanpa placeholder dan tanpa teks bantuan', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    // Placeholder contoh dan teks bantuan NIK/No. KK DIHAPUS (permintaan
    // pengguna) -- tidak ada keduanya di kolom mana pun.
    expect($html)
        ->not->toContain('placeholder="contoh 630')
        ->not->toContain('Hanya angka, tepat 16 digit');
});
