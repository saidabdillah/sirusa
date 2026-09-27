<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
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
    use RespondsToAjax;

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

    public function store(StoreKampusRequest $request): RedirectResponse|JsonResponse
    {
        foreach ($request->validated('nama_kampus') as $nama) {
            Kampus::create(['nama_kampus' => $nama]);
        }

        return $this->ajaxOk($request, 'Kampus berhasil ditambahkan', route('admin.kampus.index'));
    }

    public function edit(Kampus $kampus): View
    {
        return view('admin.kampus.ubah', compact('kampus'));
    }

    public function update(UpdateKampusRequest $request, Kampus $kampus): RedirectResponse|JsonResponse
    {
        $kampus->update($request->validated());

        return $this->ajaxOk($request, 'Kampus berhasil diperbarui', route('admin.kampus.index'));
    }

    public function destroy(Request $request, Kampus $kampus): RedirectResponse|JsonResponse
    {
        $kampus->delete();

        return $this->ajaxOk($request, 'Kampus berhasil dihapus', route('admin.kampus.index'));
    }

    public function massDestroy(MassDeleteKampusRequest $request): RedirectResponse|JsonResponse
    {
        $count = Kampus::whereIn('id', $request->validated('ids'))->delete();

        return $this->ajaxOk($request, "{$count} kampus berhasil dihapus", route('admin.kampus.index'));
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

    public function fakultasStore(StoreFakultasRequest $request, Kampus $kampus): RedirectResponse|JsonResponse
    {
        foreach ($request->validated('nama') as $nama) {
            $kampus->fakultas()->create(['nama' => $nama]);
        }

        return $this->ajaxOk($request, 'Fakultas berhasil ditambahkan', route('admin.kampus.fakultas.index', $kampus));
    }

    public function fakultasEdit(Kampus $kampus, Fakultas $fakultas): View
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        return view('admin.kampus.fakultas.ubah', compact('kampus', 'fakultas'));
    }

    public function fakultasUpdate(UpdateFakultasRequest $request, Kampus $kampus, Fakultas $fakultas): RedirectResponse|JsonResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $fakultas->update($request->validated());

        return $this->ajaxOk($request, 'Fakultas berhasil diperbarui', route('admin.kampus.fakultas.index', $kampus));
    }

    public function fakultasDestroy(Request $request, Kampus $kampus, Fakultas $fakultas): RedirectResponse|JsonResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $fakultas->delete();

        return $this->ajaxOk($request, 'Fakultas berhasil dihapus', route('admin.kampus.fakultas.index', $kampus));
    }

    public function fakultasMassDestroy(MassDeleteFakultasRequest $request, Kampus $kampus): RedirectResponse|JsonResponse
    {
        $count = Fakultas::where('kampus_id', $kampus->id)
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->ajaxOk($request, "{$count} fakultas berhasil dihapus", route('admin.kampus.fakultas.index', $kampus));
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

    public function prodiStore(StoreProdiRequest $request, Kampus $kampus, Fakultas $fakultas): RedirectResponse|JsonResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        foreach ($request->validated('nama') as $nama) {
            $fakultas->prodi()->create(['nama' => $nama]);
        }

        return $this->ajaxOk($request, 'Program studi berhasil ditambahkan', route('admin.kampus.prodi.index', [$kampus, $fakultas]));
    }

    public function prodiEdit(Kampus $kampus, Fakultas $fakultas, Prodi $prodi): View
    {
        abort_unless($fakultas->kampus_id === $kampus->id && $prodi->fakultas_id === $fakultas->id, 404);

        return view('admin.kampus.prodi.ubah', compact('kampus', 'fakultas', 'prodi'));
    }

    public function prodiUpdate(UpdateProdiRequest $request, Kampus $kampus, Fakultas $fakultas, Prodi $prodi): RedirectResponse|JsonResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id && $prodi->fakultas_id === $fakultas->id, 404);

        $prodi->update($request->validated());

        return $this->ajaxOk($request, 'Program studi berhasil diperbarui', route('admin.kampus.prodi.index', [$kampus, $fakultas]));
    }

    public function prodiDestroy(Request $request, Kampus $kampus, Fakultas $fakultas, Prodi $prodi): RedirectResponse|JsonResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id && $prodi->fakultas_id === $fakultas->id, 404);

        $prodi->delete();

        return $this->ajaxOk($request, 'Program studi berhasil dihapus', route('admin.kampus.prodi.index', [$kampus, $fakultas]));
    }

    public function prodiMassDestroy(MassDeleteProdiRequest $request, Kampus $kampus, Fakultas $fakultas): RedirectResponse|JsonResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $count = Prodi::where('fakultas_id', $fakultas->id)
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return $this->ajaxOk($request, "{$count} program studi berhasil dihapus", route('admin.kampus.prodi.index', [$kampus, $fakultas]));
    }
}
