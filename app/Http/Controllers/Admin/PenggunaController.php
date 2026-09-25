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

        return view('admin.pengguna.index', compact('users'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        return view('admin.pengguna.buat', [
            'kampusList' => $this->kampusList(),
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
                'nik' => $validated['nik'],
                'nim' => $validated['nim'] ?? null,
            ]);
        } else {
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'],
                'status' => $validated['status'],
                'kampus_id' => $validated['peran'] === 'kampus' ? ($validated['kampus_id'] ?? null) : null,
            ]);
            $user->assignRole($validated['peran']);
        }

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna baru berhasil ditambahkan. Kata sandi awal akun mahasiswa adalah 12345678.');
    }

    public function edit(User $user): View
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $user->load('roles');

        return view('admin.pengguna.ubah', [
            'user' => $user,
            'kampusList' => $this->kampusList(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        if (! auth()->user()->hasRole('super_admin')) {
            unset($validated['peran']);
        }

        $payload = $validated;

        if (! auth()->user()->hasRole('super_admin')) {
            unset($payload['peran']);
        }

        if (isset($payload['peran']) && $payload['peran'] !== 'kampus') {
            $payload['kampus_id'] = null;
        }

        $user->update($payload);

        if (isset($validated['peran'])) {
            $user->syncRoles($validated['peran']);
        }

        return redirect()->route('admin.pengguna.index')->with('success', 'Data pengguna berhasil diperbarui');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $currentUser = auth()->user();

        if (! $currentUser->hasRole('super_admin') && $user->hasRole('super_admin')) {
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

    private function kampusList(): Collection
    {
        return Kampus::query()
            ->orderBy('nama_kampus')
            ->pluck('nama_kampus', 'id');
    }
}
