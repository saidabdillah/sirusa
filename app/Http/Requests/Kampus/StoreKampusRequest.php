<?php

namespace App\Http\Requests\Kampus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKampusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'nama_kampus' => ['required', 'string', 'max:255', Rule::unique('kampus', 'nama_kampus')],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kampus.required' => 'Nama kampus harus diisi',
            'nama_kampus.max' => 'Nama kampus maksimal 255 karakter',
            'nama_kampus.unique' => 'Kampus tersebut sudah terdaftar',
        ];
    }
}
