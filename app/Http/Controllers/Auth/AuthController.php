<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\StoreLoginRequest;
use App\Http\Requests\Auth\StoreRegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Notifications\NewUserRegistered;
use App\Notifications\OtpPasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function masuk(): View
    {
        return view('auth.masuk');
    }

    public function simpanMasuk(StoreLoginRequest $request): RedirectResponse
    {
        $login = $request->validated('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $field => $login,
            'password' => $request->validated('password'),
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if ($user->status !== 'aktif') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withInput($request->only('login'))->withErrors([
                    'login' => 'Akun Anda belum diaktifkan. Silakan hubungi admin.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('success', 'Selamat datang kembali!');
        }

        return back()->withInput($request->only('login'))->withErrors([
            'login' => 'Email atau username atau kata sandi salah',
        ]);
    }

    public function daftar(): View
    {
        return view('auth.daftar');
    }

    public function simpanDaftar(StoreRegisterRequest $request): RedirectResponse
    {
        do {
            $username = Str::random(8);
        } while (User::where('username', $username)->exists());

        $user = User::create([
            'username' => $username,
            'email' => $request->email,
            'password' => $request->password,
            'status' => 'non-aktif',
        ]);

        $user->assignRole('user');

        $admins = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'super_admin']))->get();
        $admins->each->notify(new NewUserRegistered($user->email));

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Akun Anda menunggu aktivasi admin. Username Anda: '.$username);
    }

    public function lupaKataSandi(): View
    {
        return view('auth.lupa-kata-sandi');
    }

    public function kirimOtp(SendOtpRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        $rateLimitKey = 'otp-send:'.$email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withInput()->withErrors([
                'email' => 'Terlalu banyak permintaan. Coba lagi dalam '.$seconds.' detik.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $expiresInMinutes = 5;

        Cache::put('otp:'.$email, $otp, now()->addMinutes($expiresInMinutes));
        Cache::put('otp:attempts:'.$email, 0, now()->addMinutes($expiresInMinutes));

        $user = User::where('email', $email)->first();
        $user->notify(new OtpPasswordReset($otp, $expiresInMinutes));

        RateLimiter::hit($rateLimitKey, 60);

        return redirect()->route('password.otp.verify', ['email' => $email])
            ->with('success', 'Kode OTP telah dikirim ke email '.$email);
    }

    public function verifikasiOtp(Request $request): View|RedirectResponse
    {
        $email = $request->query('email');

        if (! $email || ! Cache::has('otp:'.$email)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Sesi OTP tidak valid atau sudah kedaluwarsa. Silakan coba lagi.']);
        }

        return view('auth.verifikasi-otp', compact('email'));
    }

    public function cekOtp(VerifyOtpRequest $request): RedirectResponse
    {
        $email = $request->query('email');
        $inputOtp = $request->validated('otp');

        if (! $email || ! Cache::has('otp:'.$email)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Sesi OTP tidak valid atau sudah kedaluwarsa. Silakan coba lagi.']);
        }

        $storedOtp = Cache::get('otp:'.$email);
        $attempts = Cache::get('otp:attempts:'.$email, 0);

        if ($attempts >= 5) {
            Cache::forget('otp:'.$email);
            Cache::forget('otp:attempts:'.$email);

            return redirect()->route('password.request')
                ->withErrors(['otp' => 'Terlalu banyak percobaan gagal. Silakan minta kode OTP baru.']);
        }

        if ($inputOtp !== $storedOtp) {
            Cache::put('otp:attempts:'.$email, $attempts + 1, now()->addMinutes(5));
            $remaining = 5 - $attempts - 1;

            return back()->withErrors([
                'otp' => 'Kode OTP salah. Sisa percobaan: '.$remaining,
            ]);
        }

        Cache::forget('otp:'.$email);
        Cache::forget('otp:attempts:'.$email);

        $request->session()->put('password_reset_verified', $email);

        return redirect()->route('password.reset.form')
            ->with('success', 'Kode OTP valid. Silakan buat kata sandi baru.');
    }

    public function resetKataSandi(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_verified');

        if (! $email) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Akses tidak valid. Silakan mulai dari awal.']);
        }

        return view('auth.buat-kata-sandi-baru', compact('email'));
    }

    public function simpanKataSandiBaru(ResetPasswordRequest $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_verified');

        if (! $email) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Akses tidak valid. Silakan mulai dari awal.']);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->update([
            'password' => $request->validated('password'),
        ]);

        $request->session()->forget('password_reset_verified');

        return redirect()->route('login')->with('success', 'Kata sandi berhasil direset. Silakan masuk dengan kata sandi baru.');
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar');
    }
}
