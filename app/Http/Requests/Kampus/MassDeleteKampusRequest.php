<?php

namespace App\Http\Requests\Kampus;

use Illuminate\Foundation\Http\FormRequest;

class MassDeleteKampusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:kampus,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Pilih minimal 1 kampus yang akan dihapus',
            'ids.array' => 'Data kampus terpilih tidak valid',
            'ids.min' => 'Pilih minimal 1 kampus yang akan dihapus',
            'ids.*.integer' => 'Data kampus terpilih tidak valid',
            'ids.*.distinct' => 'Data kampus terpilih tidak boleh duplikat',
            'ids.*.exists' => 'Kampus yang dipilih tidak valid',
        ];
    }
}
