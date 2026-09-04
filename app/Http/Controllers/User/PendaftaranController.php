<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Applicant\StoreApplicantRequest;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use App\Notifications\NewApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PendaftaranController extends Controller
{
    public function create(Request $request): RedirectResponse|View
    {
        $user = auth()->user();

        if (! $user->isProfileComplete()) {
            $missing = $user->getMissingProfileFields();

            return redirect()->route('profile')
                ->with('error', 'Profil belum lengkap. Silakan lengkapi data berikut terlebih dahulu: '.implode(', ', $missing));
        }

        $scholarship = Scholarship::findOrFail($request->input('beasiswa_id'));

        if ($user->applicants()->where('beasiswa_id', $scholarship->id)->exists()) {
            return redirect()->route('user.beasiswa.lihat', $scholarship)
                ->with('error', 'Anda sudah mendaftar beasiswa ini.');
        }

        $eligibilityError = $scholarship->eligibilityIssueFor($user->profile);

        if ($eligibilityError) {
            return redirect()->route('user.beasiswa.lihat', $scholarship)
                ->with('error', $eligibilityError);
        }

        $profile = $user->profile;

        return view('user.pendaftaran.buat', compact('scholarship', 'profile'));
    }

    public function store(StoreApplicantRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $profile = $request->user()->profile;
        $data['fakultas'] = $profile?->prodi?->fakultas?->nama;
        $data['prodi'] = $profile?->prodi?->nama;
        $data['ipk'] = $profile?->ipk;
        $data['semester'] = $profile?->semester;

        $uploadPath = 'pendaftaran/'.auth()->id().'/'.$data['beasiswa_id'];
        $disk = Storage::disk('local');

        if (! $disk->exists($uploadPath)) {
            $disk->makeDirectory($uploadPath);
        }

        $fileFields = [
            'dokumen_ktp',
            'dokumen_kk',
            'dokumen_akta',
            'dokumen_surat_permohonan',
            'dokumen_transkrip',
            'dokumen_surat_aktif',
            'dokumen_surat_pernyataan',
            'dokumen_sktm',
            'dokumen_bukti_ukt',
        ];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = str_replace('dokumen_', '', $field).'.'.$file->getClientOriginalExtension();
                try {
                    $path = $disk->putFileAs($uploadPath, $file, $filename);
                    $data[$field] = $path;
                } catch (\Throwable $e) {
                    Log::error('Upload gagal untuk '.$field.': '.$e->getMessage());

                    return back()->withInput()->withErrors([
                        $field => 'Gagal upload '.$field.': '.$e->getMessage(),
                    ]);
                }
            }
        }

        if ($request->hasFile('dokumen_pas_foto')) {
            $file = $request->file('dokumen_pas_foto');
            $filename = 'pas_foto.'.$file->getClientOriginalExtension();
            try {
                $path = $disk->putFileAs($uploadPath, $file, $filename);
                $data['dokumen_pas_foto'] = $path;
            } catch (\Throwable $e) {
                Log::error('Upload gagal untuk dokumen_pas_foto: '.$e->getMessage());

                return back()->withInput()->withErrors([
                    'dokumen_pas_foto' => 'Gagal upload pas foto: '.$e->getMessage(),
                ]);
            }
        }

        if ($request->hasFile('dokumen_prestasi')) {
            $prestasi = [];
            foreach ($request->file('dokumen_prestasi') as $i => $file) {
                $filename = 'prestasi_'.($i + 1).'.'.$file->getClientOriginalExtension();
                try {
                    $path = $disk->putFileAs($uploadPath, $file, $filename);
                    $prestasi[] = $path;
                } catch (\Throwable $e) {
                    Log::error('Upload gagal untuk dokumen_prestasi['.$i.']: '.$e->getMessage());

                    return back()->withInput()->withErrors([
                        'dokumen_prestasi' => 'Gagal upload prestasi ke-'.($i + 1).': '.$e->getMessage(),
                    ]);
                }
            }
            $data['dokumen_prestasi'] = $prestasi;
        }

        foreach (['ktp_ayah', 'ktp_ibu', 'ktp_wali', 'kk_wali'] as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = $field.'.'.$file->getClientOriginalExtension();
                try {
                    $path = $disk->putFileAs($uploadPath, $file, $filename);
                    $data[$field] = $path;
                } catch (\Throwable $e) {
                    Log::error('Upload gagal untuk '.$field.': '.$e->getMessage());

                    return back()->withInput()->withErrors([
                        $field => 'Gagal upload '.$field.': '.$e->getMessage(),
                    ]);
                }
            }
        }

        $applicant = Applicant::create($data);

        $applicantName = auth()->user()->profile?->nama_lengkap ?? auth()->user()->username;
        $admins = User::role(['super_admin', 'admin'])->get();
        $admins->each->notify(new NewApplication($applicant, $applicantName));

        return redirect()->route('user.pendaftaran.index')->with('success', 'Pendaftaran berhasil dikirim');
    }

    public function index(): View
    {
        $applicants = Applicant::with('beasiswa')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('user.pendaftaran.index', compact('applicants'));
    }

    public function show(Applicant $applicant): View
    {
        if ($applicant->user_id !== auth()->id()) {
            abort(403);
        }

        $applicant->load('beasiswa');

        return view('user.pendaftaran.lihat', compact('applicant'));
    }

    public function edit(Applicant $applicant): View
    {
        if ($applicant->user_id !== auth()->id()) {
            abort(403);
        }

        if ($applicant->status !== 'revisi') {
            return redirect()->route('user.pendaftaran.lihat', $applicant)
                ->with('error', 'Pendaftaran hanya bisa diperbarui jika status Revisi');
        }

        $applicant->load('beasiswa');
        $profile = auth()->user()->profile;

        return view('user.pendaftaran.lengkapi', compact('applicant', 'profile'));
    }

    public function update(Request $request, Applicant $applicant): RedirectResponse
    {
        if ($applicant->user_id !== auth()->id()) {
            abort(403);
        }

        if ($applicant->status !== 'revisi') {
            return redirect()->route('user.pendaftaran.lihat', $applicant)
                ->with('error', 'Pendaftaran hanya bisa diperbarui jika status Revisi');
        }

        $request->validate([
            'dokumen_ktp' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_akta' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_surat_permohonan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_transkrip' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_surat_aktif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_pas_foto' => 'nullable|file|mimes:jpg,jpeg,png|max:20480',
            'dokumen_prestasi.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_surat_pernyataan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_sktm' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_bukti_ukt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'ktp_ayah' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'ktp_ibu' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'ktp_wali' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'kk_wali' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $profile = $request->user()->profile;
        $data = [
            'fakultas' => $profile?->prodi?->fakultas?->nama,
            'prodi' => $profile?->prodi?->nama,
            'ipk' => $profile?->ipk,
            'semester' => $profile?->semester,
        ];

        $uploadPath = 'pendaftaran/'.auth()->id().'/'.$applicant->beasiswa_id;
        $disk = Storage::disk('local');

        if (! $disk->exists($uploadPath)) {
            $disk->makeDirectory($uploadPath);
        }

        $fileFields = [
            'dokumen_ktp',
            'dokumen_kk',
            'dokumen_akta',
            'dokumen_surat_permohonan',
            'dokumen_transkrip',
            'dokumen_surat_aktif',
            'dokumen_surat_pernyataan',
            'dokumen_sktm',
            'dokumen_bukti_ukt',
        ];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = str_replace('dokumen_', '', $field).'.'.$file->getClientOriginalExtension();
                try {
                    if ($applicant->$field && $disk->exists($applicant->$field)) {
                        $disk->delete($applicant->$field);
                    }
                    $path = $disk->putFileAs($uploadPath, $file, $filename);
                    $data[$field] = $path;
                } catch (\Throwable $e) {
                    Log::error('Upload gagal untuk '.$field.': '.$e->getMessage());

                    return back()->withInput()->withErrors([
                        $field => 'Gagal upload '.$field.': '.$e->getMessage(),
                    ]);
                }
            }
        }

        if ($request->hasFile('dokumen_pas_foto')) {
            $file = $request->file('dokumen_pas_foto');
            $filename = 'pas_foto.'.$file->getClientOriginalExtension();
            try {
                if ($applicant->dokumen_pas_foto && $disk->exists($applicant->dokumen_pas_foto)) {
                    $disk->delete($applicant->dokumen_pas_foto);
                }
                $path = $disk->putFileAs($uploadPath, $file, $filename);
                $data['dokumen_pas_foto'] = $path;
            } catch (\Throwable $e) {
                Log::error('Upload gagal untuk dokumen_pas_foto: '.$e->getMessage());

                return back()->withInput()->withErrors([
                    'dokumen_pas_foto' => 'Gagal upload pas foto: '.$e->getMessage(),
                ]);
            }
        }

        if ($request->hasFile('dokumen_prestasi')) {
            $prestasi = $applicant->dokumen_prestasi ?? [];
            foreach ($request->file('dokumen_prestasi') as $i => $file) {
                $filename = 'prestasi_'.(count($prestasi) + $i + 1).'.'.$file->getClientOriginalExtension();
                try {
                    $path = $disk->putFileAs($uploadPath, $file, $filename);
                    $prestasi[] = $path;
                } catch (\Throwable $e) {
                    Log::error('Upload gagal untuk dokumen_prestasi['.$i.']: '.$e->getMessage());

                    return back()->withInput()->withErrors([
                        'dokumen_prestasi' => 'Gagal upload prestasi ke-'.($i + 1).': '.$e->getMessage(),
                    ]);
                }
            }
            $data['dokumen_prestasi'] = $prestasi;
        }

        foreach (['ktp_ayah', 'ktp_ibu', 'ktp_wali', 'kk_wali'] as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = $field.'.'.$file->getClientOriginalExtension();
                try {
                    $old = $applicant->{$field};
                    $path = $disk->putFileAs($uploadPath, $file, $filename);

                    if ($old && $disk->exists($old)) {
                        $disk->delete($old);
                    }

                    $data[$field] = $path;
                } catch (\Throwable $e) {
                    Log::error('Upload gagal untuk '.$field.': '.$e->getMessage());

                    return back()->withInput()->withErrors([
                        $field => 'Gagal upload '.$field.': '.$e->getMessage(),
                    ]);
                }
            }
        }

        $data['status'] = 'verifikasi';
        $applicant->update($data);

        return redirect()->route('user.pendaftaran.lihat', $applicant)
            ->with('success', 'Pendaftaran berhasil diperbarui dan dikirim untuk verifikasi ulang');
    }
}
