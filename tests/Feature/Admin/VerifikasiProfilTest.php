<?php

use App\Models\Kampus;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\DataVerificationChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('verifikasi', 'admin', 'profil');

function verifikasiProfilePayload(int $prodiId): array
{
    return [
        'nama_lengkap' => 'Ahmad Fauzi',
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
    ];
}

function createPendingVerifikasiProfile(User $user, string $stage): UserProfile
{
    $stages = [
        'capil' => ['verif_capil' => 'menunggu', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kampus' => ['verif_capil' => 'setuju', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kesra' => ['verif_capil' => 'setuju', 'verif_kampus' => 'setuju', 'verif_kesra' => 'menunggu'],
    ];

    $suffix = str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);

    return UserProfile::create(array_merge([
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
}

beforeEach(function () {
    seedAkses();

    $this->capilAdmin = User::factory()->capil()->create(['email' => 'capil@test.com']);
    $this->kampusAdmin = User::factory()->kampusAdmin()->create(['email' => 'kampus@test.com']);
    $this->kesraAdmin = User::factory()->admin()->create(['email' => 'kesra@test.com']);

    $this->prodi = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat'])
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->applicantUser = User::factory()->standardUser()->create(['email' => 'user@test.com']);
});

test('capil index lists profiles still in flight but hides fully verified ones', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    // Sudah lewat ke tahap kampus, tapi belum ada keputusan di tahap berikutnya,
    // jadi Capil masih boleh membetulkan keputusannya sendiri.
    $inFlight = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($inFlight, 'kampus');

    // Sudah terverifikasi penuh, tidak ada yang perlu ditinjau di tahap manapun.
    $done = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($done, 'kesra');

    actingAs($this->capilAdmin)
        ->get(route('admin.capil.index'))
        ->assertOk()
        ->assertSee($this->applicantUser->profile->nama_lengkap)
        ->assertSee($inFlight->profile->nama_lengkap)
        ->assertDontSee($done->profile->nama_lengkap);
});

test('kampus index lists only profiles whose capil stage was approved', function () {
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'kampus');

    $waitingCapil = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($waitingCapil, 'capil');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.index'))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertDontSee($waitingCapil->profile->nama_lengkap);
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
    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertForbidden();
});

test('capil approving a profile persists the status and notifies the owner', function () {
    Notification::fake();

    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), [
            'status' => 'setuju',
            'catatan' => 'Data sesuai',
        ])
        ->assertRedirect(route('admin.capil.index'));

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_capil)->toBe('setuju');
    expect($profile->catatan_capil)->toBe('Data sesuai');

    Notification::assertSentTo($this->applicantUser, DataVerificationChanged::class);
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

test('capil verification page only shows identity data per its purpose', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->capilAdmin)
        ->get(route('admin.capil.lihat', $this->applicantUser))
        ->assertOk()
        // Data yang menjadi wewenang Capil.
        ->assertSee('Data Diri')
        ->assertSee('Data Orang Tua &amp; Wali', false)
        ->assertSee('Dokumen Diri Sendiri')
        ->assertSee('Dokumen Orang Tua / Wali')
        // Bukan wewenang Capil.
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
        ->update(['catatan_capil' => 'KTP kurang terbaca']);

    actingAs($this->kampusAdmin)
        ->get(route('admin.kampusverif.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('Catatan Capil:')
        ->assertSee('KTP kurang terbaca');
});

test('verification index columns follow the purpose of each stage', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'capil')
        ->update(['prodi_id' => $this->prodi->id, 'ipk' => 3.5, 'nim' => '2010123456']);

    // Capil: fokus identitas.
    actingAs($this->capilAdmin)
        ->get(route('admin.capil.index'))
        ->assertOk()
        ->assertSeeInOrder(['No', 'Nama', 'NIK', 'No. Kartu Keluarga', 'Desil', 'Status', 'Aksi'], false)
        ->assertDontSee('Program Studi');

    $this->applicantUser->profile->update(['verif_capil' => 'setuju']);

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
    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->applicantUser)
        ->get(route('admin.capil.index'))
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
    expect($profile->verif_capil)->toBe('menunggu');
    expect($profile->verif_kampus)->toBe('menunggu');
    expect($profile->verif_kesra)->toBe('menunggu');
    expect($profile->catatan_capil)->toBeNull();
});

test('an already approved stage stays in the queue so the decision can be changed', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), ['status' => 'setuju'])
        ->assertRedirect(route('admin.capil.index'));

    // Disetujui, tapi masih ada di daftar Capil dengan aksi ubah.
    actingAs($this->capilAdmin)
        ->get(route('admin.capil.index'))
        ->assertOk()
        ->assertSee($this->applicantUser->profile->nama_lengkap)
        ->assertSee('Ubah Keputusan')
        ->assertSee('Disetujui');
});

test('capil can revise an approval into a rejection', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), ['status' => 'setuju']);

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'Terdapat kekeliruan pada NIK',
        ])
        ->assertRedirect(route('admin.capil.index'))
        ->assertSessionHas('success');

    $profile = $this->applicantUser->refresh()->profile;
    expect($profile->verif_capil)->toBe('tolak');
    expect($profile->catatan_capil)->toBe('Terdapat kekeliruan pada NIK');
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

test('a stage cannot be revised once a downstream stage has been decided', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra');

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), [
            'status' => 'tolak',
            'catatan' => 'NIK tidak sesuai',
        ])
        ->assertForbidden();

    expect($this->applicantUser->refresh()->profile->verif_capil)->toBe('setuju');
});

test('a decision can be pulled back to the waiting list', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kampus');

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), ['status' => 'setuju']);

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), ['status' => 'menunggu'])
        ->assertRedirect(route('admin.capil.index'));

    expect($this->applicantUser->refresh()->profile->verif_capil)->toBe('menunggu');
});

test('a stage is hidden once a downstream stage has been decided', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'kesra');

    // Kesra sudah diputuskan, jadi Capil tidak boleh membatalkan adanya.
    actingAs($this->capilAdmin)
        ->get(route('admin.capil.lihat', $this->applicantUser))
        ->assertForbidden();
});

test('the decision form shows the current choice and offers to pull it back', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'capil')
        ->update(['verif_capil' => 'revisi', 'catatan_capil' => 'KTP kurang terbaca']);

    actingAs($this->capilAdmin)
        ->get(route('admin.capil.lihat', $this->applicantUser))
        ->assertOk()
        ->assertSee('Keputusan saat ini:')
        ->assertSee('Tarik Kembali (kembalikan ke Menunggu)')
        ->assertSee('<option value="revisi" selected>', false);
});

test('the verification queue can be filtered by decision status', function () {
    $pending = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($pending, 'capil');

    $rejected = User::factory()->standardUser()->create();
    createPendingVerifikasiProfile($rejected, 'capil')
        ->update(['verif_capil' => 'tolak']);

    actingAs($this->capilAdmin)
        ->get(route('admin.capil.index', ['filter' => 'tolak']))
        ->assertOk()
        ->assertSee($rejected->profile->nama_lengkap)
        ->assertDontSee($pending->profile->nama_lengkap);

    actingAs($this->capilAdmin)
        ->get(route('admin.capil.index', ['filter' => 'menunggu']))
        ->assertOk()
        ->assertSee($pending->profile->nama_lengkap)
        ->assertDontSee($rejected->profile->nama_lengkap);
});

test('an unknown decision filter falls back to the full queue', function () {
    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->capilAdmin)
        ->get(route('admin.capil.index', ['filter' => 'ngawur']))
        ->assertOk()
        ->assertSee($this->applicantUser->profile->nama_lengkap);
});

test('the empty queue renders no body row so DataTables can initialise', function () {
    foreach (['admin.capil.index', 'admin.kampusverif.index', 'admin.kesra.index'] as $route) {
        $admin = match ($route) {
            'admin.capil.index' => $this->capilAdmin,
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
        // No. Kartu Keluarga tetap wewenang Capil saja.
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
    createPendingVerifikasiProfile($this->applicantUser, 'capil')
        ->update(['verif_capil' => 'revisi', 'catatan_capil' => 'KTP kurang terbaca']);

    actingAs($this->capilAdmin)
        ->get(route('admin.capil.index'))
        ->assertOk()
        ->assertSee('Perlu Perbaikan')
        ->assertSee('KTP kurang terbaca')
        ->assertSee('Ubah Keputusan');
});

test('the verification notification names the stage and the verifying office', function (string $stage, string $admin, string $routePrefix, string $actor, string $title) {
    Notification::fake();

    createPendingVerifikasiProfile($this->applicantUser, $stage);

    actingAs($this->{$admin})
        ->put(route("admin.{$routePrefix}.verifikasi", $this->applicantUser), ['status' => 'setuju']);

    Notification::assertSentTo(
        $this->applicantUser,
        DataVerificationChanged::class,
        function (DataVerificationChanged $notification) use ($title, $actor) {
            $data = $notification->toDatabase($notification->profile->user);

            expect($data['title'])->toBe($title);
            expect($data['message'])->toContain("diverifikasi oleh {$actor}");
            expect($data['message'])->toContain('disetujui');

            return true;
        }
    );
})->with([
    'capil' => ['capil', 'capilAdmin', 'capil', 'Admin Dukcapil', 'Verifikasi Capil'],
    'kampus' => ['kampus', 'kampusAdmin', 'kampusverif', 'Admin Kampus', 'Verifikasi Kampus'],
    'kesra' => ['kesra', 'kesraAdmin', 'kesra', 'Admin Sirusa (Kesra)', 'Verifikasi Kesra'],
]);

test('the notification carries the verifier note when a revision is requested', function () {
    Notification::fake();

    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), [
            'status' => 'revisi',
            'catatan' => 'KTP kurang terbaca',
        ]);

    Notification::assertSentTo(
        $this->applicantUser,
        DataVerificationChanged::class,
        function (DataVerificationChanged $notification) {
            $data = $notification->toDatabase($notification->profile->user);

            expect($data['message'])->toContain('perlu perbaikan');
            expect($data['message'])->toContain('Catatan: KTP kurang terbaca');

            return true;
        }
    );
});

test('an approval notification omits the note', function () {
    Notification::fake();

    createPendingVerifikasiProfile($this->applicantUser, 'capil');

    actingAs($this->capilAdmin)
        ->put(route('admin.capil.verifikasi', $this->applicantUser), [
            'status' => 'setuju',
            'catatan' => 'Semua data sesuai',
        ]);

    Notification::assertSentTo(
        $this->applicantUser,
        DataVerificationChanged::class,
        function (DataVerificationChanged $notification) {
            expect($notification->toDatabase($notification->profile->user)['message'])
                ->not->toContain('Catatan:');

            return true;
        }
    );
});

test('each verification stage uses its own notification icon', function () {
    Notification::fake();

    createPendingVerifikasiProfile($this->applicantUser, 'kesra');

    actingAs($this->kesraAdmin)
        ->put(route('admin.kesra.verifikasi', $this->applicantUser), ['status' => 'setuju']);

    Notification::assertSentTo(
        $this->applicantUser,
        DataVerificationChanged::class,
        function (DataVerificationChanged $notification) {
            expect($notification->toDatabase($notification->profile->user)['icon'])
                ->toBe('fa-hand-holding-heart');

            return true;
        }
    );
});
