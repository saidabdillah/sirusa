<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\Scholarship;
use Illuminate\Support\Facades\Auth;
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
        View::composer('layouts.partials.navbar', function ($view) {
            if (Auth::check()) {
                $view->with('notifications', Auth::user()->unreadNotifications()->latest()->take(10)->get());
                $view->with('unreadCount', Auth::user()->unreadNotifications()->count());
            }
        });

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
