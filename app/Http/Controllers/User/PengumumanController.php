<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    public function show(Request $request, Scholarship $scholarship): View
    {
        abort_unless($scholarship->hasPengumuman(), 404);

        $user = $request->user();
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isApplicant = $scholarship->pendaftar()->where('user_id', $user->id)->exists();

        abort_unless($isAdmin || $isApplicant, 404);

        $penerima = $scholarship->penerima()->get();

        return view('user.pengumuman.show', compact('scholarship', 'penerima'));
    }

    public function exportPdf(Request $request, Scholarship $scholarship): Response
    {
        $isAdmin = $request->user()->hasRole(['admin', 'super_admin']);
        $isApplicant = $scholarship->pendaftar()->where('user_id', $request->user()->id)->exists();

        abort_unless($isAdmin || ($scholarship->hasPengumuman() && $isApplicant), 404);

        $penerima = $scholarship->penerima()->get();
        abort_unless($penerima->isNotEmpty(), 404);

        $pdf = Pdf::loadView('admin.exports.penerima_pdf', compact('scholarship', 'penerima'));

        return $pdf->setPaper('a4', 'portrait')->stream('daftar-penerima-beasiswa.pdf');
    }
}
