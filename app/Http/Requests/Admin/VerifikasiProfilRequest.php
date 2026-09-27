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

            // "Minta Perbaikan" tanpa catatan tidak ada artinya bagi mahasiswa:
            // dia tidak pernah diberi tahu bagian mana yang salah. Label di
            // view sudah menjanjikan "(wajib jika minta perbaikan)", jadi
            // aturan yang sama harus ditegakkan di server -- bukan hanya lewat
            // atribut `required` di browser.
            //
            // Pola yang sama sudah dipakai `KeputusanPendaftaranRequest` untuk
            // `pendaftaran_catatan` saat pendaftaran ditolak.
            'catatan' => ['nullable', 'string', 'max:255', 'required_if:status,revisi'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status verifikasi harus dipilih.',
            'status.in' => 'Status verifikasi tidak valid.',
            'catatan.required_if' => 'Catatan wajib diisi ketika memilih "Minta Perbaikan".',
            'catatan.max' => 'Catatan maksimal 255 karakter.',
        ];
    }
}
