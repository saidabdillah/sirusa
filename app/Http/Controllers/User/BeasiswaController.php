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
        $applications = $user->applicants()->pluck('status', 'beasiswa_id');

        $scholarships = Scholarship::where('status', 'aktif')
            ->where('batas_waktu', '>=', now())
            ->latest()
            ->paginate(9);

        return view('user.beasiswa.index', compact('scholarships', 'applications'));
    }

    public function show(Scholarship $scholarship): View
    {
        $user = auth()->user();
        $application = $user->applicants()->where('beasiswa_id', $scholarship->id)->first();
        $profileComplete = $user->isProfileComplete();

        return view('user.beasiswa.lihat', compact('scholarship', 'application', 'profileComplete'));
    }
}
