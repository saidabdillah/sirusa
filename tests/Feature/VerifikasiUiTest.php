<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('verifikasi', 'ui');

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);

    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->mahasiswa = User::factory()->standardUser()->create(['email' => 'mhs@test.com']);

    $this->profile = $this->mahasiswa->profile()->create([
        'nama_lengkap' => 'Ahmad Fauzi',
        'prodi_id' => $this->prodi->id,
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'menunggu',
    ]);

    $this->beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_mulai' => now()->subDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
    ]);

    $this->applicant = Applicant::create([
        'user_id' => $this->mahasiswa->id,
        'beasiswa_id' => $this->beasiswa->id,
        'status' => 'verifikasi',
    ]);
});

test('halaman tidak menampilkan indikator progres atau stepper verifikasi', function () {
    $kesraAdmin = User::factory()->admin()->create(['email' => 'kesra@test.com']);
    $superAdmin = User::factory()->superAdmin()->create(['email' => 'admin@test.com']);

    $halaman = [
        'dasbor mahasiswa' => actingAs($this->mahasiswa)->get(route('dashboard'))->assertOk()->getContent(),
        'daftar beasiswa mahasiswa' => actingAs($this->mahasiswa)->get(route('user.beasiswa.index'))->assertOk()->getContent(),
        'detail pendaftaran mahasiswa' => actingAs($this->mahasiswa)->get(route('user.pendaftaran.lihat', $this->applicant))->assertOk()->getContent(),
        'halaman profil mahasiswa' => actingAs($this->mahasiswa)->get(route('profile'))->assertOk()->getContent(),
        'detail pengguna oleh admin' => actingAs($superAdmin)->get(route('admin.pengguna.lihat', $this->mahasiswa))->assertOk()->getContent(),
        'antrean verifikasi kesra' => actingAs($kesraAdmin)->get(route('admin.kesra.index'))->assertOk()->getContent(),
        'detail verifikasi kesra' => actingAs($kesraAdmin)->get(route('admin.kesra.lihat', $this->mahasiswa))->assertOk()->getContent(),
    ];

    foreach ($halaman as $nama => $html) {
        $html = preg_replace('/\s+/', ' ', $html);

        // Tidak ada progress bar, stepper, atau kartu ringkasan status.
        expect($html, $nama)->not->toContain('progress-bar')
            ->and($html, $nama)->not->toContain('fa-step-forward')
            ->and($html, $nama)->not->toContain('fa-step-backward')
            // Status tahap hanya boleh muncul sebagai nilai keputusan, bukan
            // sebagai urutan tahap yang sedang berjalan.
            ->and($html, $nama)->not->toMatch('/Tahap\s*\d\s*dari\s*\d/i')
            ->and($html, $nama)->not->toMatch('/Verifikasi\s+Tahap\s*\d/i')
            ->and($html, $nama)->not->toMatch('/Progres\s+Verifikasi/i')
            ->and($html, $nama)->not->toMatch('/Status\s+Verifikasi\s*:/i');
    }
});

test('catatan verifikator tetap tersimpan di halaman keputusan', function () {
    $this->profile->update([
        'verif_kesra' => 'revisi',
        'catatan_kesra' => 'Lengkapi bukti pembayaran UKT.',
    ]);

    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs(User::factory()->admin()->create(['email' => 'kesra@test.com']))
            ->get(route('admin.kesra.lihat', $this->mahasiswa))
            ->assertOk()
            ->getContent(),
    );

    // Catatan tidak ikut hilang bersama kartu progres; dipindahkan ke kartu keputusan.
    expect($html)->toContain('Lengkapi bukti pembayaran UKT.')
        ->and($html)->toContain('Catatan verifikator');
});

test('data status verifikasi tetap tersedia untuk logika di belakang layar', function () {
    $this->profile->update(['verif_kesra' => 'revisi', 'catatan_kesra' => 'Belum lengkap.']);

    // UI progres dihapus, tapi kolom statusnya tetap dipakai filter dan audit.
    expect($this->profile->refresh()->verifStageDecision('kesra'))
        ->toMatchArray(['status' => 'revisi', 'decided' => true, 'label' => 'Perlu Perbaikan'])
        ->and($this->profile->catatan_kesra)->toBe('Belum lengkap.');
});
