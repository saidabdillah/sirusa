<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (! $route || ! $route->getName() || ! $request->user()) {
            return $next($request);
        }

        $routeName = $route->getName();

        $guarded = Menu::query()
            ->where('aktif', true)
            ->whereNotNull('scope')
            ->get(['scope'])
            ->contains(fn (Menu $menu) => User::menuScopeCovers($menu->scope, $routeName));

        if ($guarded && ! $request->user()->hasMenuAccess($routeName)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
