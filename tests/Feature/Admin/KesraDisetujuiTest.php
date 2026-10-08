<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('kesra', 'verifikasi', 'admin');

function beasiswaKesra(Kampus $kampus, string $nama, int $kuota = 5): Scholarship
{
    return Scholarship::factory()->create([
        'nama' => $nama,
        'kampus_id' => $kampus->id,
        'kuota' => $kuota,
        'ipk_minimal' => 0,
        'semester_minimal' => 0,
        'status' => 'aktif',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addMonths(2),
    ]);
}

/**
 * Mahasiswa yang sudah lolos Catpil dan Kampus dengan satu pendaftaran
 * berstatus menunggu -- bahan keputusan Kesra dan halaman Disetujui.
 */
function pemohonKesra(Kampus $kampus, Scholarship $beasiswa): User
{
    $user = User::factory()->standardUser()->create();

    $suffix = str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);

    UserProfile::create([
        'user_id' => $user->id,
        'nama_lengkap' => 'Pemohon '.$user->id,
        'nik' => '6302000000'.$suffix,
        'no_kk' => '6302000001'.$suffix,
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'menunggu',
    ]);

    Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => Applicant::PENDING_STATUS,
    ]);

    return $user;
}

function putuskanPendaftaran(User $kesraAdmin, User $pemohon, Applicant $applicant, string $status): TestResponse
{
    // Catatan tidak dikumpulkan lagi dari tahap Kesra, jadi helper ini hanya
    // mengirim keputusan statusnya.
    return actingAs($kesraAdmin)->put(
        route('admin.kesra.pendaftaran.keputusan', [$pemohon, $applicant]),
        [
            'pendaftaran_status' => $status,
        ],
    );
}

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->kesraAdmin = User::factory()->admin()->create(['email' => 'kesra@disetujui.test']);
    $this->catpilAdmin = User::factory()->catpil()->create(['email' => 'catpil@disetujui.test']);
    $this->mahasiswa = User::factory()->standardUser()->create(['email' => 'mhs@disetujui.test']);
});

test('halaman disetujui menampilkan pendaftaran yang sudah diputuskan kesra', function () {
    $pemohon = pemohonKesra($this->kampus, beasiswaKesra($this->kampus, 'Beasiswa Utama'));
    $applicant = $pemohon->applicants()->firstOrFail();

    putuskanPendaftaran($this->kesraAdmin, $pemohon, $applicant, 'diterima')
        ->assertRedirect(route('admin.kesra.lihat', $pemohon));

    // Pendaftar yang belum diputuskan tidak ikut masuk arsip.
    $lain = pemohonKesra($this->kampus, beasiswaKesra($this->kampus, 'Beasiswa Lain'));

    $response = actingAs($this->kesraAdmin)->get(route('admin.kesra.disetujui'));

    $response->assertOk()
        ->assertSee('Pendaftaran Disetujui')
        ->assertSee($pemohon->profile->nama_lengkap)
        ->assertSee('Beasiswa Utama')
        ->assertSee('oleh '.$this->kesraAdmin->username, false)
        // Baris yang masih menunggu putusan tetap milik antrean kerja.
        ->assertDontSee($lain->profile->nama_lengkap);

    // Aksi Hapus tertanam di setiap baris; pintu masuk ke halaman arsip
    // kini dropdown sidebar Kesra (menu "Keputusan"), bukan tombol di antrean.
    $response->assertSee(route('admin.kesra.pendaftaran.hapus', [$pemohon, $applicant->id]), false)
        ->assertDontSee('Antrean Putusan');

    // Header antrean sengaja tanpa tombol arsip (permintaan pengguna):
    // ikon fa-check-double adalah jangkar tombol "Disetujui" yang dihapus.
    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertDontSee('fa-check-double', false)
        ->assertSee(route('admin.kesra.disetujui'), false);
});

test('halaman disetujui dan aksinya ditutup untuk yang bukan pengelola kesra', function () {
    $pemohon = pemohonKesra($this->kampus, beasiswaKesra($this->kampus, 'Beasiswa Terkunci'));
    $applicant = $pemohon->applicants()->firstOrFail();
    putuskanPendaftaran($this->kesraAdmin, $pemohon, $applicant, 'diterima');

    actingAs($this->catpilAdmin)->get(route('admin.kesra.disetujui'))->assertForbidden();
    actingAs($this->mahasiswa)->get(route('admin.kesra.disetujui'))->assertForbidden();

    actingAs($this->catpilAdmin)
        ->delete(route('admin.kesra.pendaftaran.hapus', [$pemohon, $applicant]))
        ->assertForbidden();

    expect($applicant->refresh()->status)->toBe('diterima');
});

test('hapus mengembalikan pendaftaran ke antrean dan membuka kunci kesra', function () {
    $pemohon = pemohonKesra($this->kampus, beasiswaKesra($this->kampus, 'Beasiswa Dihapus'));
    $applicant = $pemohon->applicants()->firstOrFail();
    putuskanPendaftaran($this->kesraAdmin, $pemohon, $applicant, 'diterima', 'Layak menerima');

    expect($pemohon->refresh()->profile->verif_kesra)->toBe('setuju');

    actingAs($this->kesraAdmin)
        ->delete(route('admin.kesra.pendaftaran.hapus', [$pemohon, $applicant]))
        ->assertRedirect(route('admin.kesra.disetujui'))
        ->assertSessionHas('success');

    // Barisnya tidak dihapus: kembali menunggu putusan dan jejak keputusan
    // dibersihkan, supaya antrean tidak menampilkan sisa keputusan lama.
    $app = $applicant->refresh();
    expect($app->status)->toBe(Applicant::PENDING_STATUS)
        ->and($app->catatan)->toBeNull()
        ->and($app->diputuskan_at)->toBeNull()
        ->and($app->diputuskan_oleh)->toBeNull();

    // Kunci Kesra terbuka lagi karena tidak ada pendaftaran lain yang
    // menyimpan keputusan, dan barisnya kembali ke antrean kerja.
    expect($pemohon->refresh()->profile->verif_kesra)->toBe('menunggu');

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.disetujui'))
        ->assertOk()
        ->assertDontSee($pemohon->profile->nama_lengkap);

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index'))
        ->assertOk()
        ->assertSee($pemohon->profile->nama_lengkap);
});

test('hapus satu pendaftaran tidak membuka kunci selama pendaftaran lain menyimpan keputusan', function () {
    $beasiswaA = beasiswaKesra($this->kampus, 'Beasiswa A');
    $beasiswaB = beasiswaKesra($this->kampus, 'Beasiswa B');

    $pemohon = pemohonKesra($this->kampus, $beasiswaA);
    $pemohon->applicants()->create([
        'beasiswa_id' => $beasiswaB->id,
        'status' => Applicant::PENDING_STATUS,
    ]);

    $appA = $pemohon->applicants()->where('beasiswa_id', $beasiswaA->id)->firstOrFail();
    $appB = $pemohon->applicants()->where('beasiswa_id', $beasiswaB->id)->firstOrFail();

    putuskanPendaftaran($this->kesraAdmin, $pemohon, $appA, 'diterima');
    putuskanPendaftaran($this->kesraAdmin, $pemohon, $appB, 'diterima');

    actingAs($this->kesraAdmin)
        ->delete(route('admin.kesra.pendaftaran.hapus', [$pemohon, $appA]))
        ->assertRedirect(route('admin.kesra.disetujui'));

    // Pendaftaran B masih menyimpan keputusannya, jadi tahap Kesra tetap
    // tertutup dan hanya baris A yang kembali menunggu.
    expect($pemohon->refresh()->profile->verif_kesra)->toBe('setuju')
        ->and($appA->refresh()->status)->toBe(Applicant::PENDING_STATUS)
        ->and($appB->refresh()->status)->toBe('diterima');

    // Membuang keputusan terakhir yang tersisa baru membuka kuncinya.
    actingAs($this->kesraAdmin)
        ->delete(route('admin.kesra.pendaftaran.hapus', [$pemohon, $appB]))
        ->assertRedirect(route('admin.kesra.disetujui'));

    expect($pemohon->refresh()->profile->verif_kesra)->toBe('menunggu');
});

test('hanya pendaftaran yang disetujui yang bisa dihapus dari arsip', function () {
    $pemohon = pemohonKesra($this->kampus, beasiswaKesra($this->kampus, 'Beasiswa Ditolak'));
    $applicant = $pemohon->applicants()->firstOrFail();
    putuskanPendaftaran($this->kesraAdmin, $pemohon, $applicant, 'ditolak');

    // Keputusan ditolak masih diubah lewat form keputusan biasa, bukan lewat
    // Hapus -- kalau tidak, penolakan bisa dibuang dua jalan berbeda. Catatan
    // tidak dikumpulkan dari tahap Kesra, jadi keputusan baru selalu bersih.
    actingAs($this->kesraAdmin)
        ->delete(route('admin.kesra.pendaftaran.hapus', [$pemohon, $applicant]))
        ->assertRedirect(route('admin.kesra.disetujui'))
        ->assertSessionHas('error');

    $app = $applicant->refresh();
    expect($app->status)->toBe('ditolak')
        ->and($app->catatan)->toBeNull();
});

test('hapus menolak pemilik yang salah', function () {
    $pemohon = pemohonKesra($this->kampus, beasiswaKesra($this->kampus, 'Beasiswa Milik Orang'));
    $applicant = $pemohon->applicants()->firstOrFail();
    putuskanPendaftaran($this->kesraAdmin, $pemohon, $applicant, 'diterima');

    $orangLain = User::factory()->standardUser()->create();

    actingAs($this->kesraAdmin)
        ->delete(route('admin.kesra.pendaftaran.hapus', [$orangLain, $applicant]))
        ->assertNotFound();

    expect($applicant->refresh()->status)->toBe('diterima');
});

test('hapus melepas slot kuota supaya pendaftar lain bisa diterima', function () {
    $beasiswa = beasiswaKesra($this->kampus, 'Beasiswa Kuota Satu', kuota: 1);

    $pemohonA = pemohonKesra($this->kampus, $beasiswa);
    $pemohonB = pemohonKesra($this->kampus, $beasiswa);
    $appA = $pemohonA->applicants()->firstOrFail();
    $appB = $pemohonB->applicants()->firstOrFail();

    putuskanPendaftaran($this->kesraAdmin, $pemohonA, $appA, 'diterima');

    // Kuota satu sudah terpakai, jadi keputusan kedua ditolak sistem.
    putuskanPendaftaran($this->kesraAdmin, $pemohonB, $appB, 'diterima')
        ->assertSessionHasErrors('pendaftaran_status');

    expect($appB->refresh()->status)->toBe(Applicant::PENDING_STATUS);

    // Hapus mengembalikan slot A, dan sekarang keputusan untuk B lolos.
    actingAs($this->kesraAdmin)
        ->delete(route('admin.kesra.pendaftaran.hapus', [$pemohonA, $appA]))
        ->assertRedirect(route('admin.kesra.disetujui'));

    putuskanPendaftaran($this->kesraAdmin, $pemohonB, $appB, 'diterima')
        ->assertRedirect(route('admin.kesra.lihat', $pemohonB));

    expect($appB->refresh()->status)->toBe('diterima');
});
