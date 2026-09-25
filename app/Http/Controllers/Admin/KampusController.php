<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kampus\MassDeleteFakultasRequest;
use App\Http\Requests\Kampus\MassDeleteKampusRequest;
use App\Http\Requests\Kampus\MassDeleteProdiRequest;
use App\Http\Requests\Kampus\StoreFakultasRequest;
use App\Http\Requests\Kampus\StoreKampusRequest;
use App\Http\Requests\Kampus\StoreProdiRequest;
use App\Http\Requests\Kampus\UpdateFakultasRequest;
use App\Http\Requests\Kampus\UpdateKampusRequest;
use App\Http\Requests\Kampus\UpdateProdiRequest;
use App\Models\Fakultas;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Support\DataTablesProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KampusController extends Controller
{
    // ─── Kampus ────────────────────────────────────────────────────

    public function index(): View
    {
        $kampus = Kampus::withCount('fakultas')->orderBy('nama_kampus')->limit(25)->get();

        return view('admin.kampus.index', compact('kampus'));
    }

    public function data(Request $request): JsonResponse
    {
        $canManage = auth()->user()->hasMenuAccess('admin.kampus.index');

        $processor = new DataTablesProcessor(
            $request,
            Kampus::withCount('fakultas')->orderBy('nama_kampus'),
            [
                'searchable' => ['nama_kampus'],
                'orderable' => [2 => 'nama_kampus'],
            ],
            fn (Kampus $kampus, int $no) => [
                'checkbox' => $canManage ? '<input type="checkbox" class="row-check" value="'.$kampus->id.'">' : '',
                'no' => $no,
                'kampus' => e($kampus->nama_kampus),
                'fakultas_count' => $kampus->fakultas_count,
                'aksi' => $canManage ? view('admin.kampus._aksi', ['data' => $kampus])->render() : '',
            ]
        );

        return response()->json($processor->respond());
    }

    public function create(): View
    {
        return view('admin.kampus.buat');
    }

    public function store(StoreKampusRequest $request): RedirectResponse
    {
        foreach ($request->validated('nama_kampus') as $nama) {
            Kampus::create(['nama_kampus' => $nama]);
        }

        return redirect()->route('admin.kampus.index')->with('success', 'Kampus berhasil ditambahkan');
    }

    public function edit(Kampus $kampus): View
    {
        return view('admin.kampus.ubah', compact('kampus'));
    }

    public function update(UpdateKampusRequest $request, Kampus $kampus): RedirectResponse
    {
        $kampus->update($request->validated());

        return redirect()->route('admin.kampus.index')->with('success', 'Kampus berhasil diperbarui');
    }

    public function destroy(Kampus $kampus): RedirectResponse
    {
        $kampus->delete();

        return redirect()->route('admin.kampus.index')->with('success', 'Kampus berhasil dihapus');
    }

    public function massDestroy(MassDeleteKampusRequest $request): RedirectResponse
    {
        $count = Kampus::whereIn('id', $request->validated('ids'))->delete();

        return redirect()->route('admin.kampus.index')
            ->with('success', "{$count} kampus berhasil dihapus");
    }

    // ─── Fakultas ──────────────────────────────────────────────────

    public function fakultasIndex(Kampus $kampus): View
    {
        $fakultas = $kampus->fakultas()->withCount('prodi')->orderBy('nama')->limit(25)->get();

        return view('admin.kampus.fakultas.index', compact('kampus', 'fakultas'));
    }

    public function fakultasData(Request $request, Kampus $kampus): JsonResponse
    {
        $canManage = auth()->user()->hasMenuAccess('admin.kampus.index');

        $processor = new DataTablesProcessor(
            $request,
            $kampus->fakultas()->withCount('prodi')->orderBy('nama'),
            [
                'searchable' => ['nama'],
                'orderable' => [2 => 'nama'],
            ],
            fn (Fakultas $fakultas, int $no) => [
                'checkbox' => $canManage ? '<input type="checkbox" class="row-check" value="'.$fakultas->id.'">' : '',
                'no' => $no,
                'nama' => e($fakultas->nama),
                'prodi_count' => $fakultas->prodi_count,
                'aksi' => $canManage ? view('admin.kampus.fakultas._aksi', ['kampus' => $kampus, 'data' => $fakultas])->render() : '',
            ]
        );

        return response()->json($processor->respond());
    }

    public function fakultasCreate(Kampus $kampus): View
    {
        return view('admin.kampus.fakultas.buat', compact('kampus'));
    }

    public function fakultasStore(StoreFakultasRequest $request, Kampus $kampus): RedirectResponse
    {
        foreach ($request->validated('nama') as $nama) {
            $kampus->fakultas()->create(['nama' => $nama]);
        }

        return redirect()->route('admin.kampus.fakultas.index', $kampus)
            ->with('success', 'Fakultas berhasil ditambahkan');
    }

    public function fakultasEdit(Kampus $kampus, Fakultas $fakultas): View
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        return view('admin.kampus.fakultas.ubah', compact('kampus', 'fakultas'));
    }

    public function fakultasUpdate(UpdateFakultasRequest $request, Kampus $kampus, Fakultas $fakultas): RedirectResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $fakultas->update($request->validated());

        return redirect()->route('admin.kampus.fakultas.index', $kampus)
            ->with('success', 'Fakultas berhasil diperbarui');
    }

    public function fakultasDestroy(Kampus $kampus, Fakultas $fakultas): RedirectResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $fakultas->delete();

        return redirect()->route('admin.kampus.fakultas.index', $kampus)
            ->with('success', 'Fakultas berhasil dihapus');
    }

    public function fakultasMassDestroy(MassDeleteFakultasRequest $request, Kampus $kampus): RedirectResponse
    {
        $count = Fakultas::where('kampus_id', $kampus->id)
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return redirect()->route('admin.kampus.fakultas.index', $kampus)
            ->with('success', "{$count} fakultas berhasil dihapus");
    }

    // ─── Prodi ─────────────────────────────────────────────────────

    public function prodiIndex(Kampus $kampus, Fakultas $fakultas): View
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $prodi = $fakultas->prodi()->orderBy('nama')->limit(25)->get();

        return view('admin.kampus.prodi.index', compact('kampus', 'fakultas', 'prodi'));
    }

    public function prodiData(Request $request, Kampus $kampus, Fakultas $fakultas): JsonResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $canManage = auth()->user()->hasMenuAccess('admin.kampus.index');

        $processor = new DataTablesProcessor(
            $request,
            $fakultas->prodi()->orderBy('nama'),
            [
                'searchable' => ['nama'],
                'orderable' => [2 => 'nama'],
            ],
            fn (Prodi $prodi, int $no) => [
                'checkbox' => $canManage ? '<input type="checkbox" class="row-check" value="'.$prodi->id.'">' : '',
                'no' => $no,
                'nama' => e($prodi->nama),
                'aksi' => $canManage ? view('admin.kampus.prodi._aksi', ['kampus' => $kampus, 'fakultas' => $fakultas, 'data' => $prodi])->render() : '',
            ]
        );

        return response()->json($processor->respond());
    }

    public function prodiCreate(Kampus $kampus, Fakultas $fakultas): View
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        return view('admin.kampus.prodi.buat', compact('kampus', 'fakultas'));
    }

    public function prodiStore(StoreProdiRequest $request, Kampus $kampus, Fakultas $fakultas): RedirectResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        foreach ($request->validated('nama') as $nama) {
            $fakultas->prodi()->create(['nama' => $nama]);
        }

        return redirect()->route('admin.kampus.prodi.index', [$kampus, $fakultas])
            ->with('success', 'Program studi berhasil ditambahkan');
    }

    public function prodiEdit(Kampus $kampus, Fakultas $fakultas, Prodi $prodi): View
    {
        abort_unless($fakultas->kampus_id === $kampus->id && $prodi->fakultas_id === $fakultas->id, 404);

        return view('admin.kampus.prodi.ubah', compact('kampus', 'fakultas', 'prodi'));
    }

    public function prodiUpdate(UpdateProdiRequest $request, Kampus $kampus, Fakultas $fakultas, Prodi $prodi): RedirectResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id && $prodi->fakultas_id === $fakultas->id, 404);

        $prodi->update($request->validated());

        return redirect()->route('admin.kampus.prodi.index', [$kampus, $fakultas])
            ->with('success', 'Program studi berhasil diperbarui');
    }

    public function prodiDestroy(Kampus $kampus, Fakultas $fakultas, Prodi $prodi): RedirectResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id && $prodi->fakultas_id === $fakultas->id, 404);

        $prodi->delete();

        return redirect()->route('admin.kampus.prodi.index', [$kampus, $fakultas])
            ->with('success', 'Program studi berhasil dihapus');
    }

    public function prodiMassDestroy(MassDeleteProdiRequest $request, Kampus $kampus, Fakultas $fakultas): RedirectResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $count = Prodi::where('fakultas_id', $fakultas->id)
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return redirect()->route('admin.kampus.prodi.index', [$kampus, $fakultas])
            ->with('success', "{$count} program studi berhasil dihapus");
    }
}
