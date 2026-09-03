<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTemplateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TemplateController extends Controller
{
    private const TEMPLATE_EXTENSIONS = ['docx', 'doc', 'pdf'];

    public function index(): View
    {
        $templateExists = false;
        foreach (self::TEMPLATE_EXTENSIONS as $ext) {
            if (Storage::disk('local')->exists('templates/surat_permohonan.'.$ext)) {
                $templateExists = true;
                break;
            }
        }

        return view('admin.template.index', compact('templateExists'));
    }

    public function update(UpdateTemplateRequest $request): RedirectResponse
    {
        $disk = Storage::disk('local');

        foreach (self::TEMPLATE_EXTENSIONS as $ext) {
            $path = 'templates/surat_permohonan.'.$ext;
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $file = $request->file('template');
        $filename = 'surat_permohonan.'.$file->getClientOriginalExtension();
        $disk->putFileAs('templates', $file, $filename);

        return redirect()->route('admin.template.index')->with('success', 'Template surat permohonan berhasil diunggah');
    }

    public function destroy(): RedirectResponse
    {
        $disk = Storage::disk('local');

        foreach (self::TEMPLATE_EXTENSIONS as $ext) {
            $path = 'templates/surat_permohonan.'.$ext;
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        return redirect()->route('admin.template.index')->with('success', 'Template surat permohonan berhasil dihapus');
    }
}
