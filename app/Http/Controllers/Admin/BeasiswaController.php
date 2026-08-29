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
            'tanggal_pengumuman' => 'nullable|date',
        ]);

        $tanggal = $request->date('tanggal_pengumuman')?->toDateString() ?? now()->toDateString();

        $baruDiumumkan = ! $scholarship->isDiumumkan();

        $scholarship->forceFill(['tanggal_pengumuman' => $tanggal])->save();

        if ($baruDiumumkan) {
            $penerima = $scholarship->pendaftar()
                ->where('status', 'selesai')
                ->with('user')
                ->get();

            foreach ($penerima as $applicant) {
                $applicant->user->notify(new PengumumanBeasiswa($scholarship, 'diumumkan', $applicant));
            }

            return redirect()->route('admin.beasiswa.lihat', $scholarship)
                ->with('success', 'Beasiswa berhasil diumumkan dan '.$penerima->count().' pendaftar telah diberitahu.');
        }

        return redirect()->route('admin.beasiswa.lihat', $scholarship)
            ->with('success', 'Tanggal pengumuman berhasil diperbarui.');
    }

    public function bayarkan(Request $request, Scholarship $scholarship): RedirectResponse
    {
        $request->validate([
            'tanggal_pembayaran' => 'nullable|date',
        ]);

        if (! $scholarship->isDiumumkan()) {
            return redirect()->route('admin.beasiswa.lihat', $scholarship)
                ->with('error', 'Beasiswa harus diumumkan terlebih dahulu sebelum ditandai dibayarkan.');
        }

        $tanggal = $request->date('tanggal_pembayaran')?->toDateString() ?? now()->toDateString();

        $baruDibayarkan = ! $scholarship->isDibayarkan();

        $scholarship->forceFill(['tanggal_pembayaran' => $tanggal])->save();

        if ($baruDibayarkan) {
            $penerima = $scholarship->pendaftar()
                ->where('status', 'selesai')
                ->with('user')
                ->get();

            foreach ($penerima as $applicant) {
                $applicant->user->notify(new PengumumanBeasiswa($scholarship, 'dibayarkan', $applicant));
            }

            return redirect()->route('admin.beasiswa.lihat', $scholarship)
                ->with('success', 'Beasiswa berhasil ditandai dibayarkan dan '.$penerima->count().' pendaftar telah diberitahu.');
        }

        return redirect()->route('admin.beasiswa.lihat', $scholarship)
            ->with('success', 'Tanggal pembayaran berhasil diperbarui.');
    }
}
