<?php

namespace App\Http\Requests\Kampus;

use Illuminate\Foundation\Http\FormRequest;

class MassDeleteFakultasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:fakultas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Pilih minimal 1 fakultas yang akan dihapus',
            'ids.array' => 'Data fakultas terpilih tidak valid',
            'ids.min' => 'Pilih minimal 1 fakultas yang akan dihapus',
            'ids.*.integer' => 'Data fakultas terpilih tidak valid',
            'ids.*.distinct' => 'Data fakultas terpilih tidak boleh duplikat',
            'ids.*.exists' => 'Fakultas yang dipilih tidak valid',
        ];
    }
}
