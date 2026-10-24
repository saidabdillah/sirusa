<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Models\VerifikasiSemester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Antrean verifikasi PAKET SEMESTER untuk penerima beasiswa.
 *
 * Ini fase KEDUA, bukan pengganti fase seleksi:
 *
 * - Fase seleksi (Catpil + Kampus paralel, lalu Kesra memutuskan) tetap di
 *   `VerifikasiController` dan kolom `profil_pengguna.verif_*`.
 * - Fase semester hanya berlaku bagi pendaftar `diterima`, dan urutannya
 *   berurutan: Kampus memeriksa paket semester, lalu Kesra menutupnya.
 *
 * Catpil tidak muncul di sini sama sekali: data kependudukan tidak berubah
 * hanya karena mahasiswa naik semester.
 *
 * SATU menu sidebar untuk dua peran. Tahapnya ditentukan dari PERAN pengguna,
 * bukan dari URL, supaya tidak ada dua entri menu yang isinya sama.
 */
class VerifikasiSemesterController extends Controller
{
    use RespondsToAjax;

    /**
     * Daftar paket semester yang menjadi pekerjaan tahap pengguna ini.
     */
    public function index(Request $request): View
    {
        $stage = $this->tahap($request);

        $paket = $this->query($stage)
            ->with(['pendaftar.user.profile.prodi.fakultas.kampus', 'pendaftar.beasiswa'])
            ->latest('semester')
            ->latest('id')
            ->paginate(25);

        return view('admin.verifikasi.semester.index', [
            'stage' => $stage,
            'paket' => $paket,
            // Pemilih tahap hanya untuk super_admin, yang mengawasi keduanya.
            'bisaPilihTahap' => auth()->user()?->hasRole('super_admin') ?? false,
        ]);
    }

    /**
     * Detail satu paket semester: data mahasiswa + paket akademik semester itu.
     */
    public function show(VerifikasiSemester $paket, Request $request): View
    {
        $stage = $this->tahap($request);
        $this->pastikanBoleh($paket, $stage);

        $paket->load(['pendaftar.user.profile.prodi.fakultas.kampus', 'pendaftar.beasiswa']);

        return view('admin.verifikasi.semester.lihat', [
            'stage' => $stage,
            'paket' => $paket,
            'pendaftar' => $paket->pendaftar,
            'profile' => $paket->pendaftar->user?->profile,
        ]);
    }

    /**
     * Simpan keputusan satu tahap untuk satu paket semester.
     *
     * Kampus: setuju / revisi. Kesra: setuju (selesai) / tahan / dicabut.
     * Pencabutan dicatat di paket tempat ia diputuskan, beserta alasannya,
     * supaya ada jejak siapa mencabut dan kapan.
     */
    public function verifikasi(VerifikasiSemester $paket, Request $request): RedirectResponse|JsonResponse
    {
        $stage = $this->tahap($request);
        $this->pastikanBoleh($paket, $stage);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', $this->statusSah($stage))],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'status.required' => 'Keputusan harus dipilih.',
            'status.in' => 'Keputusan tidak valid.',
        ]);

        // "Perlu perbaikan", "Ditahan", dan "Dicabut" tanpa alasan tidak ada
        // artinya bagi mahasiswa: ia tidak pernah tahu apa yang harus diperbaiki.
        if (in_array($data['status'], ['revisi', 'tahan', 'dicabut'], true) && blank($data['catatan'] ?? null)) {
            return $this->ajaxFail($request, 'Catatan wajib diisi untuk keputusan ini.', null, [
                'errors' => ['catatan' => ['Catatan wajib diisi untuk keputusan ini.']],
            ]);
        }

        DB::transaction(function () use ($paket, $stage, $data): void {
            if ($stage === 'kampus') {
                $paket->status_kampus = $data['status'];
                $paket->catatan_kampus = $data['catatan'] ?? null;
                $paket->diputuskan_kampus_at = now();
                $paket->diputuskan_kampus_oleh = auth()->id();

                // Kampus meminta perbaikan -> Kesra tidak boleh memutuskan
                // paket yang dokumennya masih salah, jadi keputusan Kesra
                // dikembalikan ke menunggu dan catatan lamanya dibuang.
                if ($data['status'] === VerifikasiSemester::KAMPUS_REVISI) {
                    $paket->status_kesra = VerifikasiSemester::KESRA_MENUNGGU;
                    $paket->catatan_kesra = null;
                    $paket->diputuskan_kesra_at = null;
                    $paket->diputuskan_kesra_oleh = null;
                }
            } else {
                $paket->status_kesra = $data['status'];
                $paket->catatan_kesra = $data['catatan'] ?? null;
                $paket->diputuskan_kesra_at = now();
                $paket->diputuskan_kesra_oleh = auth()->id();
            }

            $paket->save();
        });

        return $this->ajaxOk(
            $request,
            "Verifikasi semester {$paket->semester} berhasil disimpan.",
            // Kembali ke tahap yang sedang dikerjakan, bukan selalu bawaan.
            route($stage === 'kesra' ? 'admin.semester.kesra' : 'admin.semester.kampus'),
        );
    }

    /**
     * Tahap yang dikerjakan pengguna ini, ditentukan dari PERAN.
     *
     * Peran ganda (mis. user yang punya role kampus dan kesra) memakai kesra:
     * itu tahap terakhir, dan mengunci orang pada tahap pertama akan membuat
     * pekerjaan tahap keduanya tidak bisa diselesaikan oleh siapa pun.
     */
    private function tahap(Request $request): string
    {
        $user = auth()->user();

        if ($user?->hasRole('kesra')) {
            return 'kesra';
        }

        if ($user?->hasRole('kampus')) {
            return 'kampus';
        }

        // super_admin tidak terikat satu tahap. Urutan sumbernya: parameter
        // `tahap` dari route (anak dropdown Kampus/Kesra), lalu `?tahap=`,
        // lalu bawaan kampus karena itu tahap pertama dalam urutan.
        foreach ([$request->route('tahap'), $request->query('tahap')] as $pilihan) {
            if (in_array($pilihan, ['kampus', 'kesra'], true)) {
                return $pilihan;
            }
        }

        return 'kampus';
    }

    /**
     * Tahap yang boleh mengerjakan paket ini.
     *
     * - Kampus: hanya paket yang dokumennya sudah lengkap dan belum dicabut.
     *   Paket `belum_lengkap` sengaja tidak masuk antrean, supaya petugas tidak
     *   melihat pekerjaan setengah jadi.
     * - Kesra: hanya paket yang SUDAH disetujui Kampus (urutan berurutan).
     */
    private function pastikanBoleh(VerifikasiSemester $paket, string $stage): void
    {
        abort_unless($paket->pendaftar?->status === 'diterima', 403);

        if ($stage === 'kampus') {
            $kampusId = auth()->user()?->kampus_id;

            if ($kampusId !== null && $paket->pendaftar->user?->profile?->prodi?->fakultas?->kampus_id !== $kampusId) {
                abort(403);
            }

            abort_if($paket->status_kampus === VerifikasiSemester::KAMPUS_BELUM_LENGKAP, 403);
        }

        if ($stage === 'kesra') {
            abort_unless($paket->status_kampus === VerifikasiSemester::KAMPUS_SETUJU, 403);
        }

        abort_if($paket->status_kesra === VerifikasiSemester::KESRA_DICABUT, 403);
    }

    private function query(string $stage)
    {
        $query = VerifikasiSemester::query();

        if ($stage === 'kampus') {
            $kampusId = auth()->user()?->kampus_id;

            $query->antreanKampus()
                ->when($kampusId !== null, fn ($q) => $q->whereHas(
                    'pendaftar.user.profile.prodi.fakultas',
                    fn ($f) => $f->where('kampus_id', $kampusId),
                ));
        } else {
            $query->antreanKesra();
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    private function statusSah(string $stage): array
    {
        return $stage === 'kampus'
            ? [VerifikasiSemester::KAMPUS_SETUJU, VerifikasiSemester::KAMPUS_REVISI]
            : [VerifikasiSemester::KESRA_SETUJU, VerifikasiSemester::KESRA_TAHAN, VerifikasiSemester::KESRA_DICABUT];
    }
}
