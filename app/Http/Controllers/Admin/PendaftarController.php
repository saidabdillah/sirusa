<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Applicant\UpdateApplicantRequest;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Notifications\ApplicantStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendaftarController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'status' => 'nullable|in:verifikasi,diterima,revisi,ditolak',
            'beasiswa_id' => 'nullable|integer|exists:beasiswa,id',
        ]);

        $applicants = Applicant::with(['user', 'beasiswa'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('beasiswa_id'), function ($query) use ($request) {
                $query->where('beasiswa_id', $request->integer('beasiswa_id'));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $beasiswas = Scholarship::orderBy('nama')->get(['id', 'nama']);

        return view('admin.pendaftar.index', compact('applicants', 'beasiswas'));
    }

    public function show(Applicant $applicant): View
    {
        $applicant->load(['user.profile', 'beasiswa']);

        return view('admin.pendaftar.lihat', compact('applicant'));
    }

    public function update(UpdateApplicantRequest $request, Applicant $applicant): RedirectResponse
    {
        $oldStatus = $applicant->status;
        $data = $request->safe()->only(['status', 'hasil_pengumuman', 'catatan']);

        $applicant->update($data);

        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $applicant->user->notify(new ApplicantStatusChanged($applicant, $applicant->getStatusLabelAttribute()));
        }

        return redirect()->route('admin.pendaftar.lihat', $applicant)->with('success', 'Status pendaftar berhasil diperbarui');
    }

    public function destroy(Applicant $applicant): RedirectResponse
    {
        $applicant->delete();

        return redirect()->route('admin.pendaftar.index')->with('success', 'Data pendaftar berhasil dihapus');
    }
}
