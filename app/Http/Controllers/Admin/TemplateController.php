<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $dir = public_path('storage/templates');
        $files = ['surat_permohonan.docx', 'surat_permohonan.doc', 'surat_permohonan.pdf'];

        $templateExists = false;
        foreach ($files as $file) {
            if (file_exists($dir.'/'.$file)) {
                $templateExists = true;
                break;
            }
        }

        return view('admin.template.index', compact('templateExists'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'template' => 'required|file|mimes:docx,doc,pdf|max:10240',
        ]);

        $dir = public_path('storage/templates');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Hapus file lama
        foreach (['surat_permohonan.docx', 'surat_permohonan.doc', 'surat_permohonan.pdf'] as $old) {
            $oldPath = $dir.'/'.$old;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $file = $request->file('template');
        $filename = 'surat_permohonan.'.$file->getClientOriginalExtension();
        $file->move($dir, $filename);

        return redirect()->route('admin.template.index')->with('success', 'Template surat permohonan berhasil diunggah');
    }

    public function destroy(): RedirectResponse
    {
        $dir = public_path('storage/templates');
        foreach (['surat_permohonan.docx', 'surat_permohonan.doc', 'surat_permohonan.pdf'] as $file) {
            $path = $dir.'/'.$file;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        return redirect()->route('admin.template.index')->with('success', 'Template surat permohonan berhasil dihapus');
    }
}
