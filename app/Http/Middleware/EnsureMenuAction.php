<?php

namespace App\Http\Middleware;

use App\Support\MenuAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga lapisan AKSI, pelengkap `EnsureMenuAccess` (yang menjaga lapisan
 * menu/halaman).
 *
 * `EnsureMenuAccess` menjawab "boleh membuka halaman modul ini?"; middleware
 * ini menjawab "boleh melakukan aksi ini di dalamnya?". Keduanya perlu:
 * tanpa yang ini, satu centang menu Pengguna otomatis memberi tambah, ubah,
 * dan hapus kepada siapa pun yang memegangnya.
 *
 * Route yang tidak terdaftar di `MenuAction::ROUTES` dilewatkan begitu saja --
 * menu non-CRUD (verifikasi, dasbor, beasiswa) tidak punya aksi granular dan
 * tetap dijaga `akses.menu` seperti semula.
 */
class EnsureMenuAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $user = $request->user();

        if (! $route || ! $user) {
            return $next($request);
        }

        $kebutuhan = MenuAction::forRoute($route->getName());

        if ($kebutuhan === null) {
            return $next($request);
        }

        if (! $user->hasMenuAction($kebutuhan['scope'], $kebutuhan['action'])) {
            abort(403, 'Anda tidak memiliki hak untuk melakukan aksi ini.');
        }

        return $next($request);
    }
}
