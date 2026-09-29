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
        'verif_capil' => 'setuju',
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
        'antrean keputusan kesra' => actingAs($kesraAdmin)->get(route('admin.kesra.index'))->assertOk()->getContent(),
        'detail keputusan kesra' => actingAs($kesraAdmin)->get(route('admin.kesra.lihat', $this->mahasiswa))->assertOk()->getContent(),
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

test('halaman kesra hanya punya satu form keputusan, tanpa verdict profil', function () {
    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs(User::factory()->admin()->create(['email' => 'kesra@test.com']))
            ->get(route('admin.kesra.lihat', $this->mahasiswa))
            ->assertOk()
            ->getContent(),
    );

    // Satu keputusan Kesra per pendaftaran. Endpoint `admin.kesra.verifikasi`
    // sudah ditutup di backend, jadi form verdict profil di sini tidak akan
    // pernah bisa disimpan -- memunculkannya hanya menawarkan jalan buntu.
    // Dicek lewat nama field `status` (milik form verdict profil), bukan lewat
    // URL, karena URL keputusan pendaftaran memuat prefix yang sama.
    expect($html)->not->toContain('name="status"')
        ->and($html)->not->toContain('formVerifikasi')
        ->and($html)->not->toContain('Minta Perbaikan')
        ->and(substr_count($html, 'name="pendaftaran_status"'))->toBe(1)
        ->and($html)->toContain('Data Pendaftaran Beasiswa');
});

test('opsi tarik kembali tidak lagi muncul di halaman kesra', function () {
    $this->applicant->update(['status' => 'diterima']);

    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs(User::factory()->admin()->create(['email' => 'kesra@test.com']))
            ->get(route('admin.kesra.lihat', $this->mahasiswa))
            ->assertOk()
            ->getContent(),
    );

    expect($html)->not->toContain('Tarik Kembali')
        ->and($html)->not->toContain('<option value="verifikasi"');
});

test('dropdown keputusan kesra selalu dibuka di "— pilih —"', function (string $status) {
    // Pendaftaran yang sudah diputuskan maupun yang belum harus punya select
    // kosong. Kalau "Terima" otomatis terpilih pada pendaftaran yang sudah
    // `diterima`, satu klik simpan akan mengonfirmasi keputusan lama lagi dan
    // menimpa `diputuskan_at` beserta petugasnya -- persis jebakan yang
    // dihilangkan di form profil.
    $this->applicant->update(array_filter([
        'status' => $status,
        'diputuskan_at' => $status === 'verifikasi' ? null : now(),
    ]));

    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs(User::factory()->admin()->create(['email' => 'kesra@test.com']))
            ->get(route('admin.kesra.lihat', $this->mahasiswa))
            ->assertOk()
            ->getContent(),
    );

    // Blade merender `{{ $x ? 'selected' : '' }}` yang kosong sebagai
    // `<option value="" >`, jadi yang terpilih di sini harus option placeholder.
    expect($html)->toContain('<option value="" selected>')
        ->and($html)->not->toContain('<option value="diterima" selected>')
        ->and($html)->not->toContain('<option value="ditolak" selected>');
})->with(['diterima', 'ditolak', 'verifikasi']);

test('pilihan keputusan kesra tidak hilang saat validasi gagal', function () {
    // `ditolak` tanpa alasan ditolak server, jadi admin dipaksa mengulang. Pilihannya
    // harus masih terisi supaya dia tidak perlu memilih ulang dari nol.
    actingAs(User::factory()->admin()->create(['email' => 'kesra@test.com']))
        ->from(route('admin.kesra.lihat', $this->mahasiswa))
        ->put(route('admin.kesra.pendaftaran.keputusan', [$this->mahasiswa, $this->applicant]), [
            'pendaftaran_status' => 'ditolak',
            'pendaftaran_catatan' => '',
        ])
        ->assertRedirect(route('admin.kesra.lihat', $this->mahasiswa))
        ->assertSessionHasErrors('pendaftaran_catatan');

    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs(User::factory()->admin()->create(['email' => 'kesra2@test.com']))
            ->get(route('admin.kesra.lihat', $this->mahasiswa))
            ->assertOk()
            ->getContent(),
    );

    expect($html)->toContain('<option value="ditolak" selected>');
});

test('keputusan kesra yang sudah tersimpan ditampilkan lengkap sebagai read only', function () {
    $this->applicant->update([
        'status' => 'diterima',
        'catatan' => 'Dokumen lengkap.',
        'diputuskan_at' => now(),
        'diputuskan_oleh' => $this->mahasiswa->id,
    ]);

    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs(User::factory()->admin()->create(['email' => 'kesra@test.com']))
            ->get(route('admin.kesra.lihat', $this->mahasiswa))
            ->assertOk()
            ->getContent(),
    );

    expect($html)->toContain('Keputusan Kesra')
        ->and($html)->toContain('Diputuskan oleh')
        ->and($html)->toContain('Tanggal')
        ->and($html)->toContain('Dokumen lengkap.')
        // Revisi tetap mungkin: label kolom berubah, formnya tetap ada.
        ->and($html)->toContain('Ubah Keputusan');
});

test('pendaftaran yang dibatalkan mahasiswa tidak punya form keputusan', function () {
    $this->applicant->update(['status' => 'dibatalkan']);

    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs(User::factory()->admin()->create(['email' => 'kesra@test.com']))
            ->get(route('admin.kesra.lihat', $this->mahasiswa))
            ->assertOk()
            ->getContent(),
    );

    expect($html)->not->toContain('name="pendaftaran_status"')
        ->and($html)->toContain('Dibatalkan mahasiswa');
});

test('halaman mahasiswa memakai istilah status yang sama dengan admin', function (string $status, string $badge, string $label) {
    $this->applicant->update(['status' => $status]);

    $html = preg_replace(
        '/\s+/',
        ' ',
        actingAs($this->mahasiswa)
            ->get(route('user.pendaftaran.lihat', $this->applicant))
            ->assertOk()
            ->getContent(),
    );

    // Ikon dan label diambil dari `Applicant`, jadi halaman mahasiswa tidak
    // bisa lagi menampilkan istilah berbeda dari daftar pendaftar admin.
    expect($html)->toContain('badge badge-'.$badge)
        ->and($html)->toContain($label);
})->with([
    'verifikasi' => ['verifikasi', 'warning', 'Menunggu'],
    'diterima' => ['diterima', 'success', 'Disetujui'],
    'ditolak' => ['ditolak', 'danger', 'Ditolak'],
    'dibatalkan' => ['dibatalkan', 'secondary', 'Dibatalkan'],
]);
