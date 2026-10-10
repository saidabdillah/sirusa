<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PenerimaExport;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Fakultas;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Support\ExcelDownload;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Daftar penerima beasiswa: pendaftar yang sudah diputuskan "diterima" oleh
 * Kesra. Halaman ini dipisah dari antrean verifikasi supaya cetak dan
 * ekspor selalu membaca daftar yang sudah disetujui, bukan daftar antrean.
 *
 * Daftarnya read-only penuh untuk semua role admin dengan grant
 * `admin.penerima` (Catpil, Kampus, Kesra, super_admin): tidak ada aksi
 * tulis -- tombol Aksi/hapus dihapus atas permintaan pengguna, jadi tidak
 * ada yang bisa mengembalikan penerima ke antrean dari halaman ini.
 */
class PenerimaBeasiswaController extends Controller
{
    public function index(): View
    {
        return view('admin.penerima.index', [
            'penerima' => $this->penerima(),
            'kampusId' => $this->validChoice('kampus_id'),
            'fakultasId' => $this->validChoice('fakultas_id'),
            'jurusanId' => $this->validChoice('jurusan_id'),
            'beasiswaId' => $this->validChoice('beasiswa_id'),
            'kampusOptions' => Kampus::query()->orderBy('nama_kampus')->get(['id', 'nama_kampus']),
            'fakultasOptions' => $this->fakultasOptions(),
            'jurusanOptions' => $this->jurusanOptions(),
            'beasiswaOptions' => Scholarship::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    /**
     * Halaman cetak daftar penerima. Tanpa layout aplikasi supaya
     * `window.print()` menghasilkan lembar yang bersih.
     */
    public function cetak(): View
    {
        return view('admin.penerima.cetak', [
            'penerima' => $this->penerima(),
        ]);
    }

    /**
     * Unduhan Excel daftar penerima.
     *
     * Otorisasi tetap datang dari `akses.menu`: nama route mengikuti scope
     * menu `admin.penerima`, jadi grant menu Penerima Beasiswa otomatis
     * berlaku tanpa pengecualian baru.
     */
    public function export(): StreamedResponse
    {
        $export = new PenerimaExport($this->penerima());

        return ExcelDownload::response($export->toSpreadsheet(), $export->fileName());
    }

    /**
     * Pendaftar berstatus `diterima` dari akun yang masih aktif, disaring
     * kampus/fakultas/program studi/beasiswa lewat query string yang dibawa
     * daftar, cetak, dan unduhan sekaligus.
     *
     * @return Collection<int, Applicant>
     */
    private function penerima(): Collection
    {
        $kampusId = $this->validChoice('kampus_id');
        $fakultasId = $this->validChoice('fakultas_id');
        $jurusanId = $this->validChoice('jurusan_id');
        $beasiswaId = $this->validChoice('beasiswa_id');

        // Soft coherence, sama seperti filter lokasi di Status Verifikasi
        // (`VerifikasiController::status()`): level bawah hanya dicek terhadap
        // induknya JIKA induk itu ikut dipilih; nilai yang bertentangan dengan
        // induk yang terpilih diabaikan, bukan mengosongkan daftar.
        if ($kampusId !== null && $fakultasId !== null && ! Fakultas::query()
            ->where('id', $fakultasId)
            ->where('kampus_id', $kampusId)
            ->exists()) {
            $fakultasId = null;
        }

        if ($fakultasId !== null && $jurusanId !== null && ! Prodi::query()
            ->where('id', $jurusanId)
            ->where('fakultas_id', $fakultasId)
            ->exists()) {
            $jurusanId = null;
        }

        return Applicant::query()
            ->with(['beasiswa', 'user.profile.prodi.fakultas.kampus'])
            ->where('status', 'diterima')
            ->whereHas('user', fn ($query) => $query->where('status', 'aktif'))
            ->when($kampusId, fn ($query) => $query->whereHas('user.profile.prodi.fakultas', fn ($fakultas) => $fakultas->where('kampus_id', $kampusId)))
            ->when($fakultasId, fn ($query) => $query->whereHas('user.profile.prodi', fn ($prodi) => $prodi->where('fakultas_id', $fakultasId)))
            ->when($jurusanId, fn ($query) => $query->whereHas('user.profile.prodi', fn ($prodi) => $prodi->where('id', $jurusanId)))
            ->when($beasiswaId, fn ($query) => $query->where('beasiswa_id', $beasiswaId))
            ->latest('updated_at')
            ->get();
    }

    /**
     * Dropdown Fakultas dinamis: hanya memuat milik kampus yang terpilih.
     */
    private function fakultasOptions(): Collection
    {
        $kampusId = $this->validChoice('kampus_id');

        return Fakultas::query()
            ->when($kampusId, fn ($query) => $query->where('kampus_id', $kampusId))
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }

    /**
     * Dropdown Program Studi dinamis: hanya milik fakultas terpilih, atau milik
     * fakultas mana pun di kampus terpilih selama fakultasnya belum dipilih.
     */
    private function jurusanOptions(): Collection
    {
        $kampusId = $this->validChoice('kampus_id');
        $fakultasId = $this->validChoice('fakultas_id');

        return Prodi::query()
            ->when($fakultasId, fn ($query) => $query->where('fakultas_id', $fakultasId))
            ->when($fakultasId === null && $kampusId !== null,
                fn ($query) => $query->whereHas('fakultas', fn ($fakultas) => $fakultas->where('kampus_id', $kampusId)))
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }

    private function validChoice(string $param): ?int
    {
        $value = filter_var(request($param), FILTER_VALIDATE_INT);

        return $value === false || $value < 1 ? null : $value;
    }
}
