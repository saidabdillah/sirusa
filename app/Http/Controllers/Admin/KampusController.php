<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kampus\StoreFakultasRequest;
use App\Http\Requests\Kampus\StoreKampusRequest;
use App\Http\Requests\Kampus\StoreProdiRequest;
use App\Http\Requests\Kampus\UpdateFakultasRequest;
use App\Http\Requests\Kampus\UpdateKampusRequest;
use App\Http\Requests\Kampus\UpdateProdiRequest;
use App\Models\Fakultas;
use App\Models\Kampus;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KampusController extends Controller
{
    // ─── Kampus ────────────────────────────────────────────────────

    public function index(): View
    {
        $kampus = Kampus::withCount('fakultas')->orderBy('nama_kampus')->get();

        return view('admin.kampus.index', compact('kampus'));
    }

    public function create(): View
    {
        return view('admin.kampus.buat');
    }

    public function store(StoreKampusRequest $request): RedirectResponse
    {
        Kampus::create($request->validated());

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

    // ─── Fakultas ──────────────────────────────────────────────────

    public function fakultasIndex(Kampus $kampus): View
    {
        $fakultas = $kampus->fakultas()->withCount('prodi')->orderBy('nama')->get();

        return view('admin.kampus.fakultas.index', compact('kampus', 'fakultas'));
    }

    public function fakultasCreate(Kampus $kampus): View
    {
        return view('admin.kampus.fakultas.buat', compact('kampus'));
    }

    public function fakultasStore(StoreFakultasRequest $request, Kampus $kampus): RedirectResponse
    {
        $kampus->fakultas()->create($request->validated());

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

    // ─── Prodi ─────────────────────────────────────────────────────

    public function prodiIndex(Kampus $kampus, Fakultas $fakultas): View
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $prodi = $fakultas->prodi()->orderBy('nama')->get();

        return view('admin.kampus.prodi.index', compact('kampus', 'fakultas', 'prodi'));
    }

    public function prodiCreate(Kampus $kampus, Fakultas $fakultas): View
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        return view('admin.kampus.prodi.buat', compact('kampus', 'fakultas'));
    }

    public function prodiStore(StoreProdiRequest $request, Kampus $kampus, Fakultas $fakultas): RedirectResponse
    {
        abort_unless($fakultas->kampus_id === $kampus->id, 404);

        $fakultas->prodi()->create($request->validated());

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
}
