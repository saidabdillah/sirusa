<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('dashboard', 'admin');

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->capilAdmin = User::factory()->capil()->create(['email' => 'capil@test.com']);
    $this->kampusAdmin = User::factory()->kampusAdmin()->create(['email' => 'kampus@test.com']);
    $this->kesraAdmin = User::factory()->admin()->create(['email' => 'kesra@test.com']);
    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'super@test.com']);
});

/**
 * Mahasiswa dengan profil pada tahap verifikasi tertentu.
 */
function mahasiswaTahap(string $tahap, string $nama): User
{
    $stages = [
        'capil' => ['verif_capil' => 'menunggu', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kampus' => ['verif_capil' => 'setuju', 'verif_kampus' => 'menunggu', 'verif_kesra' => 'menunggu'],
        'kesra' => ['verif_capil' => 'setuju', 'verif_kampus' => 'setuju', 'verif_kesra' => 'menunggu'],
    ];

    $user = User::factory()->standardUser()->create();
    $user->profile()->create(array_merge([
        'nama_lengkap' => $nama,
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => test()->prodi->id,
        'ipk' => 3.5,
        'semester' => 4,
    ], $stages[$tahap]));

    // Tahap kampus baru masuk antrean setelah mahasiswa mendaftar beasiswa, jadi
    // fixture-nya perlu satu pendaftaran. Beasisanya dibuat sekali lalu dipakai
    // ulang supaya indeks unik `[user_id, beasiswa_id]` tidak bermasalah.
    if ($tahap === 'kampus') {
        $beasiswa = Scholarship::query()->where('nama', 'Beasiswa Antrean')->first()
            ?: Scholarship::factory()->create([
                'nama' => 'Beasiswa Antrean',
                'kampus_id' => test()->kampus->id,
                'kampus' => test()->kampus->nama_kampus,
                'ipk_minimal' => 0,
                'semester_minimal' => 0,
                'status' => 'aktif',
                'tanggal_mulai' => now()->subDay(),
                'tanggal_selesai' => now()->addMonth(),
            ]);

        Applicant::create([
            'user_id' => $user->id,
            'beasiswa_id' => $beasiswa->id,
            'status' => 'verifikasi',
        ]);
    }

    return $user;
}

test('capil sees the capil queue dashboard, not the student dashboard', function () {
    mahasiswaTahap('capil', 'Ani Capil');

    actingAs($this->capilAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dasbor Capil')
        ->assertSee('Menunggu Tindakan')
        ->assertSee('Ani Capil');
});

test('kampus admin sees the kampus queue dashboard', function () {
    mahasiswaTahap('kampus', 'Budi Kampus');

    actingAs($this->kampusAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dasbor Kampus')
        ->assertSee('Budi Kampus');
});

test('kesra sees the kesra dashboard with the pending registration queue', function () {
    $user = mahasiswaTahap('kesra', 'Citra Kesra');
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_selesai' => now()->addMonth(),
    ]);
    Applicant::factory()->create(['user_id' => $user->id, 'beasiswa_id' => $beasiswa->id]);

    actingAs($this->kesraAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dasbor Kesra')
        ->assertSee('Pendaftaran Menunggu Putusan')
        ->assertSee('Citra Kesra');
});

test('kesra dashboard has one queue, not a duplicate list of the same people', function () {
    $user = mahasiswaTahap('kesra', 'Citra Kesra');
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_selesai' => now()->addMonth(),
    ]);
    Applicant::factory()->create(['user_id' => $user->id, 'beasiswa_id' => $beasiswa->id]);

    $html = actingAs($this->kesraAdmin)->get(route('dashboard'))->assertOk()->getContent();

    // Kesra memverifikasi satu pendaftaran per mahasiswa, jadi daftar berbasis
    // `verif_kesra` dan daftar berbasis `pendaftar.status` menunjuk orang yang
    // sama. Hanya satu yang boleh tampil.
    expect($html)->not->toContain('Menunggu Tindakan Anda')
        // Satu baris memuat identitas sekaligus beasiswa yang diputuskan.
        ->and($html)->toContain('Beasiswa')
        ->and($html)->toContain('Pendaftaran Menunggu Putusan');
});

test('kesra stat cards count registrations, not profile verdicts', function () {
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_selesai' => now()->addMonth(),
    ]);

    foreach (['verifikasi' => 2, 'diterima' => 1, 'ditolak' => 1, 'dibatalkan' => 1] as $status => $jumlah) {
        foreach (range(1, $jumlah) as $ignored) {
            $user = mahasiswaTahap('kesra', "Citra $status $ignored");

            Applicant::factory()->create([
                'user_id' => $user->id,
                'beasiswa_id' => $beasiswa->id,
                'status' => $status,
            ]);
        }
    }

    $response = actingAs($this->kesraAdmin)->get(route('dashboard'))->assertOk();

    expect($response->viewData('ringkasan'))->toBe([
        'verifikasi' => 2,
        'diterima' => 1,
        'ditolak' => 1,
        'dibatalkan' => 1,
    ]);

    // "Perlu Perbaikan" adalah status profil. Tidak ada kode yang bisa menulisnya
    // di tahap Kesra lagi, jadi menampilkannya hanya menambah angka nol yang
    // selalu nol.
    expect($response->getContent())->toContain('Menunggu Putusan')
        ->not->toContain('Perlu Perbaikan');
});

test('kesra queue count matches the applicant list its menu opens', function () {
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_selesai' => now()->addMonth(),
    ]);

    foreach (range(1, 3) as $ignored) {
        $user = mahasiswaTahap('kesra', "Citra Antre $ignored");

        Applicant::factory()->create([
            'user_id' => $user->id,
            'beasiswa_id' => $beasiswa->id,
        ]);
    }

    // Satu yang sudah diputuskan tidak boleh dihitung sebagai antrean.
    $selesai = mahasiswaTahap('kesra', 'Citra Sudah Diterima');

    Applicant::factory()->create([
        'user_id' => $selesai->id,
        'beasiswa_id' => $beasiswa->id,
        'status' => 'diterima',
    ]);

    $ringkasan = actingAs($this->kesraAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->viewData('ringkasan');

    // Halaman tujuan menu Kesra -- harus punya isi yang sama dengan angkanya.
    $dashboard = actingAs($this->kesraAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    $antrean = actingAs($this->kesraAdmin)
        ->get(route('admin.kesra.index'))
        ->assertOk()
        ->getContent();

    expect($ringkasan['verifikasi'])->toBe(3)
        // Tabel di dasbor boleh menampilkan semua pendaftaran yang masih
        // `verifikasi`, bukan hanya 8 pertama, jadi yang diperiksa di sini hanya
        // bahwa yang sudah diputuskan tidak bocor ke kartu "menunggu".
        ->and($dashboard)->toContain('Citra Antre 1')
        ->and($dashboard)->not->toContain('Citra Sudah Diterima')
        ->and($antrean)->toContain('Citra Antre 1')
        ->and($antrean)->not->toContain('Citra Sudah Diterima')
        // "Lihat Semua" harus mengarah ke antrean Kesra itu juga. Kalau masih ke
        // daftar pendaftar, angkanya benar tapi tempat kerjanya bukan antrean
        // yang sedang dikerjakan admin.
        ->and($dashboard)->toContain(route('admin.kesra.index'))
        ->and($dashboard)->not->toContain(route('admin.pendaftar.index', ['status' => 'verifikasi']));
});

test('the campus column on a stage dashboard shows the real campus name', function () {
    $user = mahasiswaTahap('kesra', 'Ani Kesra');

    Applicant::factory()->create([
        'user_id' => $user->id,
        'beasiswa_id' => Scholarship::factory()->create([
            'kampus_id' => $this->kampus->id,
            'tanggal_selesai' => now()->addMonth(),
        ])->id,
    ]);

    // `kampus` punya kolom `nama_kampus`. Menulis `kampus->nama` membuat Eloquent
    // membalas `null` dan kolomnya tampil sebagai "-", padahal relasinya lengkap.
    actingAs($this->kesraAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Ani Kesra', 'Teknik Informatika', 'Universitas Lambung Mangkurat'], false);
});

test('kolom antrean di dasbor mengikuti tahap yang memverifikasi', function () {
    $capil = mahasiswaTahap('capil', 'Ani Capil');
    $capil->profile->update(['nik' => '6302000000000001', 'no_kk' => '6302000000000002', 'desil' => 2]);

    $html = actingAs($this->capilAdmin)->get(route('dashboard'))->assertOk()->getContent();

    // Capil memeriksa kependudukan, jadi NIK/KK/desil yang tampil. Prodi dan
    // kampus tidak pernah jadi dasar keputusan di tahap ini -- kemunculannya di
    // kartu ini cuma menambah kolom yang tidak dibaca.
    expect($html)->toContain('NIK')
        ->and($html)->toContain('No. Kartu Keluarga')
        ->and($html)->toContain('Desil')
        ->and($html)->toContain('6302000000000001')
        ->and($html)->toContain('Desil 2')
        ->and($html)->not->toContain('Program Studi')
        ->and($html)->not->toContain($this->kampus->nama_kampus);

    $kampus = mahasiswaTahap('kampus', 'Budi Kampus');
    $kampus->profile->update(['nim' => '201000000001', 'ipk' => 3.75]);

    $html = actingAs($this->kampusAdmin)->get(route('dashboard'))->assertOk()->getContent();

    // Kampus memeriksa status mahasiswa, jadi NIM/prodi/IPK.
    expect($html)->toContain('NIM')
        ->and($html)->toContain('IPK')
        ->and($html)->toContain('201000000001')
        ->and($html)->toContain('3.75')
        ->and($html)->toContain('Program Studi')
        ->and($html)->not->toContain('No. Kartu Keluarga')
        ->and($html)->not->toContain('Desil');
});

test('the cross-stage table marks statuses that do not apply to a stage', function () {
    mahasiswaTahap('kesra', 'Citra Kesra');

    $html = actingAs($this->superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    // "Perlu Perbaikan" hanya milik Capil dan Kampus. Di baris Kesra selnya harus
    // tanda hubung -- angka nol akan berarti "tidak ada yang perlu perbaikan",
    // padahal status itu memang tidak berlaku di sana.
    expect($html)->toContain('Perlu Perbaikan')
        ->and($html)->toContain('&mdash;');
});

test('super admin sees the cross-stage overview', function () {
    mahasiswaTahap('capil', 'Ani Capil');
    mahasiswaTahap('kampus', 'Budi Kampus');
    mahasiswaTahap('kesra', 'Citra Kesra');

    actingAs($this->superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Sebaran Antrean per Tahap')
        ->assertSee('Alur Penerimaan')
        ->assertSee('Verifikasi Capil')
        ->assertSee('Verifikasi Kampus')
        ->assertSee('Verifikasi Kesra');
});

test('tidak ada dasbor yang menampilkan widget tahapan berikutnya', function () {
    mahasiswaTahap('capil', 'Ani Capil');
    mahasiswaTahap('kampus', 'Budi Kampus');
    mahasiswaTahap('kesra', 'Citra Kesra');
    $mahasiswa = mahasiswaTahap('capil', 'Dewi Mahasiswa');

    $dasbor = [
        'capil' => actingAs($this->capilAdmin)->get(route('dashboard'))->assertOk()->getContent(),
        'kampus' => actingAs($this->kampusAdmin)->get(route('dashboard'))->assertOk()->getContent(),
        'kesra' => actingAs($this->kesraAdmin)->get(route('dashboard'))->assertOk()->getContent(),
        'super admin' => actingAs($this->superAdmin)->get(route('dashboard'))->assertOk()->getContent(),
        'mahasiswa' => actingAs($mahasiswa)->get(route('dashboard'))->assertOk()->getContent(),
    ];

    foreach ($dasbor as $nama => $html) {
        // Widget ini hanya pernah muncul di tahap Capil dan Kampus, tapi
        // pemeriksaan dilakukan di kelima dasbor supaya widget yang strolling
        // ke halaman lain ikut ketahuan.
        expect($html, $nama)->not->toContain('Tahapan Berikutnya');
    }
});

test('statistik antrean tetap ada di dasbor tahap', function () {
    actingAs($this->capilAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        // Angka dan tabelnya tetap; yang dihapus hanya kartu "Tahapan
        // Berikutnya" beserta navigasi ke tahap lain.
        ->assertSee('Menunggu Tindakan')
        ->assertSee('Perlu Perbaikan')
        ->assertSee('Disetujui')
        ->assertSee('Ditolak')
        ->assertSee('Menunggu Tindakan Anda');
});

/*
| Kartu "Status Profil Saya" dihapus dari dasbor mahasiswa.
|
| Kartu itu menampilkan nama dan kalimat "Data profil Anda sudah tersimpan.
| Status verifikasi tidak ditampilkan di sini", yang keduanya tidak memberi
| tindakan apa pun. Alert profil belum lengkap dipindah keluar kartu supaya
| pengingatnya tetap ada, dan link ke Profil sekarang ada di alert tersebut
| serta di sidebar.
*/

test('student sees the student dashboard', function () {
    $user = mahasiswaTahap('capil', 'Dewi Mahasiswa');

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pendaftaran Saya')
        ->assertSee('Beasiswa Tersedia');
});

test('student dashboard does not repeat the profile name back to the student', function () {
    $user = mahasiswaTahap('capil', 'Dewi Mahasiswa');

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Status Profil Saya')
        ->assertDontSee('Status verifikasi tidak ditampilkan');
});

test('student dashboard still warns about the incomplete profile', function () {
    $user = mahasiswaTahap('capil', 'Dewi Mahasiswa');

    // Fixture `mahasiswaTahap()` tidak mengisi UKT, desil, dan dokumen, jadi
    // daftar field yang kurang harus non-empty dan alert-nya harus tampil.
    expect($user->getMissingProfileFields())->not->toBeEmpty();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Profil belum lengkap.')
        ->assertSee('Lengkapi profil sekarang');
});

test('student dashboard drops the warning once the profile is complete', function () {
    $user = mahasiswaTahap('capil', 'Dewi Mahasiswa');

    $user->profile->update([
        'nik' => '6302000000000001',
        'no_kk' => '6302000000000009',
        'ikut_kk' => 'ayah',
        'ukt' => 3500000,
        'desil' => 3,
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        'dokumen_sktm' => 'profil/1/sktm.pdf',
        'dokumen_transkrip' => 'profil/1/transkrip.pdf',
        'dokumen_surat_aktif' => 'profil/1/surat.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/ukt.pdf',
        'nama_ayah' => 'Ayah',
        'nik_ayah' => '6302000000000002',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu',
        'nik_ibu' => '6302000000000003',
        'pekerjaan_ibu' => 'Petani',
        'ktp_ayah' => 'profil/1/ktp-ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp-ibu.pdf',
    ]);

    expect($user->getMissingProfileFields())->toBeEmpty();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Profil belum lengkap.');
});

test('stage dashboard count matches the stage queue the admin actually opens', function () {
    mahasiswaTahap('capil', 'Ani Capil');
    mahasiswaTahap('capil', 'Budi Capil');
    // Sudah terverifikasi penuh, tidak ada yang perlu ditinjau di tahap manapun.
    $selesai = mahasiswaTahap('kesra', 'Citra Sudah Selesai');
    $selesai->profile->update(['verif_kesra' => 'setuju']);

    $response = actingAs($this->capilAdmin)->get(route('dashboard'))->assertOk();

    expect($response->viewData('ringkasan')['menunggu'])->toBe(2);

    actingAs($this->capilAdmin)
        ->get(route('admin.capil.index'))
        ->assertOk()
        ->assertSee('Ani Capil')
        ->assertSee('Budi Capil')
        ->assertDontSee('Citra Sudah Selesai');
});

test('campus admin dashboard is scoped to its own campus', function () {
    $user = mahasiswaTahap('kampus', 'Ani Kampus A');
    $user->profile->update(['prodi_id' => null]);

    $prodiLain = Kampus::create(['nama_kampus' => 'Kampus B'])
        ->fakultas()->create(['nama' => 'Fakultas B'])
        ->prodi()->create(['nama' => 'Akuntansi']);
    $user->profile->update(['prodi_id' => $prodiLain->id]);

    $this->kampusAdmin->update(['kampus_id' => $this->kampus->id]);

    actingAs($this->kampusAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Ani Kampus A');
});

test('mahasiswa dashboard shows the blocking application', function () {
    $user = mahasiswaTahap('kesra', 'Dewi Pendaftar');
    $user->profile->update([
        'verif_capil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);

    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_selesai' => now()->addMonth(),
    ]);
    Applicant::factory()->create(['user_id' => $user->id, 'beasiswa_id' => $beasiswa->id]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pendaftaran Berjalan')
        ->assertSee($beasiswa->nama);
});
