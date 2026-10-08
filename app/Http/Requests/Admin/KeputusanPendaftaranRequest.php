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
     * masih bisa memuat form verifikasi profil (Catpil, Kampus) di sebelah
     * keputusan pendaftaran. Kalau keduanya memakai `status`, satu error bag
     * akan menandai form yang salah.
     *
     * Hanya `diterima` dan `ditolak` yang valid. `verifikasi` dulu berarti
     * "tarik kembali", dan dihapus supaya keputusan Kesra tidak bisa
     * dikosongkan tanpa sengaja. Mengubah keputusan berarti memilih ulang
     * `diterima` atau `ditolak` lewat form yang sama.
     *
     * Catatan sengaja TIDAK dikumpulkan dari tahap Kesra (permintaan pengguna:
     * "di verifikasi kesra tidak perlu ada catatan"), jadi tidak ada aturan
     * `pendaftaran_catatan` lagi di sini.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'pendaftaran_status' => ['required', 'in:diterima,ditolak'],
        ];
    }

    public function messages(): array
    {
        return [
            'pendaftaran_status.required' => 'Keputusan pendaftaran harus dipilih.',
            'pendaftaran_status.in' => 'Keputusan pendaftaran tidak valid.',
        ];
    }
}
