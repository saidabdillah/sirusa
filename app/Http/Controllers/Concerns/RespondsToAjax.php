<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Satu pola balasan untuk endpoint yang dipakai dua kali: submit form biasa
 * (tanpa reload) dan request AJAX dari `form[data-ajax-form]`.
 *
 * Halaman lama tetap berfungsi: `expectsJson()` hanya benar kalau klien
 * mengirim `Accept: application/json`, yang hanya dilakukan helper di
 * `public/assets/js/custom.js`. Form request yang gagal validasi sudah otomatis
 * dibalas 422 JSON oleh Laravel, jadi trait ini tidak perlu menangani kasus itu.
 */
trait RespondsToAjax
{
    /**
     * @param  array<string, mixed>  $extra  tambahan payload (mis. `id`, `html`)
     */
    protected function ajaxOk(
        Request $request,
        string $message,
        ?string $redirect = null,
        array $extra = [],
        int $status = 200,
    ): JsonResponse|RedirectResponse {
        if (! $this->wantsAjax($request)) {
            return $redirect !== null
                ? redirect()->to($redirect)->with('success', $message)
                : back()->with('success', $message);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => $this->ajaxRedirect($request, $redirect),
            ...$extra,
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function ajaxFail(
        Request $request,
        string $message,
        ?string $redirect = null,
        array $extra = [],
        int $status = 422,
    ): JsonResponse|RedirectResponse {
        if (! $this->wantsAjax($request)) {
            return $redirect !== null
                ? redirect()->to($redirect)->with('error', $message)
                : back()->with('error', $message);
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'redirect' => $this->ajaxRedirect($request, $redirect),
            ...$extra,
        ], $status);
    }

    /**
     * `back()` tidak bisa dipakai di jalur AJAX, jadi tujuan "kembali ke halaman
     * sebelumnya" diterjemahkan dari header `Referer`. Kalau tidak ada, klien
     * dibiarkan di halaman sekarang.
     */
    private function ajaxRedirect(Request $request, ?string $redirect): ?string
    {
        return $redirect ?? $request->headers->get('referer');
    }

    protected function wantsAjax(Request $request): bool
    {
        return $request->expectsJson();
    }
}
