<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('verifikasi', 'admin', 'profil');

/**
 * Profil minimal dengan verif yang bisa diatur, tanpa dokumen -- halaman
 * status hanya membaca keputusan, bukan berkas.
 */
function profilStatus(User $user, string $nama, array $verif, ?int $prodiId = null): UserProfile
{
    $suffix = str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);

    return UserProfile::create(array_merge([
        'user_id' => $user->id,
        'nama_lengkap' => $nama,
        'nik' => '6302999999'.$suffix,
        'no_kk' => '6302888888'.$suffix,
        'nim' => '2010'.$suffix,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081250000000',
        'alamat' => 'RT 01',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $prodiId,
    ], array_merge([
        'verif_catpil' => 'menunggu',
        'verif_kampus' => 'menunggu',
        'verif_kesra' => 'menunggu',
    ], $verif)));
}

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->fakultas = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $this->prodi = $this->fakultas->prodi()->create(['nama' => 'Teknik Informatika']);
    $this->prodiKedua = $this->fakultas->prodi()->create(['nama' => 'Sistem Informasi']);

    // Kampus kedua untuk memastikan filter lokasi betul-betul bertingkat.
    $this->kampusLain = Kampus::create(['nama_kampus' => 'Universitas Islam Kalimantan']);
    $this->fakultasLain = $this->kampusLain->fakultas()->create(['nama' => 'Fakultas Ekonomi']);
    $this->prodiLain = $this->fakultasLain->prodi()->create(['nama' => 'Akuntansi']);

    $this->kesraAdmin = User::factory()->admin()->create(['email' => 'kesra@test.com']);
    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'super@test.com']);
    $this->catpilAdmin = User::factory()->catpil()->create(['email' => 'catpil@test.com']);
    $this->kampusAdmin = User::factory()->kampusAdmin()->create(['email' => 'kampus@test.com']);
});

test('kesra membuka halaman status verifikasi yang memuat seluruh profil dengan badge keputusan', function () {
    $lulus = User::factory()->standardUser()->create();
    profilStatus($lulus, 'Citra Status Lulus', [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'menunggu',
    ], $this->prodi->id);

    $revisi = User::factory()->standardUser()->create();
    profilStatus($revisi, 'Citra Status Revisi', [
        'verif_catpil' => 'revisi',
        'verif_kampus' => 'tolak',
        'verif_kesra' => 'setuju',
    ], $this->prodi->id);

    $response = actingAs($this->kesraAdmin)->get(route('admin.kesra.status'));

    $response->assertOk()
        ->assertSee('Status Verifikasi Profil Mahasiswa')
        ->assertSee(route('admin.kesra.status'), false)
        ->assertSee('Citra Status Lulus')
        ->assertSee('Citra Status Revisi')
        // Urutan kolom dan judul lokasi: Kampus, Fakultas, Program Studi.
        ->assertSee('Kampus')
        ->assertSee('Fakultas')
        ->assertSee('Program Studi')
        ->assertSee('Universitas Lambung Mangkurat')
        ->assertSee('Fakultas Teknik')
        // Badge keputusan per tahap ikut dirender.
        ->assertSee('Disetujui')
        ->assertSee('Perlu Perbaikan')
        ->assertSee('Ditolak');
});

test('kolom kesra mengikuti keputusan pendaftaran, bukan verif_kesra', function () {
    // `verif_kesra` sengaja `setuju` (penanda "sudah diputuskan", kunci tahap
    // Kampus) padahal pendaftarannya DITOLAK. Dulu kolom Kesra membaca
    // `verif_kesra` sehingga tampil "Disetujui"; sekarang harus "Ditolak".
    $ditolak = User::factory()->standardUser()->create();
    profilStatus($ditolak, 'Citra Kesra Ditolak', [
        'verif_catpil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ], $this->prodi->id);

    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'status' => 'aktif',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addMonth(),
    ]);

    Applicant::create([
        'user_id' => $ditolak->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'ditolak',
    ]);

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.status'))
        ->assertOk()
        // Baris ini dulu berakhir "Disetujui". Dua badge pertama Catpil/Kampus
        // tetap Disetujui; badge Kesra wajib Ditolak.
        ->assertSeeInOrder([
            'Citra Kesra Ditolak',
            'badge badge-success">Disetujui',
            'badge badge-success">Disetujui',
            'badge badge-danger">Ditolak',
        ], false);
});

test('catpil dan kampus membuka halaman status verifikasi sebagai rekap', function (User $admin) {
    $profil = User::factory()->standardUser()->create();
    profilStatus($profil, 'Citra Rekap Bersama', ['verif_catpil' => 'setuju'], $this->prodi->id);

    actingAs($admin)
        ->get(route('admin.kesra.status'))
        ->assertOk()
        ->assertSee('Status Verifikasi Profil Mahasiswa')
        ->assertSee('Citra Rekap Bersama');
})->with([
    'catpil' => fn () => User::factory()->catpil()->create(['email' => 'catpil-status@test.com']),
    'kampus' => fn () => User::factory()->kampusAdmin()->create(['email' => 'kampus-status@test.com']),
]);

test('status verifikasi murni rekap: tidak ada kolom aksi bagi siapa pun', function (User $viewer) {
    $siap = User::factory()->standardUser()->create();
    profilStatus($siap, 'Citra Siap Dikerjakan', ['verif_kampus' => 'setuju'], $this->prodi->id);

    // Sekalipun viewer adalah kesra/super_admin yang berhak membuka halaman
    // keputusan, halaman status ini hanya rekap -- tidak ada tombol "Putuskan"
    // maupun tautan ke halaman keputusan. Keputusan kesra diambil dari antrean.
    actingAs($viewer)
        ->get(route('admin.kesra.status'))
        ->assertOk()
        ->assertSee('Citra Siap Dikerjakan')
        ->assertDontSee('<th>Aksi</th>', false)
        ->assertDontSee(route('admin.kesra.lihat', $siap), false)
        ->assertDontSee('Putuskan');
})->with([
    'kesra' => fn () => User::factory()->admin()->create(['email' => 'kesra-rekap@test.com']),
    'super_admin' => fn () => User::factory()->superAdmin()->create(['email' => 'super-rekap@test.com']),
    'catpil' => fn () => User::factory()->catpil()->create(['email' => 'catpil-rekap@test.com']),
    'kampus' => fn () => User::factory()->kampusAdmin()->create(['email' => 'kampus-rekap@test.com']),
]);

test('role user tidak dapat membuka halaman status verifikasi', function () {
    $user = User::factory()->standardUser()->create();

    actingAs($user)
        ->get(route('admin.kesra.status'))
        ->assertForbidden();
});

test('filter status catpil dan kampus dihapus: halaman selalu rekap penuh', function () {
    $catpilSetuju = User::factory()->standardUser()->create();
    profilStatus($catpilSetuju, 'Citra Disetujui Catpil', ['verif_catpil' => 'setuju'], $this->prodi->id);

    $catpilRevisi = User::factory()->standardUser()->create();
    profilStatus($catpilRevisi, 'Citra Revisi Catpil', ['verif_catpil' => 'revisi'], $this->prodi->id);

    $kampusTolak = User::factory()->standardUser()->create();
    profilStatus($kampusTolak, 'Citra Ditolak Kampus', ['verif_catpil' => 'setuju', 'verif_kampus' => 'tolak'], $this->prodi->id);

    // Select filter per tahap tidak dirender lagi (permintaan pengguna).
    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.status'))
        ->assertOk()
        ->assertDontSee('name="verif_catpil"', false)
        ->assertDontSee('name="verif_kampus"', false);

    // Param lama di URL diabaikan: daftar tetap rekap penuh, bukan yang disaring.
    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.status', ['verif_catpil' => 'setuju']))
        ->assertOk()
        ->assertSee('Citra Disetujui Catpil')
        ->assertSee('Citra Revisi Catpil')
        ->assertSee('Citra Ditolak Kampus');

    actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.status', ['verif_kampus' => 'tolak']))
        ->assertOk()
        ->assertSee('Citra Disetujui Catpil')
        ->assertSee('Citra Revisi Catpil')
        ->assertSee('Citra Ditolak Kampus');
});

test('filter lokasi kampus, fakultas, dan jurusan menyaring profil dan saling bertingkat', function () {
    $ti = User::factory()->standardUser()->create();
    profilStatus($ti, 'Citra ULM TI', ['verif_catpil' => 'setuju'], $this->prodi->id);

    $si = User::factory()->standardUser()->create();
    profilStatus($si, 'Citra ULM SI', ['verif_catpil' => 'setuju'], $this->prodiKedua->id);

    $uniska = User::factory()->standardUser()->create();
    profilStatus($uniska, 'Citra Uniska Akuntansi', ['verif_catpil' => 'setuju'], $this->prodiLain->id);

    $statusRoute = function (array $filters) {
        return route('admin.kesra.status', $filters);
    };

    // Tingkat 1: kampus.
    actingAs($this->kesraAdmin)
        ->get($statusRoute(['kampus_id' => $this->kampus->id]))
        ->assertOk()
        ->assertSee('Citra ULM TI')
        ->assertSee('Citra ULM SI')
        ->assertDontSee('Citra Uniska Akuntansi');

    // "Memilih salah satu": fakultas (atau jurusan) boleh dipakai sendirian,
    // tanpa memilih induknya lebih dulu -- select tidak pernah disabled.
    actingAs($this->kesraAdmin)
        ->get($statusRoute(['fakultas_id' => $this->fakultas->id]))
        ->assertOk()
        ->assertSee('Citra ULM TI')
        ->assertSee('Citra ULM SI')
        ->assertDontSee('Citra Uniska Akuntansi');

    actingAs($this->kesraAdmin)
        ->get($statusRoute(['jurusan_id' => $this->prodi->id]))
        ->assertOk()
        ->assertSee('Citra ULM TI')
        ->assertDontSee('Citra ULM SI')
        ->assertDontSee('Citra Uniska Akuntansi');

    actingAs($this->kesraAdmin)
        ->get($statusRoute(['jurusan_id' => $this->prodiLain->id]))
        ->assertOk()
        ->assertSee('Citra Uniska Akuntansi')
        ->assertDontSee('Citra ULM TI');

    // "Berbanyak / bertingkat": kampus + fakultas + jurusan yang sejalan.
    actingAs($this->kesraAdmin)
        ->get($statusRoute([
            'kampus_id' => $this->kampus->id,
            'fakultas_id' => $this->fakultas->id,
            'jurusan_id' => $this->prodiKedua->id,
        ]))
        ->assertOk()
        ->assertSee('Citra ULM SI')
        ->assertDontSee('Citra ULM TI')
        ->assertDontSee('Citra Uniska Akuntansi');

    // Soft coherence: nilai yang bertentangan dengan induk yang TURUT dipilih
    // diabaikan (bukan mengosongkan daftar) -- fakultas milik kampus lain, dan
    // jurusan milik fakultas lain, keduanya di-drop ke level yang valid.
    actingAs($this->kesraAdmin)
        ->get($statusRoute([
            'kampus_id' => $this->kampus->id,
            'fakultas_id' => $this->fakultasLain->id,
        ]))
        ->assertOk()
        ->assertSee('Citra ULM TI')
        ->assertSee('Citra ULM SI')
        ->assertDontSee('Citra Uniska Akuntansi');

    actingAs($this->kesraAdmin)
        ->get($statusRoute([
            'kampus_id' => $this->kampus->id,
            'fakultas_id' => $this->fakultas->id,
            'jurusan_id' => $this->prodiLain->id,
        ]))
        ->assertOk()
        ->assertSee('Citra ULM TI')
        ->assertSee('Citra ULM SI')
        ->assertDontSee('Citra Uniska Akuntansi');
});

test('filter status verifikasi memakai kisi 4 kolom dan tombol Terapkan/Reset di baris bawah', function () {
    $response = actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.status'))
        ->assertOk();

    $html = $response->getContent();

    // Tiga tingkat filter lokasi dibagi rata 4 kolom (`col-md-3`), bukan
    // `col-md-4` -- pola yang sama dengan halaman Penerima Beasiswa.
    expect(substr_count($html, 'class="col-md-3 mb-2 mb-md-0"'))->toBe(3);
    expect($html)->not->toContain('class="col-md-4 mb-2 mb-md-0"');

    // Tombol Terapkan/Reset SEJAJAR dengan select di dalam SATU `form-row`
    // (bukan baris form terpisah di bawahnya). Tombol tetap setelah select
    // Program Studi, rata bawah lewat `align-items-end`.
    expect(substr_count($html, 'form-row align-items-end'))->toBe(1);
    expect($html)->not->toContain('align-items-end mt-2');
    $response->assertSeeInOrder(['filter-jurusan', 'Terapkan', 'Reset'], false);

    // Tabel hanya diinisialisasi SEKALI; penjaga ini mencegah toolbar
    // "Tampilkan N data"/"Cari:" tertanam dobel di halaman.
    expect($html)->toContain("isDataTable('#statusTable')")
        ->toContain("$('#statusTable').DataTable(");
});
