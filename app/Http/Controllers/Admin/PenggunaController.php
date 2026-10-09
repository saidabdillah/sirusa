<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Kampus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $users = User::with('roles')->latest()->get();

        return view('admin.pengguna.index', [
            'users' => $users,
            'roleLabels' => User::ROLE_LABELS,
        ]);
    }

    /**
     * Detail read-only satu pengguna: informasi akun + Data Diri profilnya.
     *
     * `akses.menu` sudah menolak role tanpa grant `admin.pengguna`; ini
     * defense-in-depth yang sama seperti `create()`/`edit()`. Berbeda dari
     * `abortUnlessMayTouch()` di bawah, membaca akun super_admin tetap
     * diperbolehkan karena halaman ini tidak mengubah apa pun.
     */
    public function show(User $user): View
    {
        abort_unless(auth()->user()->canManageUsers(), 403);

        $user->load(['roles', 'kampus', 'profile.user']);

        return view('admin.pengguna.lihat', [
            'user' => $user,
            'roleLabels' => User::ROLE_LABELS,
        ]);
    }

    public function create(): View
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->canManageUsers(), 403);

        return view('admin.pengguna.buat', [
            'kampusList' => $this->kampusList(),
            'peranOptions' => $currentUser->assignableRoleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        if ($validated['peran'] === 'user') {
            $username = $validated['username'] ?? null;

            if (! $username) {
                do {
                    $username = 'usr'.Str::lower(Str::random(8));
                } while (User::where('username', $username)->exists());
            }

            $user = User::create([
                'username' => $username,
                'email' => $validated['email'] ?? null,
                'password' => '12345678',
                'status' => $validated['status'],
            ]);
            $user->assignRole('user');

            $user->profile()->create([
                'nik' => $validated['nik'] ?? null,
            ]);

            $message = 'Pengguna baru berhasil ditambahkan. Kata sandi awal akun mahasiswa adalah 12345678.';
        } else {
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'],
                'status' => $validated['status'],
                'kampus_id' => $validated['peran'] === 'kampus' ? ($validated['kampus_id'] ?? null) : null,
            ]);
            $user->assignRole($validated['peran']);

            $message = 'Pengguna baru berhasil ditambahkan.';
        }

        return $this->ajaxOk($request, $message, route('admin.pengguna.index'));
    }

    public function edit(User $user): View
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->canManageUsers(), 403);
        $this->abortUnlessMayTouch($user);

        $user->load('roles');

        return view('admin.pengguna.ubah', [
            'user' => $user,
            'kampusList' => $this->kampusList(),
            'peranOptions' => $currentUser->assignableRoleOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $currentUser = auth()->user();

        $validated = $request->validated();

        // Menurunkan peran atau menonaktifkan akun sendiri akan mengunci akses ke modul ini.
        if ($user->id === $currentUser->id) {
            if ($validated['peran'] !== $user->roles->first()?->name) {
                return $this->ajaxFail($request, 'Anda tidak dapat mengubah peran akun sendiri', route('admin.pengguna.index'));
            }

            if ($validated['status'] === 'non-aktif') {
                return $this->ajaxFail($request, 'Anda tidak dapat menonaktifkan akun sendiri', route('admin.pengguna.index'));
            }
        }

        // Akun super_admin dilindungi: perannya tidak bisa diturunkan lewat form edit
        // (destroy() sudah menolak penghapusannya) dan statusnya tidak bisa dinonaktifkan —
        // aturan yang sama dengan shortcut Nonaktifkan di daftar.
        if ($user->hasRole('super_admin')) {
            if ($validated['peran'] !== 'super_admin') {
                return $this->ajaxFail($request, 'Anda tidak dapat mengubah peran Super Admin', route('admin.pengguna.index'));
            }

            if ($validated['status'] === 'non-aktif') {
                return $this->ajaxFail($request, 'Anda tidak dapat mengubah status Super Admin', route('admin.pengguna.index'));
            }
        }

        $user->update([
            'status' => $validated['status'],
            'kampus_id' => $validated['peran'] === 'kampus' ? ($validated['kampus_id'] ?? null) : null,
        ]);
        $user->syncRoles($validated['peran']);

        return $this->ajaxOk($request, 'Data pengguna berhasil diperbarui', route('admin.pengguna.index'));
    }

    /**
     * Mengaktifkan/menonaktifkan akun siapa pun kecuali super_admin.
     *
     * Gate memakai `canManageUsers()`, bukan `hasRole('super_admin')`, supaya
     * konsisten dengan `update()`: peran yang boleh menyunting akun lewat form
     * Ubah sudah bisa mengubah status di sana juga, jadi aksi ini harus terbuka
     * untuk peran yang sama. Kalau hanya super_admin, Kesra bisa mengubah status
     * lewat form Ubah tetapi tidak lewat shortcut di daftar.
     *
     * Dua pengecualian yang berlaku untuk semua peran: akun sendiri tidak boleh
     * dinonaktifkan (mengunci akses) dan akun super_admin tidak boleh disentuh
     * sama sekali.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()->canManageUsers(), 403);

        $this->abortUnlessMayTouch($user);

        if ($user->id === auth()->id()) {
            return $this->ajaxFail($request, 'Anda tidak dapat menonaktifkan akun sendiri', route('admin.pengguna.index'));
        }

        $newStatus = $user->status === 'aktif' ? 'non-aktif' : 'aktif';
        $user->update(['status' => $newStatus]);

        $label = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return $this->ajaxOk($request, "Pengguna berhasil {$label}", route('admin.pengguna.index'));
    }

    /**
     * Reset kata sandi ke default awal.
     *
     * Sama seperti `toggleStatus()`, gate-nya `canManageUsers()` agar konsisten
     * dengan hak akses menyunting akun. `abortUnlessMayTouch()` menahan peran
     * non-super_admin agar tidak bisa mereset kata sandi akun super_admin.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()->canManageUsers(), 403);

        $this->abortUnlessMayTouch($user);

        $user->update(['password' => '12345678']);

        return $this->ajaxOk($request, "Kata sandi '{$user->username}' berhasil direset menjadi 12345678", route('admin.pengguna.index'));
    }

    /**
     * Menghapus akun adalah aksi destruktif paling berat, jadi tetap khusus
     * super_admin meski peran lain boleh menyunting, mengubah status, dan
     * mereset kata sandi. Kesra sengaja tidak diberi akses hapus.
     */
    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $currentUser = auth()->user();

        if ($currentUser->id === $user->id) {
            return $this->ajaxFail($request, 'Anda tidak dapat menghapus akun sendiri', route('admin.pengguna.index'));
        }

        if ($user->hasRole('super_admin')) {
            return $this->ajaxFail($request, 'Anda tidak dapat menghapus Super Admin', route('admin.pengguna.index'));
        }

        $user->delete();

        return $this->ajaxOk($request, 'Pengguna berhasil dihapus', route('admin.pengguna.index'));
    }

    /**
     * Hanya super_admin yang boleh menyunting akun super_admin.
     *
     * Jalur POST sudah ditutup `UpdateUserRequest::authorize()`; ini untuk halaman
     * Form -> Ubah. `assignableRoles()` menutup jalur "beri peran super_admin",
     * sedangkan ini menutup jalur "sunting akun yang sudah jadi super_admin".
     */
    private function abortUnlessMayTouch(User $user): void
    {
        abort_if(
            $user->hasRole('super_admin') && ! auth()->user()->hasRole('super_admin'),
            403,
            'Anda tidak dapat menyunting akun Super Admin.',
        );
    }

    private function kampusList(): Collection
    {
        return Kampus::query()
            ->orderBy('nama_kampus')
            ->pluck('nama_kampus', 'id');
    }
}
