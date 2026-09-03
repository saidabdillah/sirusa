<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scholarship\UpdatePengumumanRequest;
use App\Models\Scholarship;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PengumumanJadwalController extends Controller
{
    public function index(): View
    {
        $scholarships = Scholarship::withCount(['pendaftar as pendaftar_count', 'pendaftar as penerima_count' => function ($query) {
            $query->where('status', 'diterima');
        }])
            ->latest()
            ->paginate(10);

        return view('admin.pengumuman.index', compact('scholarships'));
    }

    public function edit(Scholarship $scholarship): View
    {
        return view('admin.pengumuman.ubah', compact('scholarship'));
    }

    public function update(UpdatePengumumanRequest $request, Scholarship $scholarship): RedirectResponse
    {
        $mulai = $request->validated('tanggal_pengumuman');
        $selesai = $request->validated('tanggal_pengumuman_selesai');

        $scholarship->update([
            'tanggal_pengumuman' => $mulai ?: null,
            'tanggal_pengumuman_selesai' => $selesai ?: null,
            'pengumuman_notified_at' => null,
        ]);

        return redirect()->route('admin.pengumuman.index')->with('success', 'Jadwal pengumuman berhasil diperbarui');
    }

    public function destroy(Scholarship $scholarship): RedirectResponse
    {
        $scholarship->update([
            'tanggal_pengumuman' => null,
            'tanggal_pengumuman_selesai' => null,
            'pengumuman_notified_at' => null,
        ]);

        return redirect()->route('admin.pengumuman.index')->with('success', 'Jadwal pengumuman berhasil dihapus');
    }
}
