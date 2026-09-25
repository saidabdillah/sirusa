<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use App\Notifications\NewApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendaftaranController extends Controller
{
    public function create(Request $request): RedirectResponse|View
    {
        $user = auth()->user();
        $profile = $user->profile;

        if (! $user->isProfileComplete()) {
            $missing = $user->getMissingProfileFields();

            return redirect()->route('profile')
                ->with('error', 'Profil belum lengkap. Silakan lengkapi data berikut terlebih dahulu: '.implode(', ', $missing));
        }

        if (! $profile || ! $profile->isVerified()) {
            return redirect()->route('profile')
                ->with('error', 'Profil belum terverifikasi. Silakan tunggu verifikasi dari pihak kami terlebih dahulu.');
        }

        $scholarship = Scholarship::findOrFail($request->input('beasiswa_id'));

        if ($user->applicants()->where('beasiswa_id', $scholarship->id)->exists()) {
            return redirect()->route('user.beasiswa.lihat', $scholarship)
                ->with('error', 'Anda sudah mendaftar beasiswa ini.');
        }

        $eligibilityError = $scholarship->eligibilityIssueFor($profile);

        if ($eligibilityError) {
            return redirect()->route('user.beasiswa.lihat', $scholarship)
                ->with('error', $eligibilityError);
        }

        return view('user.pendaftaran.buat', compact('scholarship', 'profile'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        if (! $user->isProfileComplete()) {
            return redirect()->route('profile')
                ->with('error', 'Profil belum lengkap. Silakan lengkapi profil terlebih dahulu.');
        }

        if (! $profile || ! $profile->isVerified()) {
            return redirect()->route('profile')
                ->with('error', 'Profil belum terverifikasi. Silakan tunggu verifikasi dari pihak kami terlebih dahulu.');
        }

        $scholarship = Scholarship::findOrFail($request->integer('beasiswa_id'));

        if ($user->applicants()->where('beasiswa_id', $scholarship->id)->exists()) {
            return redirect()->route('user.beasiswa.lihat', $scholarship)
                ->with('error', 'Anda sudah mendaftar beasiswa ini.');
        }

        $eligibilityError = $scholarship->eligibilityIssueFor($profile);

        if ($eligibilityError) {
            return redirect()->route('user.beasiswa.lihat', $scholarship)
                ->with('error', $eligibilityError);
        }

        $applicant = Applicant::create([
            'user_id' => $user->id,
            'beasiswa_id' => $scholarship->id,
            'fakultas' => $profile->prodi?->fakultas?->nama,
            'prodi' => $profile->prodi?->nama,
            'ipk' => $profile->ipk,
            'semester' => $profile->semester,
            'status' => 'verifikasi',
        ]);

        $admins = User::usersGrantedMenu('admin.pendaftar');
        $admins->each->notify(new NewApplication($applicant, $profile->nama_lengkap ?: $user->username));

        return redirect()->route('user.pendaftaran.index')->with('success', 'Pendaftaran berhasil dikirim');
    }

    public function index(): View
    {
        $applicants = Applicant::with('beasiswa')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('user.pendaftaran.index', compact('applicants'));
    }

    public function show(Applicant $applicant): View
    {
        if ($applicant->user_id !== auth()->id()) {
            abort(403);
        }

        $applicant->load('beasiswa');
        $profile = auth()->user()->profile;

        return view('user.pendaftaran.lihat', compact('applicant', 'profile'));
    }
}
