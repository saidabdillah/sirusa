<?php

namespace App\Http\Requests\Applicant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:verifikasi,diterima,verifikasi_akhir,revisi,ditolak,selesai',
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $applicant = $this->route('applicant');

            if (! $applicant || ! $this->has('status')) {
                return;
            }

            $target = $this->input('status');
            $current = $applicant->status;

            if ($target === $current) {
                return;
            }

            if (in_array($current, ['selesai', 'ditolak'], true)) {
                $validator->errors()->add('status', 'Status sudah final dan tidak dapat diubah lagi.');

                return;
            }

            if ($target === 'verifikasi') {
                return;
            }

            if ($target === 'diterima') {
                if (! in_array($current, ['verifikasi', 'revisi'], true)) {
                    $validator->errors()->add('status', 'Status Diterima hanya dapat diatur dari Verifikasi atau Revisi.');
                }

                return;
            }

            if ($target === 'verifikasi_akhir') {
                if ($current !== 'diterima') {
                    $validator->errors()->add('status', 'Verifikasi Akhir hanya dapat diatur dari status Diterima.');
                } elseif (! $applicant->isBerkasTahap2Lengkap()) {
                    $validator->errors()->add('status', 'Berkas Tahap 2 (surat pernyataan, SKTM, bukti UKT) harus lengkap terlebih dahulu.');
                }

                return;
            }

            if ($target === 'selesai') {
                if ($current !== 'verifikasi_akhir') {
                    $validator->errors()->add('status', 'Pendaftar harus melewati Verifikasi Akhir sebelum Selesai.');
                } elseif (! $applicant->isBerkasTahap2Lengkap()) {
                    $validator->errors()->add('status', 'Berkas Tahap 2 (surat pernyataan, SKTM, bukti UKT) harus lengkap terlebih dahulu.');
                }

                return;
            }

            if (in_array($target, ['revisi', 'ditolak'], true)) {
                return;
            }
        });
    }
}
