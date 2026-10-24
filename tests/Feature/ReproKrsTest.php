<?php

use App\Models\DokumenAkademik;
use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Bentuk multipart seperti browser nyata: field file berada di FileBag dan
 * tidak muncul pada `$request->input()`. KRS tidak memiliki field teks, jadi
 * barisnya harus tetap ditemukan dan disimpan dari FileBag saja.
 */
test('unggah KRS file-only dari multipart browser tetap tersimpan', function () {
    seedAkses();
    Storage::fake('public');

    $kampus = Kampus::create(['nama_kampus' => 'ULM']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])
        ->prodi()->create(['nama' => 'Informatika']);

    $user = User::factory()->standardUser()->create(['email' => 'krs2@test.com']);

    UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Uji KRS Dua',
        'nik' => '6302000000000221',
        'no_kk' => '6302000000000222',
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 1',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $prodi->id,
        'ipk' => 3.5,
        'semester' => 1,
        'ukt' => 1000000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'A',
        'nik_ayah' => '6302000000000223',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'B',
        'nik_ibu' => '6302000000000224',
        'pekerjaan_ibu' => 'Petani',
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        'dokumen_pddikti' => 'profil/1/pddikti.pdf',
        'dokumen_surat_aktif' => 'profil/1/aktif.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp_ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp_ibu.pdf',
    ]);

    // Simulasikan multipart browser nyata: field teks dan field file masuk ke
    // bag request yang berbeda. KRS tidak punya field teks sama sekali, jadi
    // hanya muncul di FileBag.
    $fileAkademik = [
        'krs' => [
            1 => ['file' => UploadedFile::fake()->create('krs-1.pdf', 100, 'application/pdf')],
        ],
    ];

    actingAs($user);

    $this->call('POST', route('profile.update'), [
        '_method' => 'PUT',
        'nama_lengkap' => 'Uji KRS Dua',
        'email' => 'krs2@test.com',
        'nik' => '6302000000000221',
        'no_kk' => '6302000000000222',
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => 0,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 1',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'nama_kampus' => 'ULM',
        'fakultas' => 'Teknik',
        'prodi_id' => $prodi->id,
        'ipk' => 3.5,
        'semester' => 1,
        'ukt' => 1000000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'A',
        'nik_ayah' => '6302000000000223',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'B',
        'nik_ibu' => '6302000000000224',
        'pekerjaan_ibu' => 'Petani',
    ], [], ['dokumen_akademik' => $fileAkademik])
        ->assertSessionDoesntHaveErrors();

    $semua = DokumenAkademik::where('user_id', $user->id)->get();

    // Yang berisi harus tersimpan, yang kosong tidak boleh jadi baris hantu.
    expect($semua->where('jenis', 'krs')->pluck('semester')->values()->all())->toBe([1])
        ->and($semua->where('jenis', 'khs')->isEmpty())->toBeTrue();

    $krs = $semua->firstWhere('jenis', 'krs');
    expect(Storage::disk('public')->exists($krs->file))->toBeTrue();

    actingAs($user)->get(route('profile'))
        ->assertOk()
        ->assertSee(route('dokumen.show', $krs->file), false);
});
/**
 * Kalau mahasiswa semester 2 tidak mengunggah apa pun, KRS semester 1 dan 2
 * serta KHS semester 1 (berkas + nilai) harus DITOLAK dengan pesan yang
 * menyebut semesternya. Inilah yang sebelumnya lolos: validasi hanya memeriksa
 * baris yang dikirim, jadi form yang mengirim baris kosong dianggap sah.
 */
test('KRS dan KHS serta nilai yang tidak diisi ditolak dengan pesan per semester', function () {
    seedAkses();
    Storage::fake('public');

    $kampus = Kampus::create(['nama_kampus' => 'ULM']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])
        ->prodi()->create(['nama' => 'Informatika']);

    $user = User::factory()->standardUser()->create(['email' => 'krs3@test.com']);

    UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Uji Kosong',
        'nik' => '6302000000000331',
        'no_kk' => '6302000000000332',
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 1',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $prodi->id,
        'ipk' => 3.5,
        'semester' => 2,
        'ukt' => 1000000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'A',
        'nik_ayah' => '6302000000000333',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'B',
        'nik_ibu' => '6302000000000334',
        'pekerjaan_ibu' => 'Petani',
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        'dokumen_pddikti' => 'profil/1/pddikti.pdf',
        'dokumen_surat_aktif' => 'profil/1/aktif.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp_ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp_ibu.pdf',
    ]);

    $akademik = ['krs' => [], 'khs' => []];

    for ($s = 1; $s <= 8; $s++) {
        $akademik['krs'][$s] = ['file' => ''];
        $akademik['khs'][$s] = ['file' => '', 'nilai' => ''];
    }

    $respon = actingAs($user)->put(route('profile.update'), [
        'nama_lengkap' => 'Uji Kosong',
        'email' => 'krs3@test.com',
        'nik' => '6302000000000331',
        'no_kk' => '6302000000000332',
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => 0,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 1',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'nama_kampus' => 'ULM',
        'fakultas' => 'Teknik',
        'prodi_id' => $prodi->id,
        'ipk' => 3.5,
        'semester' => 2,
        'ukt' => 1000000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'A',
        'nik_ayah' => '6302000000000333',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'B',
        'nik_ibu' => '6302000000000334',
        'pekerjaan_ibu' => 'Petani',
        'dokumen_akademik' => $akademik,
    ]);

    // KRS sampai semester aktif (1 dan 2), KHS hanya semester sebelumnya (1).
    $respon->assertSessionHasErrors([
        'dokumen_akademik.krs.1.file',
        'dokumen_akademik.krs.2.file',
        'dokumen_akademik.khs.1.file',
        'dokumen_akademik.khs.1.nilai',
    ]);

    // Semester 3 dst. di luar rentang: tidak boleh ikut dituntut.
    expect(session('errors')->has('dokumen_akademik.krs.3.file'))->toBeFalse()
        ->and(session('errors')->has('dokumen_akademik.khs.2.nilai'))->toBeFalse();

    expect(DokumenAkademik::where('user_id', $user->id)->exists())->toBeFalse();
});
