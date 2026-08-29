<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Notifications\UserActivated;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->latest()->paginate(10);

        return view('admin.pengguna.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.pengguna.buat');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'status' => $validated['status'],
        ]);

        $user->assignRole($validated['peran']);

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna baru berhasil ditambahkan');
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('admin.pengguna.ubah', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        if (! auth()->user()->hasRole('super_admin')) {
            unset($validated['peran']);
        }

        $user->update($validated);

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

    public function destroy(User $user): RedirectResponse
    {
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
}
