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

test('catpil index lists profiles still in flight but hides fully verified ones', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    // Catpil dan Kampus paralel: keputusan Kampus tidak mengunci Catpil.
    // Satu-satunya tahap hilir adalah Kesra, dan masih menunggu, jadi Catpil
    // masih boleh membetulkan keputusannya sendiri.
    $inFlight = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($inFlight, 'kampus');

    // Sudah terverifikasi penuh (Kesra sudah memutuskan), tidak ada yang perlu
    // ditinjau di tahap manapun.
    $done = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($done, 'kesra')->update(['verif_kesra' => 'setuju']);

    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.index'))
        ->assertOk()
        ->assertSee($this->applicantUser->profile->nama_lengkap)
        ->assertSee($inFlight->profile->nama_lengkap)
        ->assertDontSee($done->profile->nama_lengkap);
});

test('kampus queue does not wait for the catpil approval', function () {
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'kampus');

    // Catpil dan Kampus paralel: profil yang Catpil belum setujui tetap masuk
    // antrean Kampus, asalkan mahasiswanya sudah mendaftar beasiswa.
    $waitingCatpil = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($waitingCatpil, 'catpil');
    Applicant::create([
        'user_id' => $waitingCatpil->id,
        'beasiswa_id' => beasiswaVerifikasi()->id,
        'status' => 'verifikasi',
    ]);

    // Akun tanpa pendaftaran tetap tidak masuk antrean Kampus.
    $tanpaPendaftaran = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($tanpaPendaftaran, 'catpil');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.index'))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertSee($waitingCatpil->profile->nama_lengkap)
        ->assertDontSee($tanpaPendaftaran->profile->nama_lengkap);
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

test('kesra index lists only profiles approved by previous stages', function () {
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'kesra');

    $waitingKampus = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($waitingKampus, 'kampus');

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertDontSee($waitingKampus->profile->nama_lengkap);
});

test('verification stage detail is forbidden when the stage is not reachable yet', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertForbidden();
});

test('catpil approving a profile persists the status and its note', function () {
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

test('kesra approval marks the profile as fully verified', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra');

    actingAs($this->kesraAdmin)
        ->put(route('admin.kesra.verifikasi', $this->applicantUser), ['status' => 'setuju'])
        ->assertRedirect(route('admin.kesra.index'));

    expect($this->applicantUser->refresh()->profile->isVerified())->toBeTrue();
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

test('revising a stage resets the downstream stages and drops their notes', function () {
    // Kesra masih menunggu, jadi Kampus masih boleh mengoreksi keputusannya.
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
    expect($profile->verif_kampus)->toBe('revisi');
    expect($profile->verif_kesra)->toBe('menunggu');
    expect($profile->catatan_kesra)->toBeNull();
});

test('a stage cannot be revised once the kesra stage has been decided', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra')
        ->update(['verif_kesra' => 'setuju']);

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'NIK tidak sesuai',
        ])
        ->assertForbidden();

    expect($this->applicantUser->refresh()->profile->verif_catpil)->toBe('setuju');
});

test('catpil decision does not wait for kampus and does not reset it', function () {
    // Catpil paralel dengan Kampus: keputusan Kampus yang sudah ada tidak
    // mengunci Catpil, dan sebaliknya keputusan Catpil tidak menghapus
    // keputusan Kampus. Hanya Kesra yang direset.
    createPendingVerifikasiProfile($this->applicantUser, 'kesra')
        ->update(['catatan_kesra' => 'Catatan lama']);

    expect($this->applicantUser->profile->verif_kampus)->toBe('setuju');

    actingAs($this->catpilAdmin)
        ->put(route('admin.catpil.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'NIK tidak sesuai',
        ])
        ->assertRedirect(route('admin.catpil.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_catpil)->toBe('tolak')
        // Keputusan Kampus tidak tersentuh.
        ->and($profile->verif_kampus)->toBe('setuju')
        // Kesra direset karena menunggu kedua tahap ini.
        ->and($profile->verif_kesra)->toBe('menunggu')
        ->and($profile->catatan_kesra)->toBeNull();
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

test('a stage is hidden once a downstream stage has been decided', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra')
        ->update(['verif_kesra' => 'setuju']);

    // Kesra sudah diputuskan, jadi Catpil tidak boleh membatalkan adanya.
    actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.lihat', $this->applicantUser))
        ->assertForbidden();
});

test('the decision form shows the current choice and offers no pull-back', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'catpil')
        ->update(['verif_catpil' => 'revisi', 'catatan_catpil' => 'KTP kurang terbaca']);

    $isi = actingAs($this->catpilAdmin)
        ->get(route('admin.catpil.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('Keputusan saat ini:')
        ->assertSee('<option value="revisi" selected>', false)
        ->getContent();

    // Menarik keputusan ke daftar tunggu tidak lagi disediakan sebagai pilihan
    // di form, jadi konfirmasi penarik keputusan tidak mungkin muncul lagi.
    expect($isi)->not->toContain('Tarik Kembali (kembalikan ke Menunggu)')
        ->not->toContain('value="menunggu"');
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
    foreach (['admin.catpil.index', 'admin.kampusverif.index', 'admin.kesra.index'] as $route) {
        $admin = match ($route) {
            'admin.catpil.index' => $this->catpilAdmin,
            'admin.kampusverif.index' => $this->kampusAdmin,
            default => $this->kesraAdmin,
        };

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

test('the kesra queue shows the complete set of profile columns', function () {
    $profile = createPendingVerifikasiProfile($this->applicantUser, 'kesra');
    $profile->update([
        'prodi_id' => $this->prodi->id,
        'nim' => '2010123456',
        'ipk' => 3.5,
        'semester' => 4,
        'ukt' => 2500000,
    ]);

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'No', 'Nama', 'NIK', 'No. Kartu Keluarga', 'Desil', 'NIM', 'Program Studi',
            'Fakultas', 'Kampus', 'IPK', 'Semester', 'UKT/SPP', 'Status', 'Aksi',
        ], false)
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

test('the kesra queue eager loads the campus chain instead of querying per row', function () {
    foreach (range(1, 4) as $ignored) {
        $user = User::factory()->standardUser()->create();
        createPendingVerifikasiProfile($user, 'kesra')
            ->update(['prodi_id' => $this->prodi->id]);
    }

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index'))
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
