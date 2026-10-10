<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('verifikasi', 'admin', 'profil');

function verifikasiProfilePayload(int $prodiId): array
{
    return array_merge([
        'nama_lengkap' => 'Ahmad Fauzi',
        'email' => 'ahmad.fauzi@example.test',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000000002',
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
        'ipk' => 3.5,
        'semester' => 4,
        'ukt' => 2500000,
        'desil' => 3,
        'nama_ayah' => 'Ayah Ahmad',
        'nik_ayah' => '6302000000000003',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Ahmad',
        'nik_ibu' => '6302000000000004',
        'pekerjaan_ibu' => 'Petani',
    ], berkasProfilDummy());
}

function createPendingVerifikasiProfile(User $user, string $stage): UserProfile
{
    $stages = [
        'catpil' => ['verif_catpil' => 'menunggu', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kampus' => ['verif_catpil' => 'setuju', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kesra' => ['verif_catpil' => 'setuju', 'verif_kampus' => 'setuju', 'verif_kesra' => 'menunggu'],
    ];

    $suffix = str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);

    $profile = UserProfile::create(array_merge([
        'user_id' => $user->id,
        'nama_lengkap' => "Ahmad Fauzi #{$user->id}",
        'nik' => '6302000000'.$suffix,
        'no_kk' => '6302000001'.$suffix,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
    ], $stages[$stage]));

    // Tahap kampus dan kesra baru masuk antrean setelah mahasiswa benar-benar
    // mendaftar beasiswa, jadi fixture-nya butuh baris `pendaftar` juga.
    if ($stage !== 'catpil') {
        Applicant::create([
            'user_id' => $user->id,
            'beasiswa_id' => beasiswaVerifikasi()->id,
            'status' => 'verifikasi',
        ]);
    }

    return $profile;
}

/**
 * Satu beasiswa dipakai bersama seluruh fixture di file ini supaya tidak
 * menabrak indeks unik `[user_id, beasiswa_id]` pada tabel `pendaftar`.
 */
function beasiswaVerifikasi(): Scholarship
{
    return Scholarship::query()->first() ?: Scholarship::factory()->create([
        'kampus_id' => Kampus::query()->firstOrFail()->id,
        'ipk_minimal' => 0,
        'semester_minimal' => 0,
        'status' => 'aktif',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addMonths(2),
    ]);
}

beforeEach(function () {
    seedAkses();

    Storage::fake('public');

    $this->catpilAdmin = User::factory()->catpil()->create(['email' => 'catpil@test.com']);
    $this->kampusAdmin = User::factory()->kampusAdmin()->create(['email' => 'kampus@test.com']);
    $this->kesraAdmin = User::factory()->admin()->create(['email' => 'kesra@test.com']);

    $this->prodi = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat'])
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->applicantUser = User::factory()->standardUser()->create(['email' => 'user@test.com']);
});

test('catpil index lists every profile, including fully verified ones', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    // Sudah lewat ke tahap kampus.
    $inFlight = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($inFlight, 'kampus');

    // Terverifikasi penuh sekalipun barisnya tetap terdaftar: Catpil tidak
    // punya kunci, jadi keputusannya masih boleh dikoreksi kapan saja.
    $done = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($done, 'kesra')
        ->update(['verif_kesra' => 'setuju']);

    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.index'))
        ->assertOk()
        ->assertSee($this->applicantUser->profile->nama_lengkap)
        ->assertSee($inFlight->profile->nama_lengkap)
        ->assertSee($done->profile->nama_lengkap);
});

test('kampus index lists registered students regardless of the catpil verdict', function () {
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'kampus');

    // Belum mendaftar, jadi memang tidak ada yang perlu dilihat Kampus.
    $waitingCatpil = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($waitingCatpil, 'catpil');

    // Catpil belum memutuskan tapi mahasiswa sudah mendaftar: Kampus tetap
    // mengambilnya, karena kedua tahap berjalan paralel.
    $parallel = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($parallel, 'catpil');
    Applicant::create([
        'user_id' => $parallel->id,
        'beasiswa_id' => beasiswaVerifikasi()->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.index'))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertSee($parallel->profile->nama_lengkap)
        ->assertDontSee($waitingCatpil->profile->nama_lengkap);
});

test('disetujui Catpil tidak otomatis membuka antrean kampus sebelum mahasiswa mendaftar', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'setuju',
            'catatan' => 'Data dasar benar',
        ])
        ->assertRedirect(route('admin.catpil.index'));

    $profile = $this->applicantUser->refresh()->profile;

    expect($profile->verif_catpil)->toBe('setuju')
        // Disetujui Catpil != otomatis terverifikasi kampus.
        ->and($profile->verif_kampus)->toBe('menunggu')
        // Tidak masuk antreanampus karena belum ada pendaftaran.
        ->and($profile->canVerifStage('kampus'))->toBeFalse();

    // Permintaan pertama KINGSA flash "berhasil disetujui" dari Catpil, yang
    // isinya memuat nama mahasiswa. Flash-nya baru hilang di permintaan
    // berikutnya, jadi pemeriksaannya dilakukan setelah satu request lain.
    actingAs($this->kampusAdmin)->get(route('admin.kampusverif.index'));

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.index'))
        ->assertOk()
        ->assertDontSee($profile->nama_lengkap);

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertForbidden();

    // Begitu ada pendaftaran, tahap kampus bisa dikerjakan.
    Applicant::create([
        'user_id' => $this->applicantUser->id,
        'beasiswa_id' => beasiswaVerifikasi()->id,
        'status' => 'verifikasi',
    ]);

    expect($profile->canVerifStage('kampus'))->toBeTrue();

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.index'))
        ->assertOk()
        ->assertSee($profile->nama_lengkap);
});

test('the kesra menu opens a queue of registrations waiting for a decision', function () {
    // `createPendingVerifikasiProfile(..., 'kesra')` sudah membuat satu baris
    // `pendaftar` berstatus `verifikasi`, jadi yang sudah diputuskan cukup diubah
    // statusnya -- membuat baris kedua akan menabrak indeks unik
    // `[user_id, beasiswa_id]`.
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'kesra');

    $diterima = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($diterima, 'kesra');
    $diterima->applicants()->update(['status' => 'diterima']);

    // Kesra punya satu keputusan dan itu keputusan pendaftaran, jadi daftar kerja
    // Kesra adalah daftar pendaftar yang perlu diputuskan. Halaman profil yang
    // dulu jadi antrean Kesra sekarang hanya berlaku untuk Catpil dan Kampus.
    $response = actingAs($this->kesraAdmin)->get(route('admin.kesra.index'));

    $response->assertOk()
        ->assertSee('Daftar Pendaftaran')
        ->assertSee($pending->profile->nama_lengkap)
        // Default-nya semua status (permintaan pengguna): yang sudah diputuskan
        // ikut tampil, dan tersaring lewat dropdown status.
        ->assertSee($diterima->profile->nama_lengkap, false);

    // Setiap baris harus bisa diklik sampai form keputusannya. Kalau tidak, admin
    // mendarat di halaman yang isinya tidak bisa dikerjakan -- dan itu persis yang
    // terjadi ketika menu Kesra cuma dialihkan ke daftar pendaftar.
    $response->assertSee(route('admin.kesra.lihat', $pending->id), false);

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.lihat', $pending->id))
        ->assertOk()
        ->assertSee('name="pendaftaran_status"', false);
});

test('kesra queue filter switches between pending and decided registrations', function () {
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'kesra');

    $diterima = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($diterima, 'kesra');
    $diterima->applicants()->update(['status' => 'diterima']);

    // Tanpa filter: semua status (permintaan pengguna; dulu default antrean).
    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertSee($diterima->profile->nama_lengkap, false);

    // Filter keputusan lama: kebalikannya.
    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index', ['filter' => 'diterima']))
        ->assertOk()
        ->assertSee($diterima->profile->nama_lengkap)
        ->assertDontSee($pending->profile->nama_lengkap, false);

    // `filter=semua` berarti seluruh arsip, termasuk yang sudah dibatalkan.
    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index', ['filter' => 'semua']))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertSee($diterima->profile->nama_lengkap);

    // `?filter=` kosong = tidak ada filter = semua status.
    // `ConvertEmptyStringsToNull` di middleware `web` mengubahnya jadi `null`,
    // sama persis dengan tidak mengirim filter sama sekali.
    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index', ['filter' => '']))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertSee($diterima->profile->nama_lengkap, false);

    // Nilai yang tidak dikenal jatuh ke "semua status", bukan 500 dan bukan juga
    // daftar yang ikut tersaring oleh nilai yang tidak masuk akal itu.
    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index', ['filter' => 'bogus']))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertSee($diterima->profile->nama_lengkap);
});

test('tombol reset filter selalu ada di ketiga antrean verifikasi', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    // Tombol Reset tidak boleh bergantung pada filter aktif. Di Kesra nilai
    // default-nya sekarang semua status, jadi kalau visibilitasnya ikut filter,
    // satu dari tiga halaman akan menampilkan tombol yang berbeda bentuknya dari
    // dua halaman lain. Daftar halaman selalu menampilkannya, dan tiga halaman ini
    // harus konsisten dengan itu.
    $halaman = [
        [fn () => actingAs($this->catpilAdmin)->get(route('admin.catpil.index'))],
        [fn () => actingAs($this->catpilAdmin)->get(route('admin.catpil.index', ['filter' => 'menunggu']))],
        [fn () => actingAs($this->kampusAdmin)->get(route('admin.kampusverif.index'))],
        [fn () => actingAs($this->kampusAdmin)->get(route('admin.kampusverif.index', ['filter' => 'revisi']))],
        [fn () => actingAs($this->kesraAdmin)->get(route('admin.kesra.index'))],
        [fn () => actingAs($this->kesraAdmin)->get(route('admin.kesra.index', ['filter' => 'diterima']))],
    ];

    foreach ($halaman as [$minta]) {
        $html = $minta()->assertOk()->getContent();

        // Diambil persis dari jangkar Reset-nya, bukan searching seluruh halaman:
        // kelas tombol lain di halaman tidak boleh menentukan lulus-tidaknya test
        // ini, dan kembali ke varian `outline` harus gagal karena alasan yang
        // benar.
        preg_match('/<a href="[^"]+" class="([^"]*)">\s*<i class="fas fa-redo"><\/i>\s*Reset/', $html, $cocok);

        expect($cocok)->not->toBeEmpty()
            ->and($cocok[1])->toBe('btn btn-secondary btn-block');
    }
});

test('the kesra queue lists and opens registrations approved by kampus only', function () {
    // `verif_kampus` disetujui tanpa pernah ada `verif_catpil` yang disetujui --
    // sejak permintaan pengguna, Kesra boleh memutuskan begitu Kampus setuju
    // tanpa menunggu Catpil. Baris seperti ini (bisa datang dari seeder/impor,
    // bukan cuma UI) wajib muncul di antrean DAN bisa dibuka.
    $user = User::factory()->standardUser()->create();

    UserProfile::create(array_merge(verifikasiProfilePayload($this->prodi->id), [
        'user_id' => $user->id,
        'verif_catpil' => 'revisi',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'menunggu',
    ]));

    Applicant::factory()->create([
        'user_id' => $user->id,
        'beasiswa_id' => beasiswaVerifikasi()->id,
    ]);

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertSee($user->profile->nama_lengkap, false);

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.lihat', $user->id))
        ->assertOk()
        ->assertSee('name="pendaftaran_status"', false);
});

test('verification stage detail is forbidden when the stage is not reachable yet', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertForbidden();
});

test('catpil approving a profile persists the status', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'setuju',
            'catatan' => 'Data sesuai',
        ])
        ->assertRedirect(route('admin.catpil.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_catpil)->toBe('setuju');
    expect($profile->catatan_catpil)->toBe('Data sesuai');
});

test('the kesra profile verdict endpoint is closed', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra');

    // Tahap Kesra hanya punya satu keputusan, dan itu keputusan pendaftaran
    // beasiswa. Endpoint verdict profil ditutup supaya tidak ada jalur tulis
    // kedua yang bisa memberi satu pendaftaran dua jawaban berbeda.
    actingAs($this->kesraAdmin)
        ->put(route('admin.kesra.verifikasi', $this->applicantUser), ['status' => 'setuju'])
        ->assertForbidden();

    expect($this->applicantUser->refresh()->profile->verif_kesra)->toBe('menunggu');
});

test('menolak profil di catpil menutup pendaftaran yang masih menunggu putusan', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');
    $applicant = $this->applicantUser->applicants()->firstOrFail();

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'Dokumen tidak valid',
        ])
        ->assertRedirect(route('admin.catpil.index'));

    // Penolakan Catpil menutup gerbang Kesra selamanya (butuh `setuju`), jadi
    // pendaftaran yang menggantung dibuat `ditolak` -- bukan keputusan Kesra,
    // hanya pembersihan -- dan mahasiswa bebas mendaftar beasiswa lain.
    expect($applicant->refresh()->status)->toBe('ditolak')
        ->and($this->applicantUser->blockingApplicant())->toBeNull()
        ->and($this->applicantUser->refresh()->profile->verif_kesra)->toBe('menunggu');
});

test('menolak profil di kampus menutup pendaftaran yang masih menunggu putusan', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra');
    $applicant = $this->applicantUser->applicants()->firstOrFail();

    actingAs($this->kampusAdmin)
        ->put(route('admin.kampusverif.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'Kampus tidak sesuai',
        ])
        ->assertRedirect(route('admin.kampusverif.index'));

    expect($applicant->refresh()->status)->toBe('ditolak');
});

test('keputusan selain tolak tidak menutup pendaftaran yang masih menunggu', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');
    $applicant = $this->applicantUser->applicants()->firstOrFail();

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'setuju',
            'catatan' => 'Data benar',
        ])
        ->assertRedirect(route('admin.catpil.index'));

    expect($applicant->refresh()->status)->toBe('verifikasi');
});

test('revisi at a stage blocks downstream stages until fixed', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->kampusAdmin)
        ->put(route('admin.kampusverif.verifikasi', $this->applicantUser), [
            'status' => 'revisi',
            'catatan' => 'Dokumen tidak terbaca',
        ])
        ->assertRedirect(route('admin.kampusverif.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_kampus)->toBe('revisi');
    expect($profile->verifStatus())->toBe('revisi');
    expect($profile->canVerifStage('kesra'))->toBeFalse();
});

test('tolak at a stage persists the status and blocks downstream stages', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->kampusAdmin)
        ->put(route('admin.kampusverif.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'Dokumen tidak valid',
        ])
        ->assertRedirect(route('admin.kampusverif.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_kampus)->toBe('tolak');
    expect($profile->verifStatus())->toBe('tolak');
    expect($profile->canVerifStage('kesra'))->toBeFalse();
});

test('tolak keputusan option is rendered on the verification decision page', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('value="tolak"', false)
        ->assertSee('>Tolak</option>', false);
});

test('catpil verification page only shows identity data per its purpose', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.lihat', $this->applicantUser))
        ->assertOk()
        // Data yang menjadi wewenang Catpil.
        ->assertSee('Data Diri')
        ->assertSee('Data Orang Tua &amp; Wali', false)
        ->assertSee('Dokumen Diri Sendiri')
        ->assertSee('Dokumen Orang Tua / Wali')
        // Bukan wewenang Catpil.
        ->assertDontSee('Data Kampus')
        ->assertDontSee('Dokumen untuk Kampus')
        ->assertDontSee('Sertifikat Prestasi');
});

test('kampus verification page shows identity data alongside campus data', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertOk()
        // Data yang menjadi wewenang Kampus.
        ->assertSee('Data Kampus')
        ->assertSee('Dokumen untuk Kampus')
        // Data diri ikut ditampilkan agar verifier bisa mencocokkan identitas.
        ->assertSee('Data Diri')
        // Bukan wewenang Kampus.
        ->assertDontSee('Data Orang Tua &amp; Wali', false)
        ->assertDontSee('Dokumen Diri Sendiri')
        ->assertDontSee('Dokumen Orang Tua / Wali')
        ->assertDontSee('Sertifikat Prestasi');
});

test('kesra verification page shows all sections for the final eligibility check', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra');

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('Data Diri')
        ->assertSee('Data Kampus')
        ->assertSee('Data Orang Tua &amp; Wali', false)
        ->assertSee('Dokumen Diri Sendiri')
        ->assertSee('Dokumen untuk Kampus')
        ->assertSee('Dokumen Orang Tua / Wali')
        ->assertSee('Sertifikat Prestasi');
});

test('previous stage notes are visible to the next stage verifier', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus')
        ->update(['catatan_catpil' => 'KTP kurang terbaca']);

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('Catatan verifikator')
        ->assertSee('Catpil')
        ->assertSee('KTP kurang terbaca');
});

test('verification index columns follow the purpose of each stage', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil')
        ->update(['prodi_id' => $this->prodi->id, 'ipk' => 3.5, 'nim' => '2010123456']);

    // Catpil: fokus identitas.
    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.index'))
        ->assertOk()
        ->assertSeeInOrder(['No', 'Nama', 'NIK', 'No. Kartu Keluarga', 'Desil', 'Status', 'Aksi'], false)
        ->assertDontSee('Program Studi');

    $this->applicantUser->profile->update(['verif_catpil' => 'setuju']);

    // Disetujui Catpil tidak otomatis berarti masuk antrean kampus: mahasiswa
    // harus sudah mendaftar beasiswa supaya ada yang perlu diverifikasi.
    Applicant::create([
        'user_id' => $this->applicantUser->id,
        'beasiswa_id' => beasiswaVerifikasi()->id,
        'status' => 'verifikasi',
    ]);

    // Kampus: fokus data mahasiswa.
    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.index'))
        ->assertOk()
        ->assertSeeInOrder(['No', 'Nama', 'NIM', 'Program Studi', 'IPK', 'Status', 'Aksi'], false)
        ->assertSee('Teknik Informatika')
        ->assertSee('2010123456')
        ->assertDontSee('No. Kartu Keluarga');
});

test('regular user cannot access verification pages', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    actingAs($this->applicantUser)
        ->get(route('admin.catpil.index'))
        ->assertForbidden();
});

test('updating profile resets verification stages', function () {
    $user = $this->applicantUser;
    createPendingVerifikasiProfile($user, 'kesra')->update([
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);
    expect($user->refresh()->profile->isVerified())->toBeTrue();

    actingAs($user)
        ->put(route('profile.update'), array_merge(verifikasiProfilePayload($this->prodi->id), [
            'nama_lengkap' => 'Ahmad Baru',
        ]))
        ->assertRedirect(route('profile'));

    $profile = $user->refresh()->profile;
    expect($profile->nama_lengkap)->toBe('Ahmad Baru');
    expect($profile->verif_catpil)->toBe('menunggu');
    expect($profile->verif_kampus)->toBe('menunggu');
    expect($profile->verif_kesra)->toBe('menunggu');
    expect($profile->catatan_catpil)->toBeNull();
});

test('an already approved stage stays in the queue so the decision can be changed', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), ['status' => 'setuju'])
        ->assertRedirect(route('admin.catpil.index'));

    // Disetujui, tapi masih ada di daftar Catpil dengan aksi ubah.
    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.index'))
        ->assertOk()
        ->assertSee($this->applicantUser->profile->nama_lengkap)
        ->assertSee('Ubah Keputusan')
        ->assertSee('Disetujui');
});

test('catpil can revise an approval into a rejection', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), ['status' => 'setuju']);

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'Terdapat kekeliruan pada NIK',
        ])
        ->assertRedirect(route('admin.catpil.index'))
        ->assertSessionHas('success');

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_catpil)->toBe('tolak');
    expect($profile->catatan_catpil)->toBe('Terdapat kekeliruan pada NIK');
});

test('revising a stage keeps downstream decisions and notes intact', function () {
    // Kesra sudah menitipkan catatannya; Kampus masih boleh mengoreksi
    // keputusannya sendiri tanpa menghapus pekerjaan hilir.
    createPendingVerifikasiProfile($this->applicantUser, 'kesra')
        ->update(['catatan_kesra' => 'Catatan lama']);

    expect($this->applicantUser->profile->verif_kampus)->toBe('setuju');

    actingAs($this->kampusAdmin)
        ->put(route('admin.kampusverif.verifikasi', $this->applicantUser), [
            'status' => 'revisi',
            'catatan' => 'Transkrip tidak terbaca',
        ])
        ->assertRedirect(route('admin.kampusverif.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_kampus)->toBe('revisi')
        // Tidak ada kaskade: keputusan hilir tetap dan catatan lama tidak hilang.
        ->and($profile->verif_kesra)->toBe('menunggu')
        ->and($profile->catatan_kesra)->toBe('Catatan lama');
});

test('a stage can still be revised after a later stage has been decided', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra');

    // Kampus sudah memutuskan, tapi keputusan Catpil tidak dikunci olehnya:
    // revisi di tahap manapun hanya mengubah keputusan tahap itu sendiri.
    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'NIK tidak sesuai',
        ])
        ->assertRedirect(route('admin.catpil.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_catpil)->toBe('tolak')
        ->and($profile->verif_kampus)->toBe('setuju');
});

test('a decision can be pulled back to the waiting list', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), ['status' => 'setuju']);

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), ['status' => 'menunggu'])
        ->assertRedirect(route('admin.catpil.index'));

    expect($this->applicantUser->refresh()->profile->verif_catpil)->toBe('menunggu');
});

test('the kampus stage stays in the queue read-only once kesra has decided', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra')
        ->update(['verif_kesra' => 'setuju']);

    // Kunci tahap Kampus: keputusan Kesra sudah keluar, jadi keputusan Kampus
    // tidak lagi bisa diubah -- tapi barisnya TETAP tampil di antrean supaya
    // masih bisa ditinjau, hanya tombol aksinya yang jadi "Lihat".
    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.index'))
        ->assertOk()
        ->assertSee($this->applicantUser->profile->nama_lengkap)
        ->assertSee('Lihat')
        ->assertDontSee('Ubah Keputusan');

    // Halaman detailnya terbuka read-only: tidak ada form keputusan.
    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('Keputusan Kampus tidak bisa diubah lagi')
        ->assertDontSee('Simpan Keputusan');

    // Jalur tulis tetap ditolak.
    actingAs($this->kampusAdmin)
        ->put(route('admin.kampusverif.verifikasi', $this->applicantUser), ['status' => 'setuju'])
        ->assertForbidden();

    // Catpil tidak punya kunci: halaman detailnya tetap terbuka dengan form.
    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('Simpan Keputusan');
});

test('the decision form shows the current choice and offers no pull-back', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil')
        ->update(['verif_catpil' => 'revisi', 'catatan_catpil' => 'KTP kurang terbaca']);

    $isi = actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('Keputusan saat ini:')
        // Keputusan lama tetap terbaca di kotak read-only, lengkap dengan
        // labelnya, jadi admin tahu dia sedang memperbaiki apa.
        ->assertSee('Perlu Perbaikan')
        ->assertSee('KTP kurang terbaca')
        ->getContent();

    // Menarik keputusan ke daftar tunggu tidak lagi disediakan sebagai pilihan
    // di form, jadi konfirmasi penarik keputusan tidak mungkin muncul lagi.
    expect($isi)->not->toContain('Tarik Kembali (kembalikan ke Menunggu)')
        ->not->toContain('value="menunggu"');
});

test('the decision dropdown is never pre-filled, even when a decision exists', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil')
        ->update(['verif_catpil' => 'revisi', 'catatan_catpil' => 'KTP kurang terbaca']);

    $isi = actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.lihat', $this->applicantUser))
        ->assertOk()
        ->getContent();

    // Keputusan lama yang terpilih sebagai default adalah jebakan: admin bisa
    // cuma membuka halaman lalu menekan simpan, dan keputusan lama terkirim
    // ulang sebagai keputusan baru. Dropdown harus selalu minta pilihan
    // eksplisit, apa pun status yang tersimpan.
    expect($isi)->toContain('<option value="" selected>');

    foreach (['setuju', 'revisi', 'tolak'] as $pilihan) {
        expect($isi)->not->toContain('<option value="'.$pilihan.'" selected>');
    }
});

test('an undecided profile starts the decision dropdown on the placeholder', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    $isi = actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.lihat', $this->applicantUser))
        ->assertOk()
        ->getContent();

    // Status `menunggu` berarti "belum ada keputusan", jadi tidak boleh
    // otomatis menyorot salah satu keputusan. Tanpa ini browser akan memilih
    // option pertama dan admin bisa menyetujui tanpa pernah memilih.
    expect($isi)->toContain('<option value="" selected>');

    foreach (['setuju', 'revisi', 'tolak'] as $pilihan) {
        expect($isi)->not->toContain('<option value="'.$pilihan.'" selected>');
    }
});

test('pilihan admin tidak hilang saat validasi gagal', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    // Dropdown yang selalu kosong tetap harus menghormati `old()`: kalau validasi
    // memaksa admin mengulang, pilihan yang barusan dibuat tidak boleh hilang
    // dan membuat dia memilih ulang dari nol.
    actingAs($this->catpilAdmin)
        ->from(route('admin.catpil.lihat', $this->applicantUser))
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'revisi',
            'catatan' => '',
        ])
        ->assertRedirect(route('admin.catpil.lihat', $this->applicantUser))
        ->assertSessionHasErrors('catatan');

    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('<option value="revisi" selected>', false);
});

test('only setujui and tolak ask for confirmation', function () {
    $isi = File::get(resource_path('views/admin/verifikasi/lihat.blade.php'));

    // Peta konfirmasi, bukan rantai ternary: tidak ada lagi nilai yang tak
    // dikenali jatuh ke teks "Tarik kembali keputusan ini ke daftar tunggu?".
    expect($isi)->toContain('var KONFIRMASI = {')
        ->toContain("text: 'Yakin setuju?'")
        ->toContain("text: 'Yakin ditolak?'")
        ->toContain("tombol: 'Ya, Setujui!'")
        ->toContain("tombol: 'Ya, Tolak!'");

    // "Minta Perbaikan" dan "— Pilih —" tidak masuk peta, jadi dikirim langsung
    // tanpa dialog.
    expect($isi)->toContain('if (!dialog) {')
        ->not->toContain('revisi: {');

    // Teks penarik keputusan harus benar-benar hilang dari view.
    expect($isi)->not->toContain('Tarik kembali keputusan ini ke daftar tunggu?')
        ->not->toContain('Ya, Tarik Kembali!');
});

test('catatan is required when a revision is requested', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    // Tanpa catatan: ditolak, dan status TIDAK tersimpan.
    actingAs($this->catpilAdmin)
        ->putJson(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'revisi',
            'catatan' => '',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('catatan');

    expect($this->applicantUser->refresh()->profile->verif_catpil)->toBe('menunggu')
        ->and($this->applicantUser->refresh()->profile->catatan_catpil)->toBeNull();

    // Dengan catatan: berhasil.
    actingAs($this->catpilAdmin)
        ->putJson(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'revisi',
            'catatan' => 'KTP kurang terbaca',
        ])
        ->assertOk();

    expect($this->applicantUser->refresh()->profile->verif_catpil)->toBe('revisi')
        ->and($this->applicantUser->refresh()->profile->catatan_catpil)->toBe('KTP kurang terbaca');
});

test('catatan stays optional for the other decisions', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    foreach (['setuju', 'tolak'] as $keputusan) {
        actingAs($this->catpilAdmin)
            ->putJson(route('admin.catpil.verifikasi', $this->applicantUser), [
                'status' => $keputusan,
            ])
            ->assertOk();

        expect($this->applicantUser->refresh()->profile->verif_catpil)->toBe($keputusan);
    }
});

test('the verification queue can be filtered by decision status', function () {
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'catpil');

    $rejected = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($rejected, 'catpil')
        ->update(['verif_catpil' => 'tolak']);

    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.index', ['filter' => 'tolak']))
        ->assertOk()
        ->assertSee($rejected->profile->nama_lengkap)
        ->assertDontSee($pending->profile->nama_lengkap);

    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.index', ['filter' => 'menunggu']))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertDontSee($rejected->profile->nama_lengkap);
});

test('an unknown decision filter falls back to the full queue', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.index', ['filter' => 'ngawur']))
        ->assertOk()
        ->assertSee($this->applicantUser->profile->nama_lengkap);
});

test('the empty queue renders no body row so DataTables can initialise', function () {
    // Kesra tidak punya daftar profil, jadi hanya Catpil danampus yang diuji di sini.
    foreach (['admin.catpil.index', 'admin.kampusverif.index'] as $route) {
        $admin = $route === 'admin.catpil.index' ? $this->catpilAdmin : $this->kampusAdmin;

        $html = actingAs($admin)->get(route($route))->assertOk()->getContent();

        // DataTables 1.10 memetakan <td> ke kolom tanpa memperhitungkan
        // colspan, sehingga satu baris @empty akan membuat init gagal total.
        preg_match('/<tbody>(.*?)<\/tbody>/is', $html, $tbody);
        expect($tbody)->toHaveCount(2);
        expect(trim($tbody[1]))->toBe('');

        // Header kolom tetap harus ada supaya tabel tidak kosong melompong.
        expect(substr_count($html, '<th>'))->toBeGreaterThan(3);
    }
});

test('the kesra decision page shows the complete set of profile columns', function () {
    $profile = createPendingVerifikasiProfile($this->applicantUser, 'kesra');
    $profile->update([
        'prodi_id' => $this->prodi->id,
        'nim' => '2010123456',
        'ipk' => 3.5,
        'semester' => 4,
        'ukt' => 2500000,
    ]);

    // Daftar profil Kesra sudah tidak ada, jadi kelengkapan data yang dilihat admin
    // sebelum memutus sekarang diuji di halaman keputusannya. Urutannya tidak
    // penting di sini: halaman ini menumpuk data per kartu, bukan kolom tabel.
    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('NIK')
        ->assertSee('No. Kartu Keluarga')
        ->assertSee('Desil')
        ->assertSee('NIM')
        ->assertSee('Program Studi')
        ->assertSee('Fakultas')
        ->assertSee('IPK')
        ->assertSee('Semester')
        ->assertSee('UKT/SPP')
        ->assertSee('Teknik Informatika')
        ->assertSee('Fakultas Teknik')
        ->assertSee('Universitas Lambung Mangkurat')
        ->assertSee('2010123456')
        ->assertSee('Rp 2.500.000');
});

test('the kampus queue adds identity columns to the student data', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus')
        ->update([
            'prodi_id' => $this->prodi->id,
            'nim' => '2010123456',
            'ipk' => 3.5,
        ]);

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'No', 'Nama', 'NIM', 'Program Studi', 'IPK', 'NIK',
            'Tempat, Tanggal Lahir', 'Jenis Kelamin', 'Agama', 'Status', 'Aksi',
        ], false)
        ->assertSee('Laki-laki')
        ->assertSee('Islam')
        // No. Kartu Keluarga tetap wewenang Catpil saja.
        ->assertDontSee('No. Kartu Keluarga');
});

test('the kesra dashboard eager loads the campus chain instead of querying per row', function () {
    foreach (range(1, 4) as $ignored) {
        $user = User::factory()->standardUser()->create();
        createPendingVerifikasiProfile($user, 'kesra')
            ->update(['prodi_id' => $this->prodi->id]);
    }

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    // Antrean Kesra ada di dasbor tahapnya, bukan di daftar profil.
    actingAs($this->kesraAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Teknik Informatika', false);

    // Satu query untuk seluruh antrean, bukan satu per baris.
    $prodiQueries = array_filter($queries, fn ($sql) => str_contains($sql, '"prodi"')
        || str_contains($sql, '`prodi`')
        || str_contains($sql, 'from "prodi'));

    expect($prodiQueries)->toHaveCount(1);
});

test('a decided row shows its note so the verifier can recall what was asked', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil')
        ->update(['verif_catpil' => 'revisi', 'catatan_catpil' => 'KTP kurang terbaca']);

    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.index'))
        ->assertOk()
        ->assertSee('Perlu Perbaikan')
        ->assertSee('KTP kurang terbaca')
        ->assertSee('Ubah Keputusan');
});
