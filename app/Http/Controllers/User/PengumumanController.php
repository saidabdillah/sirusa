<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PengumumanController extends Controller
{
    public function show(Scholarship $scholarship): View
    {
        if (! $scholarship->isPengumumanAktif()) {
            throw new NotFoundHttpException('Pengumuman tidak tersedia atau belum dimulai.');
        }

        $penerima = $scholarship->pendaftar()
            ->where('status', 'diterima')
            ->where('hasil_pengumuman', 'diterima')
            ->with('user.profile')
            ->latest()
            ->get();

        return view('user.pengumuman.show', compact('scholarship', 'penerima'));
    }
}