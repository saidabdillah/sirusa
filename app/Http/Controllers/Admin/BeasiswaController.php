<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scholarship\StoreScholarshipRequest;
use App\Http\Requests\Scholarship\UpdateScholarshipRequest;
use App\Models\Scholarship;
use App\Models\User;
use App\Notifications\NewScholarship;
use Illuminate\Http\RedirectResponse;
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
}
