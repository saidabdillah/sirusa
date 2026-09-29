<?php

use App\Exports\CapilVerifikasiExport;
use App\Exports\KampusVerifikasiExport;
use App\Exports\KesraVerifikasiExport;
use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\VerifikasiAntrean;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('verifikasi', 'admin', 'export');

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->kampusLain = Kampus::create(['nama_kampus' => 'Universitas Antapanas']);

    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->prodiLain = $this->kampusLain
        ->fakultas()->create(['nama' => 'Fakultas Ekonomi'])
        ->prodi()->create(['nama' => 'Akuntansi']);

    $this->capilAdmin = User::factory()->capil()->create(['email' => 'capil@test.com']);
    $this->kampusAdmin = User::factory()->kampusAdmin()->create([
        'email' => 'kampus@test.com',
        'kampus_id' => $this->kampus->id,
    ]);
    $this->kampusAdminLain = User::factory()->kampusAdmin()->create([
        'email' => 'kampus2@test.com',
        'kampus_id' => $this->kampusLain->id,
    ]);
    $this->kesraAdmin = User::factory()->admin()->create(['email' => 'kesra@test.com']);
});

/**
 * Profil mahasiswa pada tahap tertentu, plus satu pendaftaran untuk tahap
 * yangmemerlukannya (kampus ke atas).
 */
function buatPemohon(User $user, string $stage, Prodi $prodi, ?string $nama = null): UserProfile
{
    $stages = [
        'capil' => ['verif_capil' => 'menunggu', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kampus' => ['verif_capil' => 'setuju', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kesra' => ['verif_capil' => 'setuju', 'verif_kampus' => 'setuju', 'verif_kesra' => 'menunggu'],
    ];

    $suffix = str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);

    $profile = $user->profile()->create(array_merge([
        'nama_lengkap' => $nama ?? "Ahmad Fauzi #{$user->id}",
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
        'provinsi' => 'Kalimantan Selatan',
        'kabupaten_kota' => 'Balangan',
        'prodi_id' => $prodi->id,
        'ipk' => 3.5,
        'semester' => 5,
        'ukt' => 1500000,
        'desil' => 3,
        'ikut_kk' => 'ayah',
        'nama_ayah' => 'Ayah Ahmad',
        'pekerjaan_ayah' => 'Petani',
    ], $stages[$stage]));

    if ($stage !== 'capil') {
        Applicant::create([
            'user_id' => $user->id,
            'beasiswa_id' => beasiswaUntukEkspor()->id,
            'status' => 'verifikasi',
        ]);
    }

    return $profile;
}

function beasiswaUntukEkspor(): Scholarship
{
    return Scholarship::query()->first() ?: Scholarship::factory()->create([
        'nama' => 'Beasiswa Ekspor',
        'kampus_id' => test()->kampus->id,
        'ipk_minimal' => 0,
        'semester_minimal' => 0,
        'status' => 'aktif',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addMonths(2),
    ]);
}

/**
 * Muat hasil unduhan ke dalam sheet PhpSpreadsheet supaya nilai sel dan tipe
 * datanya bisa diperiksa apa adanya.
 */
function loadSheet(TestResponse $response): Worksheet
{
    $path = tempnam(sys_get_temp_dir(), 'ekspor').'.xlsx';
    file_put_contents($path, $response->streamedContent());

    $sheet = (new XlsxReader)->load($path)->getActiveSheet();

    @unlink($path);

    return $sheet;
}

/**
 * Ubah sheet menjadi daftar baris associative berdasarkan nama kolom di header.
 *
 * Nilai sel dibaca tanpa format tampilan, jadi yang terlihat adalah apa yang
 * benar-benar ditulis ke file.
 *
 * @return list<array<string, mixed>>
 */
function barisSheet(Worksheet $sheet): array
{
    $rows = $sheet->rangeToArray('A1:ZZ1000', null, false);
    $headings = array_map(fn ($heading) => (string) $heading, array_shift($rows) ?: []);

    return array_map(
        fn (array $row) => array_combine($headings, $row),
        array_filter($rows, fn (array $row) => trim((string) ($row[1] ?? '')) !== ''),
    );
}

/**
 * @return list<array<string, mixed>>
 */
function bacaSheet(TestResponse $response): array
{
    return barisSheet(loadSheet($response));
}

/**
 * Baris judul kolom, tanpa sel kosong hasilzá padding dari `rangeToArray()` yang
 * selalu mengisi sampai kolom terakhir yang diminta.
 *
 * @return list<string>
 */
function judulSheet(TestResponse $response): array
{
    $baris = loadSheet($response)->rangeToArray('A1:ZZ1', null, false)[0] ?? [];

    return array_map('strval', array_slice($baris, 0, count(array_filter($baris, fn ($v) => $v !== null && $v !== ''))));
}

/**
 * @return list<string>
 */
function namaDiekspor(TestResponse $response): array
{
    return array_column(bacaSheet($response), 'Nama Lengkap');
}

/**
 * Indeks kolom (1-based) sesuai judulnya, supaya tipe datanya bisa dicek
 * tanpa bergantung pada urutan kolom yang hardcoded di test.
 */
function indeksKolom(Worksheet $sheet, string $judul): int
{
    $headers = $sheet->rangeToArray('A1:ZZ1', null, false)[0] ?? [];
    $index = array_search($judul, array_map(fn ($h) => (string) $h, $headers), true);

    expect($index)->not->toBeFalse();

    return $index + 1;
}

test('unduhan capil memuat kolom identitas dan mengabaikan data akademik', function () {
    buatPemohon(User::factory()->standardUser()->create(['email' => 'mhs@test.com']), 'capil', $this->prodi, 'Capil Satu');

    $rows = bacaSheet(actingAs($this->capilAdmin)->get(route('admin.capil.export')));

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['Nama Lengkap'])->toBe('Capil Satu')
        ->and($rows[0]['NIK'])->toStartWith('6302000000')
        ->and($rows[0]['No. Kartu Keluarga'])->toStartWith('6302000001')
        ->and($rows[0]['Tanggal Lahir'])->toBe('01/01/2000')
        ->and($rows[0]['Desil'])->toBe('3')
        ->and($rows[0]['Kartu Keluarga Diikuti'])->toBe('Ayah')
        ->and($rows[0]['Email'])->toBe('mhs@test.com')
        ->and($rows[0]['Status Capil'])->toBe('Menunggu')
        // Tahap Capil tidak menilai IPK, NIM, UKT, maupun dokumen kampus.
        ->and(array_keys($rows[0]))->not->toContain('IPK', 'NIM', 'UKT/SPP', 'Bukti Pembayaran UKT/SPP');
});

test('unduhan kampus memprioritaskan kolom akademik dan UKT ditulis sebagai angka', function () {
    buatPemohon(User::factory()->standardUser()->create(['email' => 'mhs@test.com']), 'kampus', $this->prodi, 'Kampus Satu');

    $sheet = loadSheet(actingAs($this->kampusAdmin)->get(route('admin.kampusverif.export')));

    $rows = barisSheet($sheet);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['Nama Lengkap'])->toBe('Kampus Satu')
        ->and($rows[0]['Program Studi'])->toBe('Teknik Informatika')
        ->and($rows[0]['Fakultas'])->toBe('Fakultas Teknik')
        ->and($rows[0]['Kampus'])->toBe('Universitas Lambung Mangkurat')
        ->and($rows[0]['Status Capil'])->toBe('Disetujui')
        ->and($rows[0]['Status Kampus'])->toBe('Menunggu')
        ->and($rows[0]['UKT/SPP'])->toBe('1500000')
        // UKT harus sel numerik, bukan teks "Rp 1.500.000", supaya masih bisa
        // dijumlahkan dan disaring di Excel.
        ->and($sheet->getCell([indeksKolom($sheet, 'UKT/SPP'), 2])->getDataType())->toBe(DataType::TYPE_NUMERIC)
        ->and($sheet->getCell([indeksKolom($sheet, 'IPK'), 2])->getDataType())->toBe(DataType::TYPE_NUMERIC)
        // kolom yang memang teks tetap teks, supaya tidak ikut terhitung.
        ->and($sheet->getCell([indeksKolom($sheet, 'Nama Lengkap'), 2])->getDataType())->toBe(DataType::TYPE_STRING);
});

test('unduhan kesra menambah data pendaftaran dan statusnya', function () {
    $user = User::factory()->standardUser()->create(['email' => 'mhs@test.com']);
    $profile = buatPemohon($user, 'kesra', $this->prodi, 'Kesra Satu');

    Applicant::where('user_id', $user->id)->update(['status' => 'diterima']);

    // Filter `semua`, karena tanpa filter unduhan Kesra hanya berisi yang masih
    // menunggu -- sama persis dengan antrean di layar.
    $rows = bacaSheet(actingAs($this->kesraAdmin)->get(route('admin.kesra.export', ['filter' => 'semua'])));

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['Nama Lengkap'])->toBe('Kesra Satu')
        ->and($rows[0]['Beasiswa'])->toBe('Beasiswa Ekspor')
        // Label di Excel memakai istilah UI yang sama dengan badge, bukan nilai
        // mentah database: `diterima` tampil sebagai "Disetujui".
        ->and($rows[0]['Status Pendaftaran'])->toBe('Disetujui')
        ->and($rows[0]['Nama Orang Tua/Wali'])->toBe('Ayah Ahmad')
        ->and($rows[0]['UKT/SPP'])->toBe('1500000')
        ->and($rows[0]['Status Kesra'])->toBe('Menunggu');
});

test('unduhan kesra memuat persis isi antrean kesra yang sedang dibuka', function () {
    $menunggu = User::factory()->standardUser()->create(['email' => 'menunggu@test.com']);
    buatPemohon($menunggu, 'kesra', $this->prodi, 'Antrean Menunggu');

    $diterima = User::factory()->standardUser()->create(['email' => 'diterima@test.com']);
    buatPemohon($diterima, 'kesra', $this->prodi, 'Antrean Disetujui');
    Applicant::where('user_id', $diterima->id)->update(['status' => 'diterima']);

    // Tanpa filter: yang menunggu, sama seperti tabelnya.
    expect(namaDiekspor(actingAs($this->kesraAdmin)->get(route('admin.kesra.export'))))
        ->toBe(['Antrean Menunggu']);

    // Filter keputusan: kebalikannya.
    expect(namaDiekspor(actingAs($this->kesraAdmin)->get(route('admin.kesra.export', ['filter' => 'diterima']))))
        ->toBe(['Antrean Disetujui']);

    // `semua` menggabungkan keduanya -- tidak ada baris yang hilang di antara
    // tabel dan file, dan tidak ada yang muncul tiba-tiba.
    expect(namaDiekspor(actingAs($this->kesraAdmin)->get(route('admin.kesra.export', ['filter' => 'semua']))))
        ->toHaveCount(2);

    // Pendaftaran yang sudah dibatalkan mahasiswa hanya muncul di `semua`; di
    // layar pun tidak ada tombol yang mengarah ke form keputusannya.
    $dibatalkan = User::factory()->standardUser()->create(['email' => 'batal@test.com']);
    buatPemohon($dibatalkan, 'kesra', $this->prodi, 'Antrean Dibatalkan');
    Applicant::where('user_id', $dibatalkan->id)->update(['status' => 'dibatalkan']);

    expect(namaDiekspor(actingAs($this->kesraAdmin)->get(route('admin.kesra.export', ['filter' => 'semua']))))
        ->toContain('Antrean Dibatalkan')
        ->and(namaDiekspor(actingAs($this->kesraAdmin)->get(route('admin.kesra.export', ['filter' => 'dibatalkan']))))
        ->toBe(['Antrean Dibatalkan']);
});

test('setiap tahap mengunduh antrean tahapnya sendiri', function () {
    buatPemohon(User::factory()->standardUser()->create(), 'capil', $this->prodi, 'Masih Menunggu Capil');
    buatPemohon(User::factory()->standardUser()->create(), 'kampus', $this->prodi, 'Menunggu Kampus');
    buatPemohon(User::factory()->standardUser()->create(), 'kesra', $this->prodi, 'Menunggu Kesra');

    $capil = namaDiekspor(actingAs($this->capilAdmin)->get(route('admin.capil.export')));
    $kampus = namaDiekspor(actingAs($this->kampusAdmin)->get(route('admin.kampusverif.export')));
    $kesra = namaDiekspor(actingAs($this->kesraAdmin)->get(route('admin.kesra.export')));

    // Capil masih boleh menarik kembali keputusannya selama tahap berikutnya
    // belum diputuskan, jadi mahasiswa yang menunggu di Campus ikut terlihat.
    // Yang sudah diputuskan sampai Campus selesai, sebaliknya, tidak muncul
    // lagi di Capil karena mengubahnya akan membatalkan kerjaan hilir.
    expect($capil)->toContain('Masih Menunggu Capil', 'Menunggu Kampus')
        ->and($capil)->not->toContain('Menunggu Kesra')
        // Sebaliknya, tidak ada yang bisa naik ke tahap yang lebih tinggi
        // sebelum lewat tahap sebelumnya. Yang paling butuh tindakan mahasiswa
        // (perlu perbaikan, lalu masih menunggu) muncul lebih dulu.
        ->and($kampus)->toBe(['Menunggu Kampus', 'Menunggu Kesra'])
        ->and($kesra)->toBe(['Menunggu Kesra']);
});

test('urutan antrean menempatkan yang perlu diperbaiki lebih dulu', function () {
    $perlu = buatPemohon(User::factory()->standardUser()->create(), 'capil', $this->prodi, 'Budi Perlu Perbaikan');
    $menunggu = buatPemohon(User::factory()->standardUser()->create(), 'capil', $this->prodi, 'Ani Menunggu');
    $ditolak = buatPemohon(User::factory()->standardUser()->create(), 'capil', $this->prodi, 'Citra Ditolak');
    $disetujui = buatPemohon(User::factory()->standardUser()->create(), 'capil', $this->prodi, 'Dewi Disetujui');

    $perlu->update(['verif_capil' => 'revisi']);
    $ditolak->update(['verif_capil' => 'tolak']);
    $disetujui->update(['verif_capil' => 'setuju']);

    // Rank antrean: perlu perbaikan 0, menunggu 1, tolak 2, disetujui 3.
    expect(namaDiekspor(actingAs($this->capilAdmin)->get(route('admin.capil.export'))))
        ->toBe(['Budi Perlu Perbaikan', 'Ani Menunggu', 'Citra Ditolak', 'Dewi Disetujui']);
});

test('unduhan admin kampus dibatasi pada kampusnya sendiri', function () {
    buatPemohon(User::factory()->standardUser()->create(), 'kampus', $this->prodi, 'Mahasiswa Kampus Sendiri');
    buatPemohon(User::factory()->standardUser()->create(), 'kampus', $this->prodiLain, 'Mahasiswa Kampus Lain');

    $rows = bacaSheet(actingAs($this->kampusAdmin)->get(route('admin.kampusverif.export')));

    expect(array_column($rows, 'Nama Lengkap'))->toBe(['Mahasiswa Kampus Sendiri']);
});

test('unduhan mengikuti filter status yang sedang aktif', function () {
    $disetujui = buatPemohon(User::factory()->standardUser()->create(), 'capil', $this->prodi, 'Sudah Disetujui');
    $disetujui->update(['verif_capil' => 'setuju']);
    buatPemohon(User::factory()->standardUser()->create(), 'capil', $this->prodi, 'Masih Menunggu');

    $semuaFilter = bacaSheet(actingAs($this->capilAdmin)->get(route('admin.capil.export')));
    $hanyaSetuju = bacaSheet(actingAs($this->capilAdmin)->get(route('admin.capil.export', ['filter' => 'setuju'])));

    expect(array_column($semuaFilter, 'Nama Lengkap'))
        ->toContain('Sudah Disetujui', 'Masih Menunggu')
        ->and(array_column($hanyaSetuju, 'Nama Lengkap'))->toBe(['Sudah Disetujui'])
        ->and($hanyaSetuju[0]['Status Capil'])->toBe('Disetujui');
});

test('unduhan hanya bisa diakses tahap yang punya grant menu', function () {
    buatPemohon(User::factory()->standardUser()->create(), 'capil', $this->prodi);

    actingAs($this->capilAdmin)->get(route('admin.kampusverif.export'))->assertForbidden();
    actingAs($this->kampusAdmin)->get(route('admin.capil.export'))->assertForbidden();
    actingAs($this->kesraAdmin)->get(route('admin.capil.export'))->assertForbidden();
    actingAs($this->kampusAdmin)->get(route('admin.kesra.export'))->assertForbidden();

    // Yang punya grant tetap boleh mengunduh.
    actingAs($this->capilAdmin)->get(route('admin.capil.export'))->assertOk();
    actingAs($this->kampusAdmin)->get(route('admin.kampusverif.export'))->assertOk();
    actingAs($this->kesraAdmin)->get(route('admin.kesra.export'))->assertOk();
});

test('unduhan mengirim file xlsx dengan nama sesuai tahap dan filter', function () {
    buatPemohon(User::factory()->standardUser()->create(), 'kampus', $this->prodi);

    $response = actingAs($this->kampusAdmin)->get(route('admin.kampusverif.export', ['filter' => 'menunggu']));

    $response->assertOk()
        ->assertHeader(
            'content-disposition',
            'attachment; filename=verifikasi-kampus-menunggu-'.now()->format('Y-m-d').'.xlsx',
        );

    expect($response->headers->get('content-type'))
        ->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('halaman verifikasi menampilkan tombol unduh sesuai grant menu', function () {
    actingAs($this->capilAdmin)->get(route('admin.capil.index'))
        ->assertOk()
        ->assertSee('Unduh Excel')
        ->assertSee(route('admin.capil.export'), false);

    actingAs($this->capilAdmin)->get(route('admin.kampusverif.index'))->assertForbidden();
});

// ─── Perluasan kolom (spesifikasi batch 2, poin 23) ──────────────────────
//
// Ekspor harus memuat setiap field yang benar-benar ada di database. Field
// yang tidak punya sumber data TIDAK dikarang: kolom kosong lebih jujur
// daripada kolom yang selalu berisi "-".

test('ekspor capil memuat seluruh kolom yang tersedia di database', function () {
    expect(judulSheet(actingAs($this->capilAdmin)->get(route('admin.capil.export'))))->toBe([
        'No',
        'Nama Lengkap',
        'NIK',
        'No. Kartu Keluarga',
        'Jenis Kelamin',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Agama',
        'Telepon',
        'Provinsi',
        'Kabupaten/Kota',
        'Kecamatan',
        'Desa/Kelurahan',
        'Alamat',
        'Email',
        'Kartu Keluarga Diikuti',
        'Nama Orang Tua/Wali',
        'NIK Orang Tua/Wali',
        'Pekerjaan Orang Tua/Wali',
        'Desil',
        'Status Capil',
        'Catatan Verifikasi',
    ]);
});

test('ekspor kampus memuat seluruh kolom yang tersedia di database', function () {
    expect(judulSheet(actingAs($this->kampusAdmin)->get(route('admin.kampusverif.export'))))->toBe([
        'No',
        'Nama Lengkap',
        'NIM',
        'NIK',
        'No. Kartu Keluarga',
        'Jenis Kelamin',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Telepon',
        'Provinsi',
        'Kabupaten/Kota',
        'Kecamatan',
        'Desa/Kelurahan',
        'Alamat',
        'Email',
        'Program Studi',
        'Fakultas',
        'Kampus',
        'IPK',
        'Semester',
        'UKT/SPP',
        'Bukti Pembayaran UKT/SPP',
        'Desil',
        'Kartu Keluarga Diikuti',
        'Nama Orang Tua/Wali',
        'NIK Orang Tua/Wali',
        'Pekerjaan Orang Tua/Wali',
        'Status Capil',
        'Status Kampus',
        'Catatan Verifikasi',
    ]);
});

test('ekspor kesra menggabungkan identitas, akademik, dan pendaftaran', function () {
    expect(judulSheet(actingAs($this->kesraAdmin)->get(route('admin.kesra.export'))))->toBe([
        'No',
        'Nama Lengkap',
        'NIK',
        'No. Kartu Keluarga',
        'NIM',
        'Jenis Kelamin',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Agama',
        'Telepon',
        'Provinsi',
        'Kabupaten/Kota',
        'Kecamatan',
        'Desa/Kelurahan',
        'Alamat',
        'Email',
        'Program Studi',
        'Fakultas',
        'Kampus',
        'IPK',
        'Semester',
        'UKT/SPP',
        'Bukti Pembayaran UKT/SPP',
        'Desil',
        'Kartu Keluarga Diikuti',
        'Nama Orang Tua/Wali',
        'NIK Orang Tua/Wali',
        'Pekerjaan Orang Tua/Wali',
        'Beasiswa',
        'Status Pendaftaran',
        'Catatan Pendaftaran',
        'Status Capil',
        'Status Kampus',
        'Status Kesra',
        'Catatan Verifikasi',
    ]);
});

test('tidak ada kolom yang tidak punya sumber data di database', function (string $route, string $role) {
    $rows = bacaSheet(actingAs($this->{$role})->get(route($route)));

    // Field yang diminta spesifikasi tapi tidak ada kolomnya di
    // `profil_pengguna`/`pendaftar`. Kalau suatu saat salah satunya ditambahkan,
    // test ini sengaja gagal supaya kolomnya ikut diekspor.
    $tanpaSumberData = [
        'RT',
        'RW',
        'Status Pernikahan',
        'Tempat Lahir Ayah',
        'Tanggal Lahir Ayah',
        'Tempat Lahir Ibu',
        'Tanggal Lahir Ibu',
        'Penghasilan Ayah',
        'Penghasilan Ibu',
        'Desil Ayah',
        'Desil Ibu',
        'Tanggal Verifikasi',
        'Jenjang',
        'Status Mahasiswa',
    ];

    expect(array_keys($rows[0] ?? []))->not->toContain(...$tanpaSumberData);
})->with([
    'capil' => ['admin.capil.export', 'capilAdmin'],
    'kampus' => ['admin.kampusverif.export', 'kampusAdmin'],
    'kesra' => ['admin.kesra.export', 'kesraAdmin'],
]);

test('setiap tahap menampilkan keputusan tahap sebelumnya sebagai kolom sendiri', function () {
    buatPemohon(User::factory()->standardUser()->create(), 'kesra', $this->prodi, 'Lantai Tiga');

    $rows = bacaSheet(actingAs($this->kesraAdmin)->get(route('admin.kesra.export')));

    // Pemutus tahap akhir perlu tahu dasar keputusannya, dan "Status" tanpa
    // nama tahap akan ambigu begitu ada lebih dari satu kolom status.
    expect($rows[0]['Status Capil'])->toBe('Disetujui')
        ->and($rows[0]['Status Kampus'])->toBe('Disetujui')
        ->and($rows[0]['Status Kesra'])->toBe('Menunggu');
});

test('lewat 26 kolom, gaya header tetap benar-benar diterapkan', function () {
    // `range('A', $lastColumn)` hanya bisa menghitung huruf tunggal. Begitu
    // kolom melewati `Z`, fungsi itu melempar dan unduhan jadi 500.
    $ekspor = new KesraVerifikasiExport(
        new VerifikasiAntrean('kesra'),
        null
    );

    expect(count($ekspor->headings()))->toBeGreaterThan(26)
        ->and($ekspor->toSpreadsheet()->getActiveSheet()->getColumnDimension('AI')->getAutoSize())->toBeTrue();
});

test('judul kolom tidak ada yang kosong atau kembar', function () {
    foreach ([CapilVerifikasiExport::class, KampusVerifikasiExport::class, KesraVerifikasiExport::class] as $class) {
        $headings = (new $class(new VerifikasiAntrean('kesra')))->headings();

        expect($headings)->not->toContain('')
            ->and(array_filter($headings, fn ($h) => trim($h) === ''))->toBe([])
            // `barisSheet()` menggabungkan baris dengan nama kolom sebagai kunci,
            // jadi judul kembar akan menimpa kolomnya diam-diam.
            ->and(count(array_unique($headings)))->toBe(count($headings));
    }
});

// ─── Nomor identitas ditulis sebagai teks ────────────────────────────────
//
// NIK 16 digit dan sering diawali region code yang tidak nol, jadi kalau
// ditulis sebagai angka Excel akan memotong digit terakhir (batas 15 digit
// bermakna) -- rekaman yang benar jadi salah baca tanpa ada error sama sekali.

test('nomor identitas ditulis sebagai teks, bukan angka', function (string $route, string $role, string $judul, string $atribut, string $stage) {
    // Antrean tiap tahap hanya berisi yang sudah lewat tahap sebelumnya, jadi
    // fixture harus dibuat pada tahap yang sama dengan ekspornya.
    $profile = buatPemohon(User::factory()->standardUser()->create(), $stage, $this->prodi, 'Nomor Panjang');
    $profile->update([
        'nik' => '6302000000123456',
        'nim' => '0123456789',
        'no_kk' => '6302000000987654',
    ]);

    $sheet = loadSheet(actingAs($this->{$role})->get(route($route)));
    $cell = $sheet->getCell([indeksKolom($sheet, $judul), 2]);

    expect($cell->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($cell->getValue())->toBe($profile->{$atribut});
})->with([
    'NIK di ekspor capil' => ['admin.capil.export', 'capilAdmin', 'NIK', 'nik', 'capil'],
    'NIK di ekspor kesra' => ['admin.kesra.export', 'kesraAdmin', 'NIK', 'nik', 'kesra'],
    'NIM di ekspor kesra' => ['admin.kesra.export', 'kesraAdmin', 'NIM', 'nim', 'kesra'],
    'Nomor KK di ekspor capil' => ['admin.capil.export', 'capilAdmin', 'No. Kartu Keluarga', 'no_kk', 'capil'],
]);

test('angka yang memang harus dihitung tetap ditulis sebagai angka', function (string $judul) {
    buatPemohon(User::factory()->standardUser()->create(), 'kampus', $this->prodi, 'Angkadiohitung');

    $sheet = loadSheet(actingAs($this->kampusAdmin)->get(route('admin.kampusverif.export')));
    $cell = $sheet->getCell([indeksKolom($sheet, $judul), 2]);

    expect($cell->getDataType())->toBe(DataType::TYPE_NUMERIC);
})->with([
    'IPK' => 'IPK',
    'Semester' => 'Semester',
    'UKT/SPP' => 'UKT/SPP',
    'Desil' => 'Desil',
    'No urut' => 'No',
]);

test('nama berkas dokumen dilepas dari folder penyimpanan', function () {
    $profile = buatPemohon(User::factory()->standardUser()->create(), 'kampus', $this->prodi, 'Ada Dokumen');
    $profile->update(['dokumen_bukti_ukt' => 'dokumen/bukti-ukt/ahmad-ukt.pdf']);

    $rows = bacaSheet(actingAs($this->kampusAdmin)->get(route('admin.kampusverif.export')));

    expect($rows[0]['Bukti Pembayaran UKT/SPP'])->toBe('ahmad-ukt.pdf');
});

test('dokumen yang belum diunggah ditulis sebagai pagar, bukan sel kosong', function () {
    $profile = buatPemohon(User::factory()->standardUser()->create(), 'kampus', $this->prodi, 'Belum Ada Dokumen');
    $profile->update(['dokumen_bukti_ukt' => null]);

    $rows = bacaSheet(actingAs($this->kampusAdmin)->get(route('admin.kampusverif.export')));

    expect($rows[0]['Bukti Pembayaran UKT/SPP'])->toBe('-');
});

test('blok orang tua mengikuti pilihan kartu keluarga mahasiswa', function (string $ikut, string $label, string $nama, string $nik, string $pekerjaan) {
    $profile = buatPemohon(User::factory()->standardUser()->create(), 'capil', $this->prodi, 'Blok Orang Tua');
    $profile->update([
        'ikut_kk' => $ikut,
        'nama_ayah' => 'Ayah Ahmad',
        'nik_ayah' => '1111111111111111',
        'nama_ibu' => 'Ibu Siti',
        'nik_ibu' => '2222222222222222',
        'nama_wali' => 'Wali Dedi',
        'nik_wali' => '3333333333333333',
        // `pekerjaan_*` adalah enum di migrasi, jadi nilainya harus salah
        // satu dari daftar itu atau CHECK constraint menolaknya.
        'pekerjaan_ayah' => 'Petani',
        'pekerjaan_ibu' => 'Swasta',
        'pekerjaan_wali' => 'Wiraswasta',
    ]);

    $rows = bacaSheet(actingAs($this->capilAdmin)->get(route('admin.capil.export')));

    // NAMA harus ikut NIK: kalau tidak, pembaca tidak tahu nik itu milik siapa.
    // Ketiganya harus berasal dari orang yang sama. Kalau tidak, baris bisa
    // menampilkan nama ayah dengan NIK ibu tanpa ada yang bisa salah baca.
    expect($rows[0]['Kartu Keluarga Diikuti'])->toBe($label)
        ->and($rows[0]['Nama Orang Tua/Wali'])->toBe($nama)
        ->and($rows[0]['NIK Orang Tua/Wali'])->toBe($nik)
        ->and($rows[0]['Pekerjaan Orang Tua/Wali'])->toBe($pekerjaan);
})->with([
    'ayah' => ['ayah', 'Ayah', 'Ayah Ahmad', '1111111111111111', 'Petani'],
    'ibu' => ['ibu', 'Ibu', 'Ibu Siti', '2222222222222222', 'Swasta'],
    'wali' => ['wali', 'Wali', 'Wali Dedi', '3333333333333333', 'Wiraswasta'],
]);
