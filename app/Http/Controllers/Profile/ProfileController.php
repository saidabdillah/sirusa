<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\UpdateProfilRequest;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->profile;
        $missingFields = $user->getMissingProfileFields();
        $profileComplete = $user->isProfileComplete();

        return view('profil.index', compact('profile', 'missingFields', 'profileComplete'));
    }

    public function update(UpdateProfilRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            if ($request->hasFile('foto_profil')) {
                $file = $request->file('foto_profil');
                $filename = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('storage/profil'), $filename);
                $data['foto_profil'] = 'profil/'.$filename;
            }

            UserProfile::updateOrCreate(
                ['user_id' => auth()->id()],
                $data
            );

            return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('Gagal update profil: '.$e->getMessage());

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan profil: '.$e->getMessage());
        }
    }
}
