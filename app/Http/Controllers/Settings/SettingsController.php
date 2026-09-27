<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        return view('settings.index');
    }

    public function updateAccount(UpdateAccountRequest $request): RedirectResponse|JsonResponse
    {
        // `UpdateAccountRequest` sudah mewajibkan ketiga field, jadi tidak ada
        // lagi cabang "kalau password kosong, lewati saja" -- itulah yang dulu
        // membuat form kosong tetap menampilkan "berhasil diperbarui".
        $user = auth()->user();
        $user->password = $request->validated('password');
        $user->save();

        return $this->ajaxOk($request, 'Kata sandi berhasil diperbarui', route('settings'));
    }
}
