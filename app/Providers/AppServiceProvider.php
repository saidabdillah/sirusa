<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\Scholarship;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        $this->daftarkanPembatasPermintaan();

        View::composer('landing', function ($view) {
            $kampusMitra = Scholarship::query()->distinct()->pluck('kampus')->filter()->values();

            $view->with([
                'totalBeasiswa' => Scholarship::count(),
                'beasiswaAktif' => Scholarship::where('status', 'aktif')->count(),
                'totalPendaftar' => Applicant::count(),
                'totalSelesai' => Applicant::where('status', 'diterima')->count(),
                'totalKampus' => $kampusMitra->count(),
                'beasiswaPopuler' => Scholarship::where('status', 'aktif')->latest()->take(4)->get(),
                'kampusMitra' => $kampusMitra,
            ]);
        });
    }

    /**
     * Batas laju permintaan (rate limit) untuk seluruh aplikasi.
     *
     * Angkanya sengaja longgar supaya pemakaian normal tidak pernah kena:
     * satu halaman biasa menghasilkan sekitar dua permintaan (halaman + foto
     * profil di navbar). Yang ditahan adalah skrip yang membanjiri server,
     * percobaan menebak kata sandi, dan pendaftaran akun massal.
     *
     * Kuncinya memakai id pengguna kalau sudah masuk, dan IP kalau belum,
     * sehingga satu pengguna tidak memakan jatah pengguna lain di jaringan
     * yang sama (mis. lab kampus dengan satu IP publik).
     */
    private function daftarkanPembatasPermintaan(): void
    {
        // Seluruh halaman dan aksi web.
        RateLimiter::for('web', fn (Request $request) => Limit::perMinute(180)
            ->by($request->user()?->id ?: $request->ip()));

        // Penyaji berkas (KTP, KK, KRS/KHS) ikut limiter `web` di atas: satu
        // halaman biasa sudah memuat foto profil lewat rute itu, jadi batas
        // terpisah hanya akan membuat dua angka yang saling menutupi.
        // Aksi yang mengubah data. Permintaan baca (GET/HEAD/OPTIONS) tidak
        // dihitung sama sekali, jadi membuka halaman berkali-kali aman.
        RateLimiter::for('tulis', function (Request $request) {
            if ($request->isMethodSafe()) {
                return Limit::none();
            }

            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Pendaftaran akun mandiri: maksimal 5 akun per jam per IP.
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(5)
            ->by($request->ip()));

        // Percobaan masuk per IP. Batas per akun (5 kali per menit) dijaga
        // di AuthController supaya pesannya jelas di form login; limiter ini
        // menahan percobaan yang menyebar ke banyak nama akun.
        RateLimiter::for('login-ip', fn (Request $request) => Limit::perMinute(20)
            ->by($request->ip()));
    }
}
