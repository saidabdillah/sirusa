<?php

namespace App\Http\Requests\Kampus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProdiRequest extends FormRequest
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
                Rule::unique('prodi')->ignore($this->route('prodi'))->where(function ($query) {
                    return $query->where('fakultas_id', $this->route('fakultas')->getKey());
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama program studi harus diisi',
            'nama.max' => 'Nama program studi maksimal 255 karakter',
            'nama.unique' => 'Program studi tersebut sudah terdaftar pada fakultas ini',
        ];
    }
}
