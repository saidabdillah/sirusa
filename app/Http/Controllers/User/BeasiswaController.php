<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use Illuminate\View\View;

class BeasiswaController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->profile;
        $applications = $user->applicants()->pluck('status', 'beasiswa_id');

        $kampusId = $profile?->prodi?->fakultas?->kampus_id;

        // Mahasiswa hanya boleh melamar beasiswa kampusnya sendiri, jadi beasiswa
        // kampus lain tidak perlu tampil sama sekali: menampilkan beasiswa yang
        // tidak bisa didaftar hanya bikin mahasiswa bingung.
        $scholarships = Scholarship::tersedia()
            ->untukKampus($kampusId)
            ->latest()
            ->paginate(9);

        return view('user.beasiswa.index', compact('scholarships', 'applications', 'kampusId'));
    }

    public function show(Scholarship $scholarship): View
    {
        $user = auth()->user();
        $profile = $user->profile;
        $application = $user->applicants()->where('beasiswa_id', $scholarship->id)->first();
        $profileComplete = $user->isProfileComplete();
        $eligibilityError = $scholarship->eligibilityIssueFor($profile);
        $blocking = $user->blockingApplicant();

        // Pendaftaran tidak menunggu verifikasi Catpil: mahasiswa cukup
        // melengkapi profil. Tahap verifikasi baru berjalan setelah
        // pendaftaran masuk.
        $canApply = $profileComplete
            && ! $application
            && ! $blocking
            && $eligibilityError === null
            && ! $scholarship->isExpired();

        return view('user.beasiswa.lihat', compact(
            'scholarship',
            'application',
            'profileComplete',
            'eligibilityError',
            'blocking',
            'canApply',
        ));
    }
}
