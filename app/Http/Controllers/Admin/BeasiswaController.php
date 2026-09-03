<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scholarship\StoreScholarshipRequest;
use App\Http\Requests\Scholarship\UpdateScholarshipRequest;
use App\Models\Kampus;
use App\Models\Prodi;
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
        $kampusList = Kampus::with('fakultas.prodi')->orderBy('nama_kampus')->get();

        return view('admin.beasiswa.buat', compact('kampusList'));
    }

    public function store(StoreScholarshipRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $prodiIds = $data['prodi_ids'];
        unset($data['prodi_ids']);

        $data['kampus'] = Kampus::query()->findOrFail($data['kampus_id'])->nama_kampus;

        $scholarship = Scholarship::create($data);

        $this->syncFakultas($scholarship, $prodiIds);

        User::role('user')->lazy()->each->notify(new NewScholarship($scholarship));

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
        $kampusList = Kampus::with('fakultas.prodi')->orderBy('nama_kampus')->get();

        $selectedKampusId = $scholarship->kampus_id;
        if ($selectedKampusId === null) {
            $snapshotProdiNames = $scholarship->fakultas
                ->flatMap(fn ($fakultas) => $fakultas->prodi->pluck('nama'))
                ->all();
            $selectedKampusId = $kampusList->first(
                fn ($kampus) => $kampus->fakultas
                    ->flatMap(fn ($fakultas) => $fakultas->prodi->pluck('nama'))
                    ->intersect($snapshotProdiNames)
                    ->isNotEmpty()
            )?->id;
        }

        return view('admin.beasiswa.ubah', compact('scholarship', 'kampusList', 'selectedKampusId'));
    }

    public function update(UpdateScholarshipRequest $request, Scholarship $scholarship): RedirectResponse
    {
        $data = $request->validated();
        $prodiIds = $data['prodi_ids'];
        unset($data['prodi_ids']);

        $data['kampus'] = Kampus::query()->findOrFail($data['kampus_id'])->nama_kampus;

        $scholarship->update($data);
        $this->syncFakultas($scholarship, $prodiIds);

        return redirect()->route('admin.beasiswa.index')->with('success', 'Beasiswa berhasil diperbarui');
    }

    private function syncFakultas(Scholarship $scholarship, array $prodiIds): void
    {
        $scholarship->fakultas()->delete();

        $grouped = Prodi::with('fakultas')
            ->whereIn('id', $prodiIds)
            ->get()
            ->groupBy('fakultas_id');

        foreach ($grouped as $items) {
            $fakultas = $items->first()->fakultas;
            $record = $scholarship->fakultas()->create(['nama' => $fakultas->nama]);

            $record->prodi()->createMany($items->map(fn (Prodi $prodi) => ['nama' => $prodi->nama])->all());
        }
    }

    public function destroy(Scholarship $scholarship): RedirectResponse
    {
        $scholarship->delete();

        return redirect()->route('admin.beasiswa.index')->with('success', 'Beasiswa berhasil dihapus');
    }
}
