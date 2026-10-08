<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\Scholarship;
use Carbon\Carbon;
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
}
