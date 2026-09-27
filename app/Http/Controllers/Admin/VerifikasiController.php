<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CapilVerifikasiExport;
use App\Exports\KampusVerifikasiExport;
use App\Exports\KesraVerifikasiExport;
use App\Exports\VerifikasiExport;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifikasiProfilRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\DataVerificationChanged;
use App\Support\ExcelDownload;
use App\Support\VerifikasiAntrean;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiController extends Controller
{
    use RespondsToAjax;

    private const FILTERS = ['menunggu', 'revisi', 'setuju', 'tolak'];

    /**
     * Kelas unduhan Excel per tahap. Isi dan cakupannya mengikuti antrean di
     * layar, jadi tiap verifikator hanya mengunduh datanya sendiri.
     *
     * @var array<string, class-string<VerifikasiExport>>
     */
    private const EXPORTS = [
        'capil' => CapilVerifikasiExport::class,
        'kampus' => KampusVerifikasiExport::class,
        'kesra' => KesraVerifikasiExport::class,
    ];

    public function index(string $stage): View
    {
        $stage = $this->validStage($stage);
        $filter = $this->validFilter();
        $antrean = $this->antrean($stage);

        return view('admin.verifikasi.index', [
            'stage' => $stage,
            'users' => $antrean->antrean($filter),
            'filter' => $filter,
        ]);
    }

    public function show(User $user, string $stage): View
    {
        $stage = $this->validStage($stage);
        $profile = $user->profile;

        if (! $profile || ! $profile->canVerifStage($stage)) {
            abort(403);
        }

        if ($stage === 'kampus' && $this->diLuarKampus($profile)) {
            abort(403);
        }

        return view('admin.verifikasi.lihat', compact('stage', 'user', 'profile'));
    }

    public function verifikasi(User $user, string $stage, VerifikasiProfilRequest $request): RedirectResponse|JsonResponse
    {
        $stage = $this->validStage($stage);
        $profile = $user->profile;

        if (! $profile || ! $profile->canVerifStage($stage)) {
            abort(403);
        }

        if ($stage === 'kampus' && $this->diLuarKampus($profile)) {
            abort(403);
        }

        $data = $request->validated();

        $stages = UserProfile::verifStages();
        $profile->{$stages[$stage][0]} = $data['status'];
        $profile->{$stages[$stage][1]} = $data['catatan'] ?? null;

        // Keputusan pada tahap ini tidak lagi disetujui, maka urutan ke tahap
        // berikutnya tidak berlaku dan harus diulang dari awal.
        if ($data['status'] !== 'setuju') {
            $profile->resetDownstreamStages($stage);
        }

        $profile->save();

        $user->notify(new DataVerificationChanged($profile, $stage, $data['status'], $data['catatan'] ?? null));

        $successAction = match ($data['status']) {
            'setuju' => 'setujui',
            'revisi' => 'minta perbaikan',
            'tolak' => 'tolak',
            default => 'tarik kembali',
        };

        return $this->ajaxOk(
            $request,
            "Verifikasi profil {$profile->nama_lengkap} berhasil di{$successAction}.",
            route($this->antrean($stage)->routeName())
        );
    }

    /**
     * Unduhan Excel antrean tahap ini.
     *
     * Otorisasi tetap datang dari `akses.menu`: nama route mengikuti prefix
     * tahap (`admin.capil.*`, `admin.kampusverif.*`, `admin.kesra.*`) sehingga
     * grant menu yang sudah ada otomatis berlaku tanpa pengecualian baru.
     * Cakupan data diambil dari `VerifikasiAntrean`, jadi admin kampus tetap
     * hanya melihat kampusnya sendiri.
     */
    public function export(string $stage): StreamedResponse
    {
        $stage = $this->validStage($stage);

        $export = new (self::EXPORTS[$stage])(
            $this->antrean($stage),
            $this->validFilter(),
        );

        return ExcelDownload::response($export->toSpreadsheet(), $export->fileName());
    }

    private function validStage(string $stage): string
    {
        if (! in_array($stage, UserProfile::verifStageOrder(), true)) {
            abort(404);
        }

        return $stage;
    }

    /**
     * Filter status pada daftar verifikasi. Null berarti tidak difilter.
     */
    private function validFilter(): ?string
    {
        $filter = request('filter');

        return in_array($filter, self::FILTERS, true) ? $filter : null;
    }

    private function antrean(string $stage): VerifikasiAntrean
    {
        return new VerifikasiAntrean($stage);
    }

    /**
     * Admin kampus hanya boleh menangani mahasiswa di kampusnya sendiri.
     */
    private function diLuarKampus(UserProfile $profile): bool
    {
        $kampusId = $this->antrean('kampus')->scopedKampusId();

        return $kampusId !== null && $profile->prodi?->fakultas?->kampus_id !== $kampusId;
    }
}
