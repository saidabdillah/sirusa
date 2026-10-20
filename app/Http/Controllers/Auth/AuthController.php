<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreLoginRequest;
use App\Models\NikDiizinkan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Jumlah percobaan masuk yang salah untuk satu akun sebelum ditahan,
     * dan lama penahanannya (detik).
     *
     * Tanpa batas ini, kata sandi bisa ditebak terus-menerus oleh skrip.
     * Dihitung per kombinasi akun + IP supaya pengguna lain di jaringan yang
     * sama tidak ikut tertahan.
     */
    private const BATAS_PERCOBAAN = 5;

    private const DETIK_TERTAHAN = 60;

    public function masuk(): View
    {
        return view('auth.masuk');
    }

    public function simpanMasuk(StoreLoginRequest $request): RedirectResponse
    {
        $login = $request->validated('login');
        $password = $request->validated('password');

        $kunciBatas = 'masuk:'.Str::lower($login).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunciBatas, self::BATAS_PERCOBAAN)) {
            $detik = RateLimiter::availableIn($kunciBatas);

            return back()->withInput($request->only('login'))->withErrors([
                'login' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$detik} detik.",
            ]);
        }

        $user = User::whereHas('profile', fn ($query) => $query->where('nik', $login))->first()
            ?? User::where('username', $login)->first()
            ?? User::where('email', $login)->first();

        if ($user && Hash::check($password, $user->password)) {
            RateLimiter::clear($kunciBatas);

            // Akun lama yang NIK-nya baru didaftarkan di `nik_diizinkan`
            // setelah ia mendaftar: aktifkan saat ia berhasil masuk, supaya
            // tidak perlu menunggu admin. NIK yang tidak terdaftar tetap
            // ditolak seperti biasa.
            if ($user->status !== 'aktif' && NikDiizinkan::mengizinkan($user->profile?->nik)) {
                $user->update(['status' => 'aktif']);
            }

            if ($user->status !== 'aktif') {
                return back()->withInput($request->only('login'))->withErrors([
                    'login' => 'Akun Anda belum diaktifkan. Silakan hubungi admin.',
                ]);
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('success', 'Selamat datang kembali!');
        }

        // Percobaan gagal baru dihitung di sini: percobaan dengan kata sandi
        // benar tidak pernah menghabiskan jatah.
        RateLimiter::hit($kunciBatas, self::DETIK_TERTAHAN);

        return back()->withInput($request->only('login'))->withErrors([
            'login' => 'NIK, username, atau kata sandi salah',
        ]);
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar');
    }
}
