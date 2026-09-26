<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VerifikasiProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:menunggu,setuju,revisi,tolak'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status verifikasi harus dipilih.',
            'status.in' => 'Status verifikasi tidak valid.',
            'catatan.max' => 'Catatan maksimal 255 karakter.',
        ];
    }
}
