<?php

namespace App\Http\Requests\Kampus;

use App\Models\Fakultas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFakultasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'array', 'min:1'],
            'nama.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $kampusId = $this->route('kampus')->getKey();
                $namaList = $this->input('nama');

                if (! is_array($namaList)) {
                    return;
                }

                $normalized = collect($namaList)->map(fn ($nama) => trim((string) $nama));

                if ($normalized->count() !== $normalized->unique()->count()) {
                    $validator->errors()->add('nama', 'Terdapat nama fakultas yang sama dalam daftar.');
                }

                foreach ($namaList as $nama) {
                    $exists = Fakultas::where('kampus_id', $kampusId)->where('nama', $nama)->exists();

                    if ($exists) {
                        $validator->errors()->add('nama', "Fakultas \"{$nama}\" sudah terdaftar pada kampus ini.");
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Minimal harus mengisi 1 nama fakultas',
            'nama.array' => 'Nama fakultas tidak valid',
            'nama.min' => 'Minimal harus mengisi 1 nama fakultas',
            'nama.*.required' => 'Nama fakultas harus diisi',
            'nama.*.string' => 'Nama fakultas harus berupa teks',
            'nama.*.max' => 'Nama fakultas maksimal 255 karakter',
        ];
    }
}
