<?php

namespace App\Http\Requests\Kampus;

use Illuminate\Foundation\Http\FormRequest;

class MassDeleteProdiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:prodi,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Pilih minimal 1 program studi yang akan dihapus',
            'ids.array' => 'Data program studi terpilih tidak valid',
            'ids.min' => 'Pilih minimal 1 program studi yang akan dihapus',
            'ids.*.integer' => 'Data program studi terpilih tidak valid',
            'ids.*.distinct' => 'Data program studi terpilih tidak boleh duplikat',
            'ids.*.exists' => 'Program studi yang dipilih tidak valid',
        ];
    }
}
