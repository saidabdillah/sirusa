<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $roles = Role::orderBy('id')->get();

        return view('admin.role.index', compact('roles'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse|JsonResponse
    {
        $role = Role::create(['name' => $request->validated('name')]);

        $wajibMenus = DB::table('menus')->where('wajib', true)->get(['id']);
        foreach ($wajibMenus as $menu) {
            DB::table('role_menu')->insert([
                'role_id' => $role->id,
                'menu_id' => $menu->id,
            ]);
        }

        return $this->ajaxOk($request, 'Role berhasil ditambahkan. Silakan atur akses menunya di halaman Akses Menu.', route('admin.role.index'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse|JsonResponse
    {
        $role->update(['name' => $request->validated('name')]);

        return $this->ajaxOk($request, 'Role berhasil diperbarui', route('admin.role.index'));
    }

    public function destroy(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        if ($role->name === 'super_admin') {
            return $this->ajaxFail($request, 'Role Super Admin tidak dapat dihapus', route('admin.role.index'));
        }

        $userCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();

        if ($userCount > 0) {
            return $this->ajaxFail($request, 'Role tidak dapat dihapus karena masih digunakan oleh '.$userCount.' pengguna', route('admin.role.index'));
        }

        $role->delete();

        return $this->ajaxOk($request, 'Role berhasil dihapus', route('admin.role.index'));
    }
}
