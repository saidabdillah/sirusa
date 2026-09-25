<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAccountRequest;
use Illuminate\Http\RedirectResponse;
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

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
            $user->save();
        }

        return redirect()->route('settings')->with('success', 'Kata sandi berhasil diperbarui');
    }
}
