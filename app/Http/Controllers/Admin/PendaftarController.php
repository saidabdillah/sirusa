<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Applicant\UpdateApplicantRequest;
use App\Models\Applicant;
use App\Notifications\ApplicantStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PendaftarController extends Controller
{
    public function index(): View
    {
        $applicants = Applicant::with(['user', 'beasiswa'])->latest()->paginate(10);

        return view('admin.pendaftar.index', compact('applicants'));
    }

    public function show(Applicant $applicant): View
    {
        $applicant->load(['user.profile', 'beasiswa']);

        return view('admin.pendaftar.lihat', compact('applicant'));
    }

    public function update(UpdateApplicantRequest $request, Applicant $applicant): RedirectResponse
    {
        $oldStatus = $applicant->status;
        $data = $request->safe()->only(['status', 'catatan']);

        $applicant->update($data);

        if (isset($data['status']) && $data['status'] === 'selesai' && ! $applicant->nomor_penetapan) {
            $applicant->forceFill([
                'nomor_penetapan' => sprintf('SK/%s/PEND/%04d', now()->format('Y'), $applicant->id),
                'tanggal_penetapan' => now()->toDateString(),
            ])->save();
        }

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
