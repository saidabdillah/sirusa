<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template' => 'required|file|mimes:docx,doc,pdf|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'template.required' => 'File template harus diunggah',
            'template.file' => 'Field template harus berupa file',
            'template.mimes' => 'Format file harus DOCX, DOC, atau PDF',
            'template.max' => 'Ukuran file maksimal 10MB',
        ];
    }
}
