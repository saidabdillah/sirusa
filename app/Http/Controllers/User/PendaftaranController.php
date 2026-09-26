<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
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
        $scholarship = Scholarship::findOrFail($request->input('beasiswa_id'));

        if ($error = $this->profilError($user, $profile)) {
            return redirect()->route('profile')->with('error', $error);
        }

        if ($error = $this->beasiswaError($user, $scholarship, $profile)) {
            return redirect()->route('user.beasiswa.lihat', $scholarship)->with('error', $error);
        }

        return view('user.pendaftaran.buat', compact('scholarship', 'profile'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->profile;
        $scholarship = Scholarship::findOrFail($request->integer('beasiswa_id'));

        if ($error = $this->profilError($user, $profile)) {
            return redirect()->route('profile')->with('error', $error);
        }

        if ($error = $this->beasiswaError($user, $scholarship, $profile)) {
            return redirect()->route('user.beasiswa.lihat', $scholarship)->with('error', $error);
        }

        $snapshot = [
            'fakultas' => $profile?->prodi?->fakultas?->nama,
            'prodi' => $profile?->prodi?->nama,
            'ipk' => $profile?->ipk,
            'semester' => $profile?->semester,
            'status' => 'verifikasi',
            'catatan' => null,
        ];

        // Pendaftaran yang pernah dibatalkan dihidupkan kembali, bukan dibuat
        // baris baru. Index unik [user_id, beasiswa_id] tidak mengizinkan dua
        // baris untuk pasangan yang sama, dan menghidupkan ulang lebih mudah
        // dibaca daripada riwayat yang bertumpuk.
        $applicant = $user->applicants()
            ->where('beasiswa_id', $scholarship->id)
            ->where('status', 'dibatalkan')
            ->first();

        if ($applicant) {
            $applicant->update($snapshot);
        } else {
            $applicant = $user->applicants()->create([
                'beasiswa_id' => $scholarship->id,
                ...$snapshot,
            ]);
        }

        User::usersGrantedMenu('admin.pendaftar')
            ->each->notify(new NewApplication($applicant, $profile?->nama_lengkap ?: $user->username));

        return redirect()->route('user.pendaftaran.index')->with('success', 'Pendaftaran berhasil dikirim');
    }

    /**
     * Pembatalan oleh mahasiswa, supaya dia tidak terkunci selamanya pada satu
     * pendaftaran yang salah pilih. Statusnya `dibatalkan`, bukan `ditolak` --
     * yang berarti keputusan Kesra, dan justru membuka jalan mendaftar lagi.
     */
    public function destroy(Applicant $applicant): RedirectResponse
    {
        if ($applicant->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $applicant->canBeCancelled()) {
            return redirect()->route('user.pendaftaran.index')
                ->with('error', 'Pendaftaran ini sudah diputuskan sehingga tidak bisa dibatalkan.');
        }

        $applicant->update(['status' => 'dibatalkan']);

        return redirect()->route('user.pendaftaran.index')
            ->with('success', 'Pendaftaran dibatalkan. Anda bisa mendaftar beasiswa lain.');
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

    /**
     * Syarat profil, dipisah dari syarat beasiswa supaya `create` dan `store`
     * menolak dengan alasan yang sama dan mengarahkan ke halaman profil.
     */
    private function profilError(User $user, ?UserProfile $profile): ?string
    {
        if (! $user->isProfileComplete()) {
            return 'Profil belum lengkap. Silakan lengkapi profil terlebih dahulu.';
        }

        if (! $profile?->isVerified()) {
            return 'Profil belum terverifikasi. Silakan tunggu verifikasi dari pihak kami terlebih dahulu.';
        }

        return null;
    }

    /**
     * Syarat beasiswa: satu mahasiswa hanya boleh punya satu pendaftaran yang
     * masih menutup jalan pendaftaran lain, lalu persyaratan beasiswa yang
     * dipilih.
     */
    private function beasiswaError(User $user, Scholarship $scholarship, ?UserProfile $profile): ?string
    {
        $alreadyRegistered = $user->applicants()
            ->where('beasiswa_id', $scholarship->id)
            ->where('status', '!=', 'dibatalkan')
            ->exists();

        if ($alreadyRegistered) {
            return 'Anda sudah mendaftar beasiswa ini.';
        }

        $blocking = $user->blockingApplicant();

        if ($blocking) {
            $name = $blocking->beasiswa?->nama ?? 'beasiswa sebelumnya';

            return $blocking->status === 'diterima'
                ? "Anda sudah diterima pada Beasiswa {$name}. Setiap mahasiswa hanya boleh menerima satu beasiswa."
                : "Anda sudah punya pendaftaran yang sedang diproses pada Beasiswa {$name}. Batalkan dulu jika ingin menggantinya.";
        }

        return $scholarship->eligibilityIssueFor($profile);
    }
}
