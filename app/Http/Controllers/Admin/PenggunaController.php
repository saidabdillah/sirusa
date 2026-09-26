<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Kampus;
use App\Models\User;
use App\Notifications\UserActivated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->latest()->get();

        return view('admin.pengguna.index', [
            'users' => $users,
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

    public function store(StoreUserRequest $request): RedirectResponse
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

        return redirect()->route('admin.pengguna.index')->with('success', $message);
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

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $currentUser = auth()->user();

        $validated = $request->validated();

        // Menurunkan peran atau menonaktifkan akun sendiri akan mengunci akses ke modul ini.
        if ($user->id === $currentUser->id) {
            if ($validated['peran'] !== $user->roles->first()?->name) {
                return redirect()->route('admin.pengguna.index')->with('error', 'Anda tidak dapat mengubah peran akun sendiri');
            }

            if ($validated['status'] === 'non-aktif') {
                return redirect()->route('admin.pengguna.index')->with('error', 'Anda tidak dapat menonaktifkan akun sendiri');
            }
        }

        // Akun super_admin dilindungi: perannya tidak bisa diturunkan lewat form edit
        // (destroy() sudah menolak penghapusannya) dan statusnya tidak bisa dinonaktifkan —
        // aturan yang sama dengan shortcut Nonaktifkan di daftar.
        if ($user->hasRole('super_admin')) {
            if ($validated['peran'] !== 'super_admin') {
                return redirect()->route('admin.pengguna.index')->with('error', 'Anda tidak dapat mengubah peran Super Admin');
            }

            if ($validated['status'] === 'non-aktif') {
                return redirect()->route('admin.pengguna.index')->with('error', 'Anda tidak dapat mengubah status Super Admin');
            }
        }

        $user->update([
            'status' => $validated['status'],
            'kampus_id' => $validated['peran'] === 'kampus' ? ($validated['kampus_id'] ?? null) : null,
        ]);
        $user->syncRoles($validated['peran']);

        return redirect()->route('admin.pengguna.index')->with('success', 'Data pengguna berhasil diperbarui');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        if ($user->hasRole('super_admin')) {
            return redirect()->route('admin.pengguna.index')->with('error', 'Anda tidak dapat mengubah status Super Admin');
        }

        $newStatus = $user->status === 'aktif' ? 'non-aktif' : 'aktif';
        $user->update(['status' => $newStatus]);

        if ($newStatus === 'aktif') {
            $user->notify(new UserActivated($user->username));
        }

        $label = $newStatus === 'aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.pengguna.index')->with('success', "Pengguna berhasil {$label}");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $user->update(['password' => '12345678']);

        return redirect()->route('admin.pengguna.index')->with('success', "Kata sandi '{$user->username}' berhasil direset menjadi 12345678");
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $currentUser = auth()->user();

        if ($currentUser->id === $user->id) {
            return redirect()->route('admin.pengguna.index')->with('error', 'Anda tidak dapat menghapus akun sendiri');
        }

        if ($user->hasRole('super_admin')) {
            return redirect()->route('admin.pengguna.index')->with('error', 'Anda tidak dapat menghapus Super Admin');
        }

        $user->delete();

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil dihapus');
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
