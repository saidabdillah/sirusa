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
use App\Support\DataTablesProcessor;
use Illuminate\Http\JsonResponse;
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

    public function data(Request $request): JsonResponse
    {
        $canManage = auth()->user()->hasMenuAccess('admin.beasiswa.index');

        $processor = new DataTablesProcessor(
            $request,
            Scholarship::latest(),
            [
                'searchable' => ['nama', 'kampus'],
                'orderable' => [1 => 'nama', 2 => 'kampus', 3 => 'kuota'],
            ],
            fn (Scholarship $scholarship, int $no) => [
                'no' => $no,
                'nama' => e($scholarship->nama),
                'kampus' => e($scholarship->kampus),
                'kuota' => $scholarship->kuota,
                'gelar' => e((string) $scholarship->tingkat_gelar),
                'batas' => $this->renderBatasWaktu($scholarship),
                'ipk' => number_format($scholarship->ipk_minimal, 2),
                'semester' => $scholarship->semester_minimal,
                'status' => $scholarship->status === 'aktif'
                    ? '<span class="badge badge-success">Aktif</span>'
                    : '<span class="badge badge-secondary">Non-aktif</span>',
                'aksi' => $canManage ? view('admin.beasiswa._aksi', ['data' => $scholarship])->render() : '',
            ]
        );

        return response()->json($processor->respond());
    }

    private function renderBatasWaktu(Scholarship $scholarship): string
    {
        $mulai = $scholarship->tanggal_mulai?->translatedFormat('d M Y');
        $selesai = $scholarship->tanggal_selesai?->translatedFormat('d M Y');

        $periode = ($mulai && $selesai) ? $mulai.' - '.$selesai : '-';

        if ($scholarship->isExpired()) {
            return '<span class="text-danger">'.e($periode).'</span>';
        }

        return e($periode);
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
