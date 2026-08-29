<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Scholarship;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        if ($user->hasRole(['super_admin', 'admin'])) {
            return $this->adminDashboard();
        }

        return $this->userDashboard();
    }

    private function adminDashboard(): View
    {
        $totalScholarships = Scholarship::count();
        $activeScholarships = Scholarship::where('status', 'aktif')->count();
        $announcedScholarships = Scholarship::whereNotNull('tanggal_pengumuman')->count();
        $totalApplicants = Applicant::count();
        $pendingApplicants = Applicant::where('status', 'verifikasi')->count();
        $acceptedApplicants = Applicant::where('status', 'diterima')->count();
        $revisionApplicants = Applicant::where('status', 'revisi')->count();
        $rejectedApplicants = Applicant::where('status', 'ditolak')->count();

        $recentApplicants = Applicant::with(['user', 'beasiswa'])
            ->latest()
            ->take(5)
            ->get();

        $upcomingDeadlines = Scholarship::where('status', 'aktif')
            ->where('batas_waktu', '>=', now())
            ->orderBy('batas_waktu')
            ->take(5)
            ->get();

        return view('dasbor', compact(
            'totalScholarships',
            'activeScholarships',
            'announcedScholarships',
            'totalApplicants',
            'pendingApplicants',
            'acceptedApplicants',
            'revisionApplicants',
            'rejectedApplicants',
            'recentApplicants',
            'upcomingDeadlines',
        ));
    }

    private function userDashboard(): View
    {
        $userId = Auth::id();

        $totalApplications = Applicant::where('user_id', $userId)->count();
        $pendingApplications = Applicant::where('user_id', $userId)->where('status', 'verifikasi')->count();
        $acceptedApplications = Applicant::where('user_id', $userId)->where('status', 'diterima')->count();
        $rejectedApplications = Applicant::where('user_id', $userId)->where('status', 'ditolak')->count();
        $revisionApplications = Applicant::where('user_id', $userId)->where('status', 'revisi')->count();

        $recentApplications = Applicant::where('user_id', $userId)
            ->with('beasiswa')
            ->latest()
            ->take(5)
            ->get();

        $availableScholarships = Scholarship::where('status', 'aktif')
            ->where('batas_waktu', '>=', now())
            ->orderBy('batas_waktu')
            ->take(5)
            ->get();

        return view('dasbor', [
            'totalApplications' => $totalApplications,
            'pendingApplications' => $pendingApplications,
            'acceptedApplications' => $acceptedApplications,
            'rejectedApplications' => $rejectedApplications,
            'revisionApplications' => $revisionApplications,
            'recentApplications' => $recentApplications,
            'availableScholarships' => $availableScholarships,
        ]);
    }
}