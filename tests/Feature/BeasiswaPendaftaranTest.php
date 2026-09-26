<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\ApplicationDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

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
        'verif_capil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);

    return $user;
}

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->prodi = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik'])->prodi()->create(['nama' => 'Teknik Informatika']);
    $this->kampusLain = Kampus::create(['nama_kampus' => 'Universitas Ranking Dua']);
    $this->prodiLain = $this->kampusLain->fakultas()->create(['nama' => 'Fakultas Ekonomi'])->prodi()->create(['nama' => 'Akuntansi']);
    $this->mahasiswa = User::factory()->standardUser()->create(['email' => 'mhs@test.com']);
    $this->kesra = User::factory()->admin()->create(['email' => 'kesra@test.com']);
    $this->kapil = User::factory()->capil()->create(['email' => 'capil@test.com']);
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

test('kesra bisa menerima pendaftaran dan mahasiswa diberi tahu', function () {
    Notification::fake();

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

    Notification::assertSentTo($user, ApplicationDecision::class);
});

test('kesra tidak bisa menerima pendaftaran yang kuotanya habis', function () {
    Notification::fake();

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
    Notification::assertNothingSent();
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

test('kesra tidak bisa memutuskan pendaftaran yang profilnya belum disetujui di tahap kesra', function () {
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

    actingAs($this->kesra)
        ->put(route('admin.kesra.pendaftaran.keputusan', [$user, $applicant]), [
            'pendaftaran_status' => 'diterima',
        ])
        ->assertSessionHasErrors('pendaftaran_status');

    expect($applicant->refresh()->status)->toBe('verifikasi');
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
    Notification::fake();

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

    // Hanya penolakan ronde 1 yang dikabari; pendaftaran ronde 2 belum
    // pernah diputuskan, jadi tidak boleh ada kabar kedua.
    Notification::assertSentToTimes($user, ApplicationDecision::class, 1);
});

test('penerimaan satu pendaftaran tidak otomatis pendaftaran lain', function () {
    Notification::fake();

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
