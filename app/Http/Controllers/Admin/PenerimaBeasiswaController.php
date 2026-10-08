<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PenerimaExport;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Support\ExcelDownload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Daftar penerima beasiswa: pendaftar yang sudah diputuskan "diterima" oleh
 * Kesra. Halaman ini dipisah dari antrean verifikasi supaya cetak dan
 * ekspor selalu membaca daftar yang sudah disetujui, bukan daftar antrean.
 *
 * Aksi "Hapus" di sini tidak menghapus baris `pendaftar` apa pun: statusnya
 * dikembalikan ke `verifikasi`, jadi pendaftaran itu masuk lagi ke antrean
 * keputusan Kesra. Baris baru justru mustahil dibuat karena index unik
 * (user_id, beasiswa_id) hanya mengizinkan satu baris per pasangan.
 */
class PenerimaBeasiswaController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        return view('admin.penerima.index', [
            'penerima' => $this->penerima(),
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

    public function tarik(Request $request, Applicant $applicant): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.penerima.index'), 403);

        if ($applicant->status !== 'diterima') {
            return $this->ajaxFail(
                $request,
                'Pendaftaran ini bukan penerima aktif, jadi tidak bisa dikembalikan ke antrean.',
                route('admin.penerima.index')
            );
        }

        // Catatan keputusan lama ikut dikosongkan supaya tidak ada alasan
        // penerimaan usang yang tertinggal saat pendaftaran diputuskan ulang.
        $applicant->update(['status' => 'verifikasi', 'catatan' => null]);

        return $this->ajaxOk(
            $request,
            sprintf(
                'Pendaftaran %s dikembalikan ke antrean verifikasi.',
                $applicant->user?->profile?->nama_lengkap ?? 'mahasiswa'
            ),
        );
    }

    /**
     * Pendaftar berstatus `diterima` dari akun yang masih aktif.
     *
     * @return Collection<int, Applicant>
     */
    private function penerima(): Collection
    {
        return Applicant::query()
            ->with(['beasiswa', 'user.profile.prodi.fakultas.kampus'])
            ->where('status', 'diterima')
            ->whereHas('user', fn ($query) => $query->where('status', 'aktif'))
            ->latest('updated_at')
            ->get();
    }
}
