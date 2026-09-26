<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\VerifikasiAntrean;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dasbor dipilih dari berapa banyak tahap verifikasi yang boleh diakses,
     * bukan dari nama role. Threshold-nya: satu tahap = dasbor tahap itu, lebih
     * dari satu = ikhtisar, nol = dasbor mahasiswa. Dengan begitu role baru atau
     * perubahan grant menu tidak membuat dasbor ikut salah.
     */
    public function __invoke(): View
    {
        $user = Auth::user();
        $stages = $this->accessibleStages($user);

        return match (count($stages)) {
            0 => $this->mahasiswa($user),
            1 => $this->verifikasi(reset($stages), $user),
            default => $this->ikhtisar($user),
        };
    }

    /**
     * @return array<int, string>
     */
    private function accessibleStages(User $user): array
    {
        return array_values(array_filter(
            UserProfile::verifStageOrder(),
            fn (string $stage) => $user->hasMenuAccess($this->antrean($stage)->routeName()),
        ));
    }

    private function verifikasi(string $stage, User $user): View
    {
        $antrean = $this->antrean($stage);

        return view('dasbor.verifikasi', [
            'stage' => $stage,
            // Judul memakai nama pemangku tugas ("Kesra"), bukan nama antrean
            // ("Verifikasi Kesra"), supaya tidak bentrok dengan label menu.
            'stageLabel' => $antrean->actorLabel(),
            'ringkasan' => $antrean->ringkasan(),
            'antrean' => $antrean->antrean('menunggu')->take(8),
            'pendaftaran' => $antrean->pendaftaran(),
            'stages' => $this->accessibleStages($user),
        ]);
    }

    /**
     * Ikhtisar lintas tahap, hanya masuk ke sini bila pengguna punya akses ke
     * lebih dari satu tahap verifikasi.
     */
    private function ikhtisar(User $user): View
    {
        $stages = $this->accessibleStages($user);

        $perStage = collect($stages)
            ->mapWithKeys(fn (string $stage) => [$stage => $this->antrean($stage)->ringkasan()]);

        return view('dasbor.ikhtisar', [
            'stages' => $stages,
            'perStage' => $perStage,
            'funnel' => $this->antrean('kesra')->funnel(),
            'totalBeasiswa' => Scholarship::count(),
            'beasiswaAktif' => Scholarship::where('status', 'aktif')->count(),
            'pendaftarBaru' => Applicant::where('status', 'verifikasi')->count(),
            'deadline' => Scholarship::tersedia()->orderBy('tanggal_selesai')->take(5)->get(),
        ]);
    }

    private function mahasiswa(User $user): View
    {
        $profile = $user->profile;
        $kampusId = $profile?->prodi?->fakultas?->kampus_id;

        return view('dasbor.mahasiswa', [
            'profile' => $profile,
            'kampusId' => $kampusId,
            'missingFields' => $user->getMissingProfileFields(),
            'applications' => $user->applicants()->with('beasiswa')->latest()->take(5)->get(),
            'totalApplications' => $user->applicants()->count(),
            'activeApplication' => $user->blockingApplicant(),
            'beasiswaTersedia' => Scholarship::tersedia()->untukKampus($kampusId)->orderBy('tanggal_selesai')->take(5)->get(),
            'jumlahBeasiswaTersedia' => Scholarship::tersedia()->untukKampus($kampusId)->count(),
        ]);
    }

    private function antrean(string $stage): VerifikasiAntrean
    {
        return new VerifikasiAntrean($stage);
    }
}
