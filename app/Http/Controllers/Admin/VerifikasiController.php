<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifikasiProfilRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\DataVerificationChanged;
use App\Support\VerifikasiAntrean;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    private const FILTERS = ['menunggu', 'revisi', 'setuju', 'tolak'];

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

    public function verifikasi(User $user, string $stage, VerifikasiProfilRequest $request): RedirectResponse
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

        return redirect()->route($this->antrean($stage)->routeName())
            ->with('success', "Verifikasi profil {$profile->nama_lengkap} berhasil di{$successAction}.");
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
