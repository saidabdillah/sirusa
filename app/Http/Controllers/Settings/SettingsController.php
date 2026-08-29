<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SendEmailOtpRequest;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\VerifyEmailOtpRequest;
use App\Notifications\VerifyEmailChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index');
    }

    public function updateAccount(UpdateAccountRequest $request): RedirectResponse
    {
        $user = auth()->user();

        if ($request->filled('email')) {
            $user->email = $request->validated('email');
        }

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();

        return redirect()->route('settings')->with('success', 'Pengaturan akun berhasil disimpan');
    }

    public function sendEmailOtp(SendEmailOtpRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $newEmail = $request->validated('email');

        $rateLimitKey = 'otp-email-send:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withInput()->withErrors([
                'email' => 'Terlalu banyak permintaan. Coba lagi dalam '.$seconds.' detik.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $expiresInMinutes = 5;

        Cache::put('otp-email-change:'.$user->id, $otp, now()->addMinutes($expiresInMinutes));
        Cache::put('otp-email-change-attempts:'.$user->id, 0, now()->addMinutes($expiresInMinutes));

        $request->session()->put('pending_email_change', $newEmail);

        Notification::route('mail', $newEmail)->notify(new VerifyEmailChange($otp, $expiresInMinutes, $newEmail));

        RateLimiter::hit($rateLimitKey, 60);

        return redirect()->route('settings.email.verify')
            ->with('success', 'Kode OTP telah dikirim ke email '.$newEmail);
    }

    public function showEmailOtpVerify(): View|RedirectResponse
    {
        $pendingEmail = session('pending_email_change');

        if (! $pendingEmail || ! Cache::has('otp-email-change:'.auth()->id())) {
            return redirect()->route('settings')
                ->withErrors(['email' => 'Sesi OTP tidak valid atau sudah kedaluwarsa. Silakan coba lagi.']);
        }

        return view('settings.verify-email-otp', compact('pendingEmail'));
    }

    public function verifyEmailChange(VerifyEmailOtpRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $pendingEmail = $request->session()->get('pending_email_change');
        $inputOtp = $request->validated('otp');

        if (! $pendingEmail || ! Cache::has('otp-email-change:'.$user->id)) {
            return redirect()->route('settings')
                ->withErrors(['email' => 'Sesi OTP tidak valid atau sudah kedaluwarsa. Silakan coba lagi.']);
        }

        $storedOtp = Cache::get('otp-email-change:'.$user->id);
        $attempts = Cache::get('otp-email-change-attempts:'.$user->id, 0);

        if ($attempts >= 5) {
            Cache::forget('otp-email-change:'.$user->id);
            Cache::forget('otp-email-change-attempts:'.$user->id);
            $request->session()->forget('pending_email_change');

            return redirect()->route('settings')
                ->withErrors(['otp' => 'Terlalu banyak percobaan gagal. Silakan minta kode OTP baru.']);
        }

        if ($inputOtp !== $storedOtp) {
            Cache::put('otp-email-change-attempts:'.$user->id, $attempts + 1, now()->addMinutes(5));
            $remaining = 5 - $attempts - 1;

            return back()->withErrors([
                'otp' => 'Kode OTP salah. Sisa percobaan: '.$remaining,
            ]);
        }

        Cache::forget('otp-email-change:'.$user->id);
        Cache::forget('otp-email-change-attempts:'.$user->id);

        $user->update(['email' => $pendingEmail]);
        $request->session()->forget('pending_email_change');

        return redirect()->route('settings')->with('success', 'Alamat email Anda berhasil diubah');
    }
}
