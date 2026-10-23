<?php

use App\Models\Applicant;
use App\Models\DokumenAkademik;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\VerifikasiSemester;
use App\Support\PaketSemester;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('verifikasi-semester');

beforeEach(function () {
    seedAkses();

    $kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->prodi = $kampus->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->kampusAdmin = User::factory()->kampusAdmin()->create([
        'email' => 'kampus-sem@test.com',
        'kampus_id' => $kampus->id,
    ]);
    $this->kesra = User::factory()->admin()->create(['email' => 'kesra-sem@test.com']);

    $this->beasiswa = Scholarship::factory()->create([
        'nama' => 'Beasiswa Uji Semester',
        'kampus_id' => $kampus->id,
        'kampus' => $kampus->nama_kampus,
    ]);
});

/**
 * Mahasiswa yang SUDAH diterima sebagai penerima beasiswa, dengan paket
 * semester tertentu sudah lengkap diunggah.
 */
function penerimaDenganPaket(int $semesterAktif, array $semesterLengkap = []): array
{
    $user = User::factory()->standardUser()->create(['email' => 'penerima@test.com']);
    $user->profile()->create([
        'nama_lengkap' => 'Budi Penerima',
        'nik' => '6302000000000001',
        'prodi_id' => test()->prodi->id,
        'semester' => $semesterAktif,
        'jenjang' => 'S1',
    ]);

    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
        'beasiswa_id' => test()->beasiswa->id,
        'status' => 'diterima',
        'semester' => $semesterAktif,
    ]);

    foreach ($semesterLengkap as $semester) {
        DokumenAkademik::create([
            'user_id' => $user->id,
            'jenis' => 'krs',
            'semester' => $semester,
            'file' => "profil/{$user->id}/akademik/krs-{$semester}.pdf",
        ]);
        DokumenAkademik::create([
            'user_id' => $user->id,
            'jenis' => 'khs',
            'semester' => $semester,
            'file' => "profil/{$user->id}/akademik/khs-{$semester}.pdf",
            'nilai' => 3.5,
        ]);
    }

    return [$user, $applicant];
}

test('paket semester hanya dibuat untuk pendaftaran yang sudah diterima', function () {
    [$user, $applicant] = penerimaDenganPaket(3, [1, 2]);

    VerifikasiSemester::sinkron($user);

    // Semester 1 dan 2 lengkap -> masuk antrean; semester 3 belum.
    expect(VerifikasiSemester::where('pendaftar_id', $applicant->id)->count())->toBe(2)
        ->and(VerifikasiSemester::where('pendaftar_id', $applicant->id)
            ->where('status_kampus', VerifikasiSemester::KAMPUS_MENUNGGU)->count())->toBe(2);

    // Pendaftar yang belum diterima tidak pernah punya paket semester.
    $lain = User::factory()->standardUser()->create(['email' => 'belum@test.com']);
    $lain->profile()->create(['nama_lengkap' => 'Belum Diterima', 'semester' => 3]);
    Applicant::factory()->create([
        'user_id' => $lain->id,
        'beasiswa_id' => $this->beasiswa->id,
        'status' => 'verifikasi',
        'semester' => 3,
    ]);

    VerifikasiSemester::sinkron($lain);

    expect(VerifikasiSemester::whereHas('pendaftar', fn ($q) => $q->where('user_id', $lain->id))->count())->toBe(0);
});

test('sinkron tidak menimpa keputusan yang sudah ada', function () {
    [$user, $applicant] = penerimaDenganPaket(2, [1]);

    VerifikasiSemester::sinkron($user);

    $paket = VerifikasiSemester::where('pendaftar_id', $applicant->id)->first();
    $paket->update(['status_kampus' => VerifikasiSemester::KAMPUS_REVISI, 'catatan_kampus' => 'KHS buram']);

    // Simpan profil lagi tidak boleh mengembalikan keputusan menjadi "menunggu".
    VerifikasiSemester::sinkron($user);

    expect(VerifikasiSemester::where('pendaftar_id', $applicant->id)->count())->toBe(1)
        ->and($paket->fresh()->status_kampus)->toBe(VerifikasiSemester::KAMPUS_REVISI)
        ->and($paket->fresh()->catatan_kampus)->toBe('KHS buram');
});

test('paket belum lengkap tidak masuk antrean kampus', function () {
    [$user, $applicant] = penerimaDenganPaket(3, [1]);

    VerifikasiSemester::sinkron($user);

    $paket = VerifikasiSemester::where('pendaftar_id', $applicant->id)->where('semester', 2)->first();

    // Baris untuk semester 2 memang belum dibuat; yang ada hanya semester 1.
    expect($paket)->toBeNull();

    $paket1 = VerifikasiSemester::where('pendaftar_id', $applicant->id)->where('semester', 1)->first();
    actingAs($this->kampusAdmin)->get(route('admin.semester.index'))
        ->assertOk()
        ->assertSee('Budi Penerima');
});

test('kampus memutuskan lalu paket masuk antrean kesra', function () {
    [$user, $applicant] = penerimaDenganPaket(2, [1]);
    VerifikasiSemester::sinkron($user);

    $paket = VerifikasiSemester::where('pendaftar_id', $applicant->id)->first();

    // Sebelum Kampus setuju, Kesra tidak boleh membuka maupun memutuskan.
    actingAs($this->kesra)->put(route('admin.semester.verifikasi', $paket), [
        'status' => 'setuju',
    ])->assertForbidden();

    actingAs($this->kampusAdmin)->put(route('admin.semester.verifikasi', $paket), [
        'status' => 'setuju',
    ])->assertSessionDoesntHaveErrors();

    expect($paket->fresh()->status_kampus)->toBe(VerifikasiSemester::KAMPUS_SETUJU);

    // Setelah Kampus setuju barulah Kesra bisa menutup semester itu.
    actingAs($this->kesra)->put(route('admin.semester.verifikasi', $paket), [
        'status' => 'setuju',
    ])->assertSessionDoesntHaveErrors();

    expect($paket->fresh()->selesai())->toBeTrue();
});

test('kampus minta perbaikan mengembalikan status kesra ke menunggu', function () {
    [$user, $applicant] = penerimaDenganPaket(2, [1]);
    VerifikasiSemester::sinkron($user);
    $paket = VerifikasiSemester::where('pendaftar_id', $applicant->id)->first();

    actingAs($this->kampusAdmin)->put(route('admin.semester.verifikasi', $paket), ['status' => 'setuju']);
    actingAs($this->kesra)->put(route('admin.semester.verifikasi', $paket), ['status' => 'setuju']);

    actingAs($this->kampusAdmin)->put(route('admin.semester.verifikasi', $paket), [
        'status' => 'revisi',
        'catatan' => 'KHS tidak terbaca',
    ])->assertSessionDoesntHaveErrors();

    $paket->refresh();

    expect($paket->status_kampus)->toBe(VerifikasiSemester::KAMPUS_REVISI)
        ->and($paket->status_kesra)->toBe(VerifikasiSemester::KESRA_MENUNGGU)
        ->and($paket->catatan_kampus)->toBe('KHS tidak terbaca');
});

test('keputusan yang butuh alasan ditolak tanpa catatan', function () {
    [$user, $applicant] = penerimaDenganPaket(2, [1]);
    VerifikasiSemester::sinkron($user);
    $paket = VerifikasiSemester::where('pendaftar_id', $applicant->id)->first();

    // Penolakan lewat `ajaxFail()`, jadi pesannya masuk flash `error`, bukan
    // error validasi Laravel.
    actingAs($this->kampusAdmin)->put(route('admin.semester.verifikasi', $paket), [
        'status' => 'revisi',
    ])->assertSessionHas('error');

    expect($paket->fresh()->status_kampus)->toBe(VerifikasiSemester::KAMPUS_MENUNGGU);
});

test('kesra bisa mencabut beasiswa dengan alasan, dan paket terkunci', function () {
    [$user, $applicant] = penerimaDenganPaket(2, [1]);
    VerifikasiSemester::sinkron($user);
    $paket = VerifikasiSemester::where('pendaftar_id', $applicant->id)->first();

    actingAs($this->kampusAdmin)->put(route('admin.semester.verifikasi', $paket), ['status' => 'setuju']);

    actingAs($this->kesra)->put(route('admin.semester.verifikasi', $paket), [
        'status' => 'dicabut',
        'catatan' => 'Tidak memperbaiki dokumentasi selama dua semester',
    ])->assertSessionDoesntHaveErrors();

    expect($paket->fresh()->status_kesra)->toBe(VerifikasiSemester::KESRA_DICABUT);

    // Setelah dicabut, tidak ada tahap yang bisa mengubahnya lagi.
    actingAs($this->kampusAdmin)->put(route('admin.semester.verifikasi', $paket), [
        'status' => 'setuju',
    ])->assertForbidden();
});

test('pengingat dasbor menyebut semester yang belum lengkap', function () {
    [$user, $applicant] = penerimaDenganPaket(3, [1, 2]);

    actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('KRS &amp; KHS Semester 3', false)
        ->assertSee('Beasiswa Uji Semester');
});

test('halaman pendaftaran mahasiswa menampilkan status tiap semester', function () {
    [$user, $applicant] = penerimaDenganPaket(2, [1]);

    // Paket semester 1 sudah diputuskan selesai; semester 2 belum lengkap.
    VerifikasiSemester::sinkron($user);
    VerifikasiSemester::where('pendaftar_id', $applicant->id)->update([
        'status_kampus' => VerifikasiSemester::KAMPUS_SETUJU,
        'status_kesra' => VerifikasiSemester::KESRA_SETUJU,
    ]);

    actingAs($user)->get(route('user.pendaftaran.lihat', $applicant))
        ->assertOk()
        ->assertSee('Verifikasi per Semester')
        ->assertSee('Selesai')
        ->assertSee('Belum lengkap');
});

test('paket semester menghitung kekurangan dokumen dengan benar', function () {
    [$user, $applicant] = penerimaDenganPaket(2, []);

    // Hanya KRS semester 1, tanpa KHS.
    DokumenAkademik::create([
        'user_id' => $user->id,
        'jenis' => 'krs',
        'semester' => 1,
        'file' => "profil/{$user->id}/akademik/krs-1.pdf",
    ]);

    $paket = PaketSemester::untuk($applicant->fresh());

    expect($paket[0]['semester'])->toBe(1)
        ->and($paket[0]['lengkap'])->toBeFalse()
        ->and($paket[0]['kurang'])->toBe(['Berkas KHS', 'Nilai KHS']);
});
