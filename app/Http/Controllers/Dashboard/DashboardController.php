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
        $paidScholarships = Scholarship::whereNotNull('tanggal_pembayaran')->count();
        $totalApplicants = Applicant::count();
        $pendingApplicants = Applicant::where('status', 'verifikasi')->count();
        $acceptedApplicants = Applicant::whereIn('status', ['diterima', 'verifikasi_akhir'])->count();
        $revisionApplicants = Applicant::where('status', 'revisi')->count();
        $rejectedApplicants = Applicant::where('status', 'ditolak')->count();
        $completedApplicants = Applicant::where('status', 'selesai')->count();

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
            'paidScholarships',
            'totalApplicants',
            'pendingApplicants',
            'acceptedApplicants',
            'revisionApplicants',
            'rejectedApplicants',
            'completedApplicants',
            'recentApplicants',
            'upcomingDeadlines',
        ));
    }

    private function userDashboard(): View
    {
        $userId = Auth::id();

        $totalApplications = Applicant::where('user_id', $userId)->count();
        $pendingApplications = Applicant::where('user_id', $userId)->where('status', 'verifikasi')->count();
        $acceptedApplications = Applicant::where('user_id', $userId)
            ->whereIn('status', ['diterima', 'verifikasi_akhir', 'selesai'])
            ->count();
        $rejectedApplications = Applicant::where('user_id', $userId)->where('status', 'ditolak')->count();

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
            'recentApplications' => $recentApplications,
            'availableScholarships' => $availableScholarships,
        ]);
    }
}
