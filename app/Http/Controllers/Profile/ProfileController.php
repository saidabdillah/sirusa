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
use Illuminate\View\View;

class ProfileController extends Controller
{
    private const PROVINSI = 'Kalimantan Selatan';

    private const KABUPATEN = 'Balangan';

    private const KABUPATEN_CODE = '6311';

    private array $documentFields = [
        'dokumen_ktp',
        'dokumen_kk',
        'dokumen_desil',
        'dokumen_sktm',
        'dokumen_transkrip',
        'dokumen_surat_aktif',
        'dokumen_surat_pernyataan',
        'dokumen_bukti_ukt',
        'ktp_ayah',
        'ktp_ibu',
        'ktp_wali',
        'kk_wali',
    ];

    public function __construct(
        private WilayahService $wilayah
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->profile;
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

        return view('profil.index', compact(
            'profile',
            'profileComplete',
            'districts',
            'kampusList',
            'kampusJson'
        ));
    }

    public function update(UpdateProfilRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['provinsi'] = self::PROVINSI;
        $data['kabupaten_kota'] = self::KABUPATEN;

        $ikutKk = $request->input('ikut_kk', 'ayah');

        $data['ikut_kk'] = $ikutKk;
        $data['kk_ikut_wali'] = $ikutKk === 'wali';

        if (! $data['kk_ikut_wali']) {
            $data['nama_wali'] = null;
            $data['nik_wali'] = null;
            $data['pekerjaan_wali'] = null;
            $data['hubungan_wali'] = null;
            $data['ktp_wali'] = null;
            $data['kk_wali'] = null;
        }

        $profile = auth()->user()->profile;
        $uploadDisk = Storage::disk('public');

        try {
            foreach (['foto_profil'] as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $old = $profile?->{$field};
                    $data[$field] = $uploadDisk->putFile('profil/'.auth()->id(), $file, 'public');
                    $this->deleteIfExists($uploadDisk, $old);
                }
            }

            foreach ($this->documentFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $old = $profile?->{$field};
                    $data[$field] = $uploadDisk->putFile('profil/'.auth()->id(), $file, 'public');
                    $this->deleteIfExists($uploadDisk, $old);
                }
            }

            if ($request->hasFile('dokumen_prestasi')) {
                $prestasi = $profile?->dokumen_prestasi ?? [];
                foreach ($request->file('dokumen_prestasi') as $file) {
                    $path = $uploadDisk->putFile('profil/'.auth()->id(), $file, 'public');
                    $prestasi[] = $path;
                }
                $data['dokumen_prestasi'] = $prestasi;
            }

            $profile = UserProfile::updateOrCreate(
                ['user_id' => auth()->id()],
                $data
            );

            $profile->resetVerification();
            $profile->save();

            return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui');
        } catch (\Throwable $e) {
            Log::error('Gagal update profil: '.$e->getMessage());

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan profil: '.$e->getMessage());
        }
    }

    private function deleteIfExists($disk, ?string $path): void
    {
        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
