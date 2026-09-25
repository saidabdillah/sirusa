<?php

namespace App\Http\Requests\Kampus;

use App\Models\Prodi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProdiRequest extends FormRequest
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
                $fakultasId = $this->route('fakultas')->getKey();
                $namaList = $this->input('nama');

                if (! is_array($namaList)) {
                    return;
                }

                $normalized = collect($namaList)->map(fn ($nama) => trim((string) $nama));

                if ($normalized->count() !== $normalized->unique()->count()) {
                    $validator->errors()->add('nama', 'Terdapat nama program studi yang sama dalam daftar.');
                }

                foreach ($namaList as $nama) {
                    $exists = Prodi::where('fakultas_id', $fakultasId)->where('nama', $nama)->exists();

                    if ($exists) {
                        $validator->errors()->add('nama', "Program studi \"{$nama}\" sudah terdaftar pada fakultas ini.");
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Minimal harus mengisi 1 nama program studi',
            'nama.array' => 'Nama program studi tidak valid',
            'nama.min' => 'Minimal harus mengisi 1 nama program studi',
            'nama.*.required' => 'Nama program studi harus diisi',
            'nama.*.string' => 'Nama program studi harus berupa teks',
            'nama.*.max' => 'Nama program studi maksimal 255 karakter',
        ];
    }
}
