<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::orderBy('id')->get();

        return view('admin.role.index', compact('roles'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name')]);

        $wajibMenus = DB::table('menus')->where('wajib', true)->get(['id']);
        foreach ($wajibMenus as $menu) {
            DB::table('role_menu')->insert([
                'role_id' => $role->id,
                'menu_id' => $menu->id,
            ]);
        }

        return redirect()->route('admin.role.index')->with('success', 'Role berhasil ditambahkan. Silakan atur akses menunya di halaman Akses Menu.');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update(['name' => $request->validated('name')]);

        return redirect()->route('admin.role.index')->with('success', 'Role berhasil diperbarui');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'super_admin') {
            return redirect()->route('admin.role.index')->with('error', 'Role Super Admin tidak dapat dihapus');
        }

        $userCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();

        if ($userCount > 0) {
            return redirect()->route('admin.role.index')->with('error', 'Role tidak dapat dihapus karena masih digunakan oleh '.$userCount.' pengguna');
        }

        $role->delete();

        return redirect()->route('admin.role.index')->with('success', 'Role berhasil dihapus');
    }
}
