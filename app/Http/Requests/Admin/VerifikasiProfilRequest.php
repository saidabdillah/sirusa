<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifikasiProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tahap verifikasi yang sedang dikerjakan, dibaca dari `defaults('stage', ...)`
     * di `routes/web.php`. Dipakai untuk aturan yang hanya berlaku di satu tahap.
     */
    private function stage(): ?string
    {
        $stage = $this->route('stage');

        return is_string($stage) ? $stage : null;
    }

    public function rules(): array
    {
        // Tanggal mulai berdomisili hanya ada di tahap Catpil, dan wajib persis
        // saat keputusannya "Setujui": itulah momen petugas menyatakan bahwa
        // mahasiswa berdomisili di wilayah persyaratan beasiswa. Pada keputusan
        // lain tanggalnya tidak relevan, jadi tidak diwajibkan.
        $tanggalWajib = $this->stage() === 'catpil' && $this->input('status') === 'setuju';

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

            // Tidak boleh di masa depan: domisili dihitung dari tanggal yang
            // sudah terjadi. `before_or_equal:today` dipakai (bukan angka tetap)
            // supaya aturannya tidak perlu diubah tiap tahun.
            'berdomisili_sejak' => [
                Rule::requiredIf($tanggalWajib),
                'nullable',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status verifikasi harus dipilih.',
            'status.in' => 'Status verifikasi tidak valid.',
            'catatan.required_if' => 'Catatan wajib diisi ketika memilih "Minta Perbaikan".',
            'catatan.max' => 'Catatan maksimal 255 karakter.',
            'berdomisili_sejak.required' => 'Tanggal berdomisili harus diisi ketika memilih "Setujui".',
            'berdomisili_sejak.date' => 'Tanggal berdomisili tidak valid.',
            'berdomisili_sejak.before_or_equal' => 'Tanggal berdomisili tidak boleh di masa depan.',
        ];
    }
}
