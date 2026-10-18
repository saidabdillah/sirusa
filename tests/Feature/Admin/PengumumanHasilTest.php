<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('kesra', 'verifikasi', 'admin');

/**
 * Pengumuman hasil seleksi serentak.
 *
 * Aturan yang dikunci di sini: keputusan Kesra disimpan lebih dulu, tetapi
 * mahasiswa baru melihatnya setelah tombol "Umumkan Hasil" ditekan, dan
 * pengumuman itu menulis satu waktu yang sama untuk semua pendaftar.
 */
beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);

    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->kesra = User::factory()->admin()->create(['email' => 'kesra@test.com']);

    $this->beasiswa = Scholarship::factory()->create([
        'nama' => 'Beasiswa Serentak',
        'kampus_id' => $this->kampus->id,
        'kuota' => 10,
        'ipk_minimal' => 0,
        'semester_minimal' => 0,
        'status' => 'aktif',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addMonths(2),
    ]);
});

/**
 * Mahasiswa dengan profil yang sudah lolos Catpil dan Kampus, plus satu
 * pendaftaran pada beasiswa yang dipakai test.
 */
function pemohonSerentak(Kampus $kampus, Scholarship $beasiswa, string $nama): User
{
    $user = User::factory()->standardUser()->create();

    $suffix = str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);

    UserProfile::create([
        'user_id' => $user->id,
        'prodi_id' => $kampus->fakultas()->first()->prodi()->first()->id,
        'jenjang' => 'S1',
        'tahun_masuk' => 2022,
        'kepala_keluarga' => false,
        'nama_lengkap' => $nama,
        'nik' => '6302000000'.$suffix,
        'no_kk' => '6302000001'.$suffix,
        'ipk' => 3.50,
        'semester' => 4,
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'menunggu',
    ]);

    Applicant::create([
        'user_id' => $user->id,
        'beasiswa_id' => $beasiswa->id,
        'ipk' => 3.50,
        'semester' => 4,
        'status' => Applicant::PENDING_STATUS,
    ]);

    return $user;
}

function putuskanSerentak(User $kesra, User $pemohon, string $status): void
{
    actingAs($kesra)->put(
        route('admin.kesra.pendaftaran.keputusan', [$pemohon, $pemohon->applicants()->first()]),
        ['pendaftaran_status' => $status],
    )->assertRedirect();
}

test('keputusan kesra tidak terlihat mahasiswa sebelum diumumkan', function () {
    $pemohon = pemohonSerentak($this->kampus, $this->beasiswa, 'Ahmad Serentak');
    putuskanSerentak($this->kesra, $pemohon, 'diterima');

    $applicant = $pemohon->applicants()->first();

    // Tersimpan untuk petugas, tetapi tahap Kesra pada profil belum ditutup.
    expect($applicant->status)->toBe('diterima')
        ->and($applicant->decision_at)->toBeNull()
        ->and($pemohon->profile->fresh()->verif_kesra)->toBe('menunggu');

    // ...tapi mahasiswa masih melihat "Menunggu" di semua halaman yang dia buka.
    actingAs($pemohon)->get(route('user.pendaftaran.lihat', $applicant))
        ->assertOk()
        ->assertSee('Menunggu')
        ->assertDontSee('Selamat! Anda Diterima');

    actingAs($pemohon)->get(route('user.pendaftaran.index'))
        ->assertOk()
        ->assertSee('Menunggu')
        ->assertDontSee('>Setuju<', false);

    actingAs($pemohon)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Menunggu');
});

test('pengumuman menolak selama masih ada pendaftar yang belum diputuskan', function () {
    $sudah = pemohonSerentak($this->kampus, $this->beasiswa, 'Sudah Diputuskan');
    pemohonSerentak($this->kampus, $this->beasiswa, 'Masih Menunggu');

    putuskanSerentak($this->kesra, $sudah, 'diterima');

    actingAs($this->kesra)
        ->post(route('admin.kesra.umumkan', $this->beasiswa))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($this->beasiswa->fresh()->result_announced_at)->toBeNull()
        ->and($sudah->applicants()->first()->decision_at)->toBeNull();
});

test('pengumuman mengisi satu waktu yang sama untuk semua pendaftar', function () {
    $a = pemohonSerentak($this->kampus, $this->beasiswa, 'Pendaftar A');
    $b = pemohonSerentak($this->kampus, $this->beasiswa, 'Pendaftar B');

    putuskanSerentak($this->kesra, $a, 'diterima');
    putuskanSerentak($this->kesra, $b, 'ditolak');

    actingAs($this->kesra)
        ->post(route('admin.kesra.umumkan', $this->beasiswa))
        ->assertRedirect();

    $this->beasiswa->refresh();

    expect($this->beasiswa->result_announced_at)->not->toBeNull();

    $aDecision = $a->applicants()->first()->decision_at;
    $bDecision = $b->applicants()->first()->decision_at;

    // Momen pengumuman identik sampai ke detiknya: inilah inti "serentak".
    expect($aDecision)->not->toBeNull()
        ->and($bDecision)->not->toBeNull()
        ->and($aDecision->equalTo($bDecision))->toBeTrue()
        ->and($aDecision->equalTo($this->beasiswa->result_announced_at))->toBeTrue();

    // Tahap Kesra pada profil juga baru ditutup pada momen pengumuman, bukan
    // saat keputusan per pendaftar diambil.
    expect($a->profile->fresh()->verif_kesra)->toBe('setuju')
        ->and($b->profile->fresh()->verif_kesra)->toBe('setuju');
});

test('setelah diumumkan mahasiswa melihat hasil masing-masing', function () {
    $diterima = pemohonSerentak($this->kampus, $this->beasiswa, 'Pendaftar Diterima');
    $ditolak = pemohonSerentak($this->kampus, $this->beasiswa, 'Pendaftar Ditolak');

    putuskanSerentak($this->kesra, $diterima, 'diterima');
    putuskanSerentak($this->kesra, $ditolak, 'ditolak');

    actingAs($this->kesra)->post(route('admin.kesra.umumkan', $this->beasiswa))->assertRedirect();

    actingAs($diterima)->get(route('user.pendaftaran.lihat', $diterima->applicants()->first()))
        ->assertOk()
        ->assertSee('Setuju')
        ->assertDontSee('Menunggu Pengumuman');

    actingAs($ditolak)->get(route('user.pendaftaran.lihat', $ditolak->applicants()->first()))
        ->assertOk()
        ->assertSee('Tolak')
        ->assertDontSee('Menunggu Pengumuman');
});

test('hasil yang sudah diumumkan tidak bisa diubah lagi', function () {
    $pemohon = pemohonSerentak($this->kampus, $this->beasiswa, 'Pendaftar Terkunci');
    putuskanSerentak($this->kesra, $pemohon, 'diterima');

    actingAs($this->kesra)->post(route('admin.kesra.umumkan', $this->beasiswa))->assertRedirect();

    actingAs($this->kesra)->put(
        route('admin.kesra.pendaftaran.keputusan', [$pemohon, $pemohon->applicants()->first()]),
        ['pendaftaran_status' => 'ditolak'],
    )->assertSessionHasErrors('pendaftaran_status');

    expect($pemohon->applicants()->first()->status)->toBe('diterima');
});

test('pengumuman ganda ditolak', function () {
    $pemohon = pemohonSerentak($this->kampus, $this->beasiswa, 'Pendaftar Ganda');
    putuskanSerentak($this->kesra, $pemohon, 'diterima');

    actingAs($this->kesra)->post(route('admin.kesra.umumkan', $this->beasiswa))->assertRedirect();
    $pertama = $this->beasiswa->fresh()->result_announced_at;

    actingAs($this->kesra)->post(route('admin.kesra.umumkan', $this->beasiswa))
        ->assertSessionHas('error');

    expect($this->beasiswa->fresh()->result_announced_at->equalTo($pertama))->toBeTrue();
});

test('halaman keputusan kesra menampilkan panel pengumuman per beasiswa', function () {
    $pemohon = pemohonSerentak($this->kampus, $this->beasiswa, 'Pendaftar Panel');
    putuskanSerentak($this->kesra, $pemohon, 'diterima');

    actingAs($this->kesra)->get(route('admin.kesra.disetujui'))
        ->assertOk()
        ->assertSee('Pengumuman Hasil Seleksi')
        ->assertSee('Beasiswa Serentak')
        ->assertSee('Siap diumumkan')
        ->assertSee('Umumkan Hasil');
});
