<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifikasiProfilRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\DataVerificationChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    private const STAGES = ['capil', 'kampus', 'kesra'];

    public function index(string $stage): View
    {
        $stage = $this->validStage($stage);

        $kampusId = $this->scopedKampusId();

        $users = User::role('user')
            ->where('status', 'aktif')
            ->with(['profile' => fn ($query) => $query->when(
                $stage === 'kampus',
                fn ($profile) => $profile->with('prodi')
            )])
            ->when($kampusId && $stage === 'kampus', fn ($query) => $query->whereHas('profile.prodi.fakultas', fn ($q) => $q->where('kampus_id', $kampusId))
            )
            ->get()
            ->filter(fn (User $user) => $user->profile && $user->profile->canVerifStage($stage))
            ->sortBy(fn (User $user) => $user->profile->verifStatus() === 'revisi' ? 0 : 1)
            ->values();

        return view('admin.verifikasi.index', compact('stage', 'users'));
    }

    public function show(User $user, string $stage): View
    {
        $stage = $this->validStage($stage);
        $profile = $user->profile;

        if (! $profile || ! $profile->canVerifStage($stage)) {
            abort(403);
        }

        if ($stage === 'kampus') {
            $userKampusId = $this->scopedKampusId();

            if ($userKampusId && $profile->prodi?->fakultas?->kampus_id !== $userKampusId) {
                abort(403);
            }
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

        if ($stage === 'kampus') {
            $userKampusId = $this->scopedKampusId();

            if ($userKampusId && $profile->prodi?->fakultas?->kampus_id !== $userKampusId) {
                abort(403);
            }
        }

        $data = $request->validated();

        $stages = UserProfile::verifStages();
        $profile->{$stages[$stage][0]} = $data['status'];
        $profile->{$stages[$stage][1]} = $data['catatan'] ?? null;
        $profile->save();

        $user->notify(new DataVerificationChanged($profile, $data['status']));

        $statusLabel = match ($data['status']) {
            'setuju' => 'disetujui',
            'revisi' => 'perlu perbaikan',
            'tolak' => 'ditolak',
            default => 'menunggu',
        };
        $successAction = match ($data['status']) {
            'setuju' => 'setujui',
            'revisi' => 'minta perbaikan',
            'tolak' => 'tolak',
            default => 'perbarui',
        };

        return redirect()->route($this->indexRoute($stage))
            ->with('success', "Verifikasi profil {$profile->nama_lengkap} berhasil di{$successAction}.");
    }

    private function validStage(string $stage): string
    {
        if (! in_array($stage, self::STAGES, true)) {
            abort(404);
        }

        return $stage;
    }

    private function indexRoute(string $stage): string
    {
        return match ($stage) {
            'capil' => 'admin.capil.index',
            'kampus' => 'admin.kampusverif.index',
            default => 'admin.kesra.index',
        };
    }

    private function scopedKampusId(): ?int
    {
        $user = auth()->user();

        if (! $user->hasRole('kampus')) {
            return null;
        }

        return $user->kampus_id;
    }
}
