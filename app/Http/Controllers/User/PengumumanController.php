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
    public function show(Scholarship $scholarship): View
    {
        abort_unless($scholarship->hasPengumuman(), 404);

        $penerima = $scholarship->penerima()->get();

        return view('user.pengumuman.show', compact('scholarship', 'penerima'));
    }

    public function exportPdf(Request $request, Scholarship $scholarship): Response
    {
        abort_unless($request->user()->hasRole(['admin', 'super_admin']), 403);

        $penerima = $scholarship->penerima()->get();
        abort_unless($penerima->isNotEmpty(), 404);

        $pdf = Pdf::loadView('admin.exports.penerima_pdf', compact('scholarship', 'penerima'));

        return $pdf->download('daftar-penerima-beasiswa.pdf');
    }
}
