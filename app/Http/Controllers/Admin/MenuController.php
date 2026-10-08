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
        $validated = $request->validated();
        // Hanya terjemahkan `parent_id` kalau kirimannya benar-benar membawa
        // kunci itu. Request programatis yang tidak menyebut `parent_id` tidak
        // boleh diam-diam menjadikan anak menu jadi top-level (store) atau
        // melepas induk yang tersimpan (update).
        if (array_key_exists('parent_id', $request->all())) {
            $validated['parent_id'] = $this->resolveParentId(
                $request->input('parent_id'),
                (string) $validated['section'],
                (int) $validated['urutan'],
            );
        }

        $menu = Menu::create(array_merge($validated, [
            // Form tidak lagi menawarkan `aktif`/`wajib`, jadi menu baru selalu
            // lahir aktif dan tidak wajib. Nilainya ditulis eksplisit di sini
            // (bukan cuma mengandalkan default kolom) supaya permintaan yang
            // mengirim kedua kunci itu lewat API/curl tetap diabaikan.
            'aktif' => true,
            'wajib' => false,
        ]));

        $this->grantToSuperAdmin($menu);

        return $this->ajaxOk($request, 'Menu berhasil ditambahkan', route('admin.menukelola.index'));
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        if (array_key_exists('parent_id', $request->all())) {
            $validated['parent_id'] = $this->resolveParentId(
                $request->input('parent_id'),
                (string) $validated['section'],
                (int) $validated['urutan'],
            );
        }

        $menu->update($validated);

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

    /**
     * Terjemahkan isi select `parent_id` menjadi id menu.
     *
     * Select-nya memakai Select2 `tags`, jadi admin bisa mengetik nama grup
     * yang belum ada. Tiga kemungkinan:
     *
     * 1. kosong -> menu top-level (null);
     * 2. angka yang menunjuk menu yang ada -> dipakai apa adanya;
     * 3. nama baru -> dibuat sebagai menu top-level, KECUALI ada menu
     *    top-level berlabel sama (cocok tanpa memedulikan huruf besar/kecil),
     *    yang berarti admin hanya mengetik ulang nama yang sudah ada.
     *
     * Menu induk baru sengaja dibuat serendah mungkin supaya tidak berpura-pura
     * jadi menu jadi: `route`/`scope` NULL (induk murni pemicu dropdown), ikon
     * `fas fa-folder`, `section` dan `urutan` diambil dari menu anak yang sedang
     * disimpan sehingga induknya muncul persis di grup dan posisi yang sama
     * dengan anak pertamanya, `aktif` true, `wajib` false. `scope` NULL juga
     * berarti induk tidak memberi akses route apa pun -- akses tetap dipegang
     * scope anak masing-masing.
     *
     * Hanya `parent_id IS NULL` yang boleh dicocokkan/dibuat: sidebar hanya
     * merender dua tingkat, jadi induk yang kebetulan berupa anak akan membuat
     * menu baru itu tidak pernah tampil.
     */
    private function resolveParentId(mixed $rawParent, string $section, int $urutan): ?int
    {
        $label = is_scalar($rawParent) ? trim((string) $rawParent) : '';

        if ($label === '') {
            return null;
        }

        if (Menu::isExistingMenuId($label)) {
            return (int) $label;
        }

        $sejenis = Menu::query()
            ->whereNull('parent_id')
            ->whereRaw('LOWER(label) = ?', [mb_strtolower($label)])
            ->first();

        if ($sejenis) {
            return $sejenis->id;
        }

        $induk = Menu::create([
            'label' => $label,
            'section' => $section,
            'icon' => 'fas fa-folder',
            'route' => null,
            'scope' => null,
            'urutan' => $urutan,
            'aktif' => true,
            'wajib' => false,
        ]);

        $this->grantToSuperAdmin($induk);

        return $induk->id;
    }

    /**
     * Menu baru otomatis di-grant ke super_admin, sama seperti `store()`
     * sebelumnya -- tanpa ini menu buatan admin tidak muncul di sidebar
     * siapa pun sampai grant-nya diatur manual di halaman Akses Menu.
     *
     * `insertOrIgnore` supaya aman dipanggil dua kali untuk menu yang sama
     * (pivot `role_menu` punya primary key gabungan role_id+menu_id).
     */
    private function grantToSuperAdmin(Menu $menu): void
    {
        DB::table('role_menu')->insertOrIgnore([
            'role_id' => Role::findByName('super_admin')->id,
            'menu_id' => $menu->id,
        ]);
    }
}
