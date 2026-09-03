<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\UpdateProfilRequest;
use App\Models\Kampus;
use App\Models\UserProfile;
use App\Services\WilayahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    private const PROVINSI = 'Kalimantan Selatan';

    private const KABUPATEN = 'Balangan';

    private const KABUPATEN_CODE = '6311';

    public function __construct(
        private WilayahService $wilayah
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->profile;
        $missingFields = $user->getMissingProfileFields();
        $profileComplete = $user->isProfileComplete();

        $districts = $this->wilayah->getDistricts(self::KABUPATEN_CODE);

        $kampusList = Kampus::with('fakultas.prodi')
            ->orderBy('nama_kampus')
            ->get();

        $kampusJson = $kampusList->map(function (Kampus $kampus) {
            return [
                'nama' => $kampus->nama_kampus,
                'fakultas' => $kampus->fakultas->map(function ($fakultas) {
                    return [
                        'nama' => $fakultas->nama,
                        'prodi' => $fakultas->prodi->map(fn ($prodi) => [
                            'id' => $prodi->id,
                            'nama' => $prodi->nama,
                        ])->values(),
                    ];
                })->values(),
            ];
        });

        $selectedDistrict = null;
        if ($profile?->kecamatan) {
            $selectedDistrict = collect($districts)->first(
                fn ($district) => strtolower($district['district'] ?? '') === strtolower($profile->kecamatan)
            );
        }

        return view('profil.index', compact(
            'profile',
            'missingFields',
            'profileComplete',
            'districts',
            'selectedDistrict',
            'kampusList',
            'kampusJson'
        ));
    }

    public function update(UpdateProfilRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['provinsi'] = self::PROVINSI;
        $data['kabupaten_kota'] = self::KABUPATEN;

        try {
            if ($request->hasFile('foto_profil')) {
                $file = $request->file('foto_profil');
                $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
                $data['foto_profil'] = Storage::disk('public')->putFileAs('profil', $file, $filename);
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
