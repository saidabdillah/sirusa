<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuAccessRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class MenuController extends Controller
{
    public function index(): View
    {
        $roles = Role::orderBy('id')->get();
        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('urutan')->get();

        $grants = [];
        foreach ($roles as $role) {
            $grants[$role->id] = DB::table('role_menu')->where('role_id', $role->id)->pluck('menu_id')->all();
        }

        return view('admin.menu.index', compact('roles', 'menus', 'grants'));
    }

    public function perbarui(UpdateMenuAccessRequest $request): RedirectResponse
    {
        $grants = $request->validated('grants') ?? [];

        $allMenuIds = DB::table('menus')->where('aktif', true)->pluck('id')->all();
        $wajibMenuIds = DB::table('menus')->where('wajib', true)->pluck('id')->all();
        $superAdminModuleIds = DB::table('menus')->whereIn('scope', ['admin.role', 'admin.menu', 'admin.menuKelola', 'admin.pengguna'])->pluck('id')->all();

        foreach (Role::all() as $role) {
            $menuIds = [];
            foreach ($grants[$role->id] ?? [] as $menuId) {
                if (in_array((int) $menuId, $allMenuIds, true)) {
                    $menuIds[] = (int) $menuId;
                }
            }

            foreach ($wajibMenuIds as $menuId) {
                if (! in_array((int) $menuId, $menuIds, true)) {
                    $menuIds[] = (int) $menuId;
                }
            }

            if ($role->name === 'super_admin') {
                foreach ($superAdminModuleIds as $menuId) {
                    if (! in_array((int) $menuId, $menuIds, true)) {
                        $menuIds[] = (int) $menuId;
                    }
                }
            }

            $menuIds = array_unique($menuIds);

            DB::table('role_menu')->where('role_id', $role->id)->delete();

            foreach ($menuIds as $menuId) {
                DB::table('role_menu')->insert([
                    'role_id' => $role->id,
                    'menu_id' => $menuId,
                ]);
            }
        }

        return redirect()->route('admin.menu.index')->with('success', 'Akses menu berhasil diperbarui');
    }

    public function kelola(): View
    {
        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('urutan')->get();
        $parents = Menu::whereNull('parent_id')->orderBy('urutan')->get();

        return view('admin.menu.kelola', compact('menus', 'parents'));
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $menu = Menu::create(array_merge($request->validated(), [
            'aktif' => $request->boolean('aktif'),
            'wajib' => $request->boolean('wajib'),
        ]));

        $superAdmin = Role::findByName('super_admin');
        DB::table('role_menu')->insert([
            'role_id' => $superAdmin->id,
            'menu_id' => $menu->id,
        ]);

        return redirect()->route('admin.menuKelola.index')->with('success', 'Menu berhasil ditambahkan');
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update(array_merge($request->validated(), [
            'aktif' => $request->boolean('aktif'),
            'wajib' => $request->boolean('wajib'),
        ]));

        return redirect()->route('admin.menuKelola.index')->with('success', 'Menu berhasil diperbarui');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        if ($menu->wajib) {
            return redirect()->route('admin.menuKelola.index')->with('error', 'Menu wajib tidak dapat dihapus');
        }

        if ($menu->children()->exists()) {
            return redirect()->route('admin.menuKelola.index')->with('error', 'Menu masih memiliki sub-menu. Hapus sub-menu terlebih dahulu.');
        }

        $menu->delete();

        return redirect()->route('admin.menuKelola.index')->with('success', 'Menu berhasil dihapus');
    }
}
