<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuAccessRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class MenuController extends Controller
{
    use RespondsToAjax;

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

    public function perbarui(UpdateMenuAccessRequest $request): RedirectResponse|JsonResponse
    {
        $grants = $request->validated('grants') ?? [];

        $allMenuIds = DB::table('menus')->where('aktif', true)->pluck('id')->all();
        $wajibMenuIds = DB::table('menus')->where('wajib', true)->pluck('id')->all();
        $superAdminModuleIds = DB::table('menus')->whereIn('scope', ['admin.role', 'admin.menu', 'admin.menukelola', 'admin.pengguna'])->pluck('id')->all();

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

        return $this->ajaxOk($request, 'Akses menu berhasil diperbarui', route('admin.menu.index'));
    }

    public function kelola(): View
    {
        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('urutan')->get();
        $parents = Menu::whereNull('parent_id')->orderBy('urutan')->get();

        return view('admin.menu.kelola', compact('menus', 'parents'));
    }

    public function store(StoreMenuRequest $request): RedirectResponse|JsonResponse
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

        return $this->ajaxOk($request, 'Menu berhasil ditambahkan', route('admin.menukelola.index'));
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse|JsonResponse
    {
        $menu->update(array_merge($request->validated(), [
            'aktif' => $request->boolean('aktif'),
            'wajib' => $request->boolean('wajib'),
        ]));

        return $this->ajaxOk($request, 'Menu berhasil diperbarui', route('admin.menukelola.index'));
    }

    public function destroy(Request $request, Menu $menu): RedirectResponse|JsonResponse
    {
        if ($menu->wajib) {
            return $this->ajaxFail($request, 'Menu wajib tidak dapat dihapus', route('admin.menukelola.index'));
        }

        if ($menu->children()->exists()) {
            return $this->ajaxFail($request, 'Menu masih memiliki sub-menu. Hapus sub-menu terlebih dahulu.', route('admin.menukelola.index'));
        }

        $menu->delete();

        return $this->ajaxOk($request, 'Menu berhasil dihapus', route('admin.menukelola.index'));
    }
}
