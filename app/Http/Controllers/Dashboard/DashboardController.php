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

        if ($user->hasMenuAccess('admin.beasiswa.index')) {
            return $this->adminDashboard();
        }

        return $this->userDashboard();
    }

    private function adminDashboard(): View
    {
        $totalScholarships = Scholarship::count();
        $activeScholarships = Scholarship::where('status', 'aktif')->count();
        $inactiveScholarships = Scholarship::where('status', 'non-aktif')->count();
        $totalApplicants = Applicant::count();
        $pendingApplicants = Applicant::where('status', 'verifikasi')->count();
        $acceptedApplicants = Applicant::where('status', 'diterima')->count();
        $rejectedApplicants = Applicant::where('status', 'ditolak')->count();

        $recentApplicants = Applicant::with(['user', 'beasiswa'])
            ->latest()
            ->take(5)
            ->get();

        $upcomingDeadlines = Scholarship::where('status', 'aktif')
            ->where('tanggal_selesai', '>=', now())
            ->orderBy('tanggal_selesai')
            ->take(5)
            ->get();

        return view('dasbor', compact(
            'totalScholarships',
            'activeScholarships',
            'inactiveScholarships',
            'totalApplicants',
            'pendingApplicants',
            'acceptedApplicants',
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

        $recentApplications = Applicant::where('user_id', $userId)
            ->with('beasiswa')
            ->latest()
            ->take(5)
            ->get();

        $availableScholarships = Scholarship::where('status', 'aktif')
            ->where('tanggal_selesai', '>=', now())
            ->orderBy('tanggal_selesai')
            ->take(5)
            ->get();

        $activeAvailableCount = Scholarship::where('status', 'aktif')
            ->where('tanggal_selesai', '>=', now())
            ->count();

        return view('dasbor', [
            'totalApplications' => $totalApplications,
            'pendingApplications' => $pendingApplications,
            'acceptedApplications' => $acceptedApplications,
            'rejectedApplications' => $rejectedApplications,
            'recentApplications' => $recentApplications,
            'availableScholarships' => $availableScholarships,
            'activeAvailableCount' => $activeAvailableCount,
        ]);
    }
}
