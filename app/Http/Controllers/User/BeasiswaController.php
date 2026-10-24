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

        // Mahasiswa hanya melihat beasiswa yang CAKUPANnya memuat prodinya.
        // Beasiswa tanpa cakupan dianggap terbuka untuk semua prodi.
        //
        // `cakupan_count` masuk ke SELECT yang sama supaya `cakupanLabel()`
        // tidak menembak COUNT per kartu, dan `diterima_count` membuat
        // `sisaKuota()` tidak perlu COUNT per kartu juga.
        $scholarships = Scholarship::tersedia()
            ->untukMahasiswa($profile)
            ->withCount([
                'cakupan',
                'pendaftar as penerima_diterima' => fn ($q) => $q->where('status', 'diterima'),
            ])
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

        // Muat cakupan sekali di sini: daftar prodi di halaman detail dan
        // `allowsProdi()` di dalam eligibilityIssueFor() memakai data yang sama.
        $scholarship->loadMissing('cakupan.prodi.fakultas.kampus');

        // View memanggil `sisaKuota()` dua kali (panel info dan pesan blokir di
        // `eligibilityIssueFor()`). Tanpa hitungan ini setiap pemanggilan
        // menembak COUNT-nya sendiri; dengan ini cukup satu.
        $scholarship->loadCount([
            'pendaftar as penerima_diterima' => fn ($q) => $q->where('status', 'diterima'),
        ]);

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
