<?php

namespace App\Http\Requests\Kampus;

use App\Http\Requests\Concerns\TrimsNameInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKampusRequest extends FormRequest
{
    use TrimsNameInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_kampus' => $this->trimSederhana($this->input('nama_kampus')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_kampus' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kampus', 'nama_kampus')->ignore($this->route('kampus')),
            ],
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
