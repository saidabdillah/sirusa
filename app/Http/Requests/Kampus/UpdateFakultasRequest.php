<?php

namespace App\Http\Requests\Kampus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFakultasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fakultas')->ignore($this->route('fakultas'))->where(function ($query) {
                    return $query->where('kampus_id', $this->route('kampus')->getKey());
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama fakultas harus diisi',
            'nama.max' => 'Nama fakultas maksimal 255 karakter',
            'nama.unique' => 'Fakultas tersebut sudah terdaftar pada kampus ini',
        ];
    }
}
