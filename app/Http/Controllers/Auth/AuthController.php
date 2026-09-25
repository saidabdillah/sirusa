<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreLoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $password = $request->validated('password');

        $user = User::whereHas('profile', fn ($query) => $query->where('nik', $login))->first()
            ?? User::where('username', $login)->first()
            ?? User::where('email', $login)->first();

        if ($user && Hash::check($password, $user->password)) {
            if ($user->status !== 'aktif') {
                return back()->withInput($request->only('login'))->withErrors([
                    'login' => 'Akun Anda belum diaktifkan. Silakan hubungi admin.',
                ]);
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->route('dashboard')->with('success', 'Selamat datang kembali!');
        }

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
