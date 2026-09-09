<?php

namespace App\Http\Requests\Kampus;

use App\Models\Kampus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreKampusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'nama_kampus' => ['required', 'array', 'min:1'],
            'nama_kampus.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $namaList = $this->input('nama_kampus');

                if (! is_array($namaList)) {
                    return;
                }

                $normalized = collect($namaList)->map(fn ($nama) => trim((string) $nama));

                if ($normalized->count() !== $normalized->unique()->count()) {
                    $validator->errors()->add('nama_kampus', 'Terdapat nama kampus yang sama dalam daftar.');
                }

                foreach ($namaList as $nama) {
                    if (Kampus::where('nama_kampus', $nama)->exists()) {
                        $validator->errors()->add('nama_kampus', "Kampus \"{$nama}\" sudah terdaftar.");
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kampus.required' => 'Minimal harus mengisi 1 nama kampus',
            'nama_kampus.array' => 'Nama kampus tidak valid',
            'nama_kampus.min' => 'Minimal harus mengisi 1 nama kampus',
            'nama_kampus.*.required' => 'Nama kampus harus diisi',
            'nama_kampus.*.string' => 'Nama kampus harus berupa teks',
            'nama_kampus.*.max' => 'Nama kampus maksimal 255 karakter',
        ];
    }
}
