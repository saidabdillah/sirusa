<?php

namespace App\Http\Requests\Applicant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:verifikasi,diterima,revisi,ditolak,selesai',
            'catatan' => 'nullable|string',
            'fakultas' => 'nullable|string|max:255',
            'prodi' => 'nullable|string|max:255',
            'ipk' => 'nullable|numeric|between:0,4',
            'semester' => 'nullable|integer|between:1,14',
            'dokumen_ktp' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_surat_permohonan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_transkrip' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_surat_aktif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_pas_foto' => 'nullable|file|mimes:jpg,jpeg,png|max:20480',
            'dokumen_prestasi.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_surat_pernyataan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_sktm' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_bukti_ukt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status tidak valid',
        ];
    }
}
