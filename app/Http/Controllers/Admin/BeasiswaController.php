<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scholarship\StoreScholarshipRequest;
use App\Http\Requests\Scholarship\UpdateScholarshipRequest;
use App\Models\Scholarship;
use App\Models\User;
use App\Notifications\NewScholarship;
use App\Notifications\PengumumanBeasiswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BeasiswaController extends Controller
{
    public function index(): View
    {
        $scholarships = Scholarship::latest()->paginate(10);

        return view('admin.beasiswa.index', compact('scholarships'));
    }

    public function create(): View
    {
        return view('admin.beasiswa.buat');
    }

    public function store(StoreScholarshipRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $fakultasData = $data['fakultas'] ?? [];
        unset($data['fakultas']);

        $scholarship = Scholarship::create($data);

        foreach ($fakultasData as $fakultasItem) {
            $fakultas = $scholarship->fakultas()->create(['nama' => $fakultasItem['nama']]);
            foreach ($fakultasItem['prodi'] as $prodiItem) {
                $fakultas->prodi()->create(['nama' => $prodiItem['nama']]);
            }
        }

        $users = User::role('user')->get();
        $users->each->notify(new NewScholarship($scholarship));

        return redirect()->route('admin.beasiswa.index')->with('success', 'Beasiswa berhasil ditambahkan');
    }

    public function show(Scholarship $scholarship): View
    {
        $scholarship->load('fakultas.prodi');
        $applicants = $scholarship->pendaftar()->with(['user.profile', 'beasiswa'])->latest()->paginate(10);

        return view('admin.beasiswa.lihat', compact('scholarship', 'applicants'));
    }

    public function edit(Scholarship $scholarship): View
    {
        $scholarship->load('fakultas.prodi');

        return view('admin.beasiswa.ubah', compact('scholarship'));
    }

    public function update(UpdateScholarshipRequest $request, Scholarship $scholarship): RedirectResponse
    {
        $data = $request->validated();
        $fakultasData = $data['fakultas'] ?? [];
        unset($data['fakultas']);

        $scholarship->update($data);

        $scholarship->fakultas()->delete();
        foreach ($fakultasData as $fakultasItem) {
            $fakultas = $scholarship->fakultas()->create(['nama' => $fakultasItem['nama']]);
            foreach ($fakultasItem['prodi'] as $prodiItem) {
                $fakultas->prodi()->create(['nama' => $prodiItem['nama']]);
            }
        }

        return redirect()->route('admin.beasiswa.index')->with('success', 'Beasiswa berhasil diperbarui');
    }

    public function destroy(Scholarship $scholarship): RedirectResponse
    {
        $scholarship->delete();

        return redirect()->route('admin.beasiswa.index')->with('success', 'Beasiswa berhasil dihapus');
    }

    public function umumkan(Request $request, Scholarship $scholarship): RedirectResponse
    {
        $request->validate([
            'tanggal_pengumuman' => 'required|date',
            'tanggal_pengumuman_selesai' => 'required|date|after_or_equal:tanggal_pengumuman',
        ]);

        $tanggal = $request->date('tanggal_pengumuman')->toDateString();
        $selesai = $request->date('tanggal_pengumuman_selesai')->toDateString();

        $baruDiumumkan = ! $scholarship->isDiumumkan();

        $scholarship->forceFill([
            'tanggal_pengumuman' => $tanggal,
            'tanggal_pengumuman_selesai' => $selesai,
        ])->save();

        if ($baruDiumumkan) {
            $pendaftar = $scholarship->pendaftar()
                ->with('user')
                ->get();

            $notifikasiBerhasil = 0;
            $notifikasiGagal = 0;

            foreach ($pendaftar as $applicant) {
                try {
                    // Coba kirim via queue
                    $applicant->user->notify(new PengumumanBeasiswa($scholarship));
                    $notifikasiBerhasil++;
                } catch (\Exception $e) {
                    // Fallback ke synchronous jika queue gagal
                    try {
                        $applicant->user->notifyNow(new PengumumanBeasiswa($scholarship));
                        $notifikasiBerhasil++;
                        Log::warning('Queue gagal, fallback ke sync untuk user: ' . $applicant->user->email, ['error' => $e->getMessage()]);
                    } catch (\Exception $e2) {
                        $notifikasiGagal++;
                        Log::error('Notifikasi gagal total untuk user: ' . $applicant->user->email, ['error' => $e2->getMessage()]);
                    }
                }
            }

            $message = 'Beasiswa berhasil diumumkan. Periode: ' . $tanggal . ' s.d. ' . $selesai;
            if ($notifikasiBerhasil > 0) {
                $message .= '. Notifikasi terkirim ke ' . $notifikasiBerhasil . ' penerima';
            }
            if ($notifikasiGagal > 0) {
                $message .= '. ' . $notifikasiGagal . ' notifikasi gagal (cek log).';
            }

            return redirect()->route('admin.beasiswa.lihat', $scholarship)
                ->with('success', $message);
        }

        return redirect()->route('admin.beasiswa.lihat', $scholarship)
            ->with('success', 'Tanggal pengumuman berhasil diperbarui. Periode: ' . $tanggal . ' s.d. ' . $selesai);
    }

    public function hasilPengumumanIndex(Scholarship $scholarship): View
    {
        $applicants = $scholarship->pendaftar()
            ->where('status', 'diterima')
            ->with(['user.profile', 'beasiswa'])
            ->latest()
            ->paginate(20);

        return view('admin.beasiswa.hasil-pengumuman', compact('scholarship', 'applicants'));
    }

    public function hasilPengumumanUpdate(Request $request, Scholarship $scholarship): RedirectResponse
    {
        $request->validate([
            'hasil' => 'required|array',
            'hasil.*' => 'in:diterima,tidak_diterima',
        ]);

        $updated = 0;
        foreach ($request->hasil as $applicantId => $hasil) {
            $applicant = $scholarship->pendaftar()->where('id', $applicantId)->where('status', 'diterima')->first();
            if ($applicant && $applicant->hasil_pengumuman !== $hasil) {
                $applicant->update(['hasil_pengumuman' => $hasil]);
                $updated++;
            }
        }

        return redirect()->route('admin.beasiswa.hasil-pengumuman', $scholarship)
            ->with('success', "Hasil pengumuman berhasil diperbarui untuk {$updated} pendaftar.");
    }

    public function pengumumanDestroy(Scholarship $scholarship): RedirectResponse
    {
        $scholarship->forceFill([
            'tanggal_pengumuman' => null,
            'tanggal_pengumuman_selesai' => null,
        ])->save();

        return redirect()->route('admin.beasiswa.lihat', $scholarship)
            ->with('success', 'Pengumuman berhasil dihapus. Tanggal pengumuman dan selesai dikosongkan.');
    }
}