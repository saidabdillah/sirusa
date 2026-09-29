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
     * masih bisa memuat form verifikasi profil (Capil, Kampus) di sebelah
     * keputusan pendaftaran. Kalau keduanya memakai `status`, satu error bag
     * akan menandai form yang salah.
     *
     * Hanya `diterima` dan `ditolak` yang valid. `verifikasi` dulu berarti
     * "tarik kembali", dan dihapus supaya keputusan Kesra tidak bisa
     * dikosongkan tanpa sengaja. Mengubah keputusan berarti memilih ulang
     * `diterima` atau `ditolak` lewat form yang sama.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'pendaftaran_status' => ['required', 'in:diterima,ditolak'],
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
