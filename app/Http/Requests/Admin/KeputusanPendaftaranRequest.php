<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class KeputusanPendaftaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Nama input memakai awalan `pendaftaran_` karena halaman detail verifikasi
     * Kesra memuat dua form: verifikasi profil dan keputusan pendaftaran. Kalau
     * keduanya memakai `status`, satu error bag akan menandai form yang salah.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'pendaftaran_status' => ['required', 'in:diterima,ditolak,verifikasi'],
            'pendaftaran_catatan' => ['nullable', 'string', 'max:500', 'required_if:pendaftaran_status,ditolak'],
        ];
    }

    public function messages(): array
    {
        return [
            'pendaftaran_status.required' => 'Keputusan pendaftaran harus dipilih.',
            'pendaftaran_status.in' => 'Keputusan pendaftaran tidak valid.',
            'pendaftaran_catatan.required_if' => 'Alasan wajib diisi jika pendaftaran ditolak.',
            'pendaftaran_catatan.max' => 'Alasan maksimal 500 karakter.',
        ];
    }
}
