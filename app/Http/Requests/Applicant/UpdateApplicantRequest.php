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
            'status' => 'sometimes|in:verifikasi,diterima,revisi,ditolak',
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

            if ($current === 'ditolak') {
                $validator->errors()->add('status', 'Status sudah final (Ditolak) dan tidak dapat diubah lagi.');

                return;
            }

            if ($target === 'verifikasi') {
                return;
            }

            if ($target === 'diterima') {
                if (! in_array($current, ['verifikasi', 'revisi'], true)) {
                    $validator->errors()->add('status', 'Status Diterima hanya dapat diatur dari Verifikasi atau Revisi.');
                }

                $beasiswa = $applicant->beasiswa;
                if ($beasiswa && (int) $beasiswa->kuota > 0) {
                    $diterima = $beasiswa->pendaftar()
                        ->where('status', 'diterima')
                        ->where('id', '!=', $applicant->id)
                        ->count();

                    if ($diterima >= (int) $beasiswa->kuota) {
                        $validator->errors()->add(
                            'status',
                            "Kuota beasiswa ini ({$beasiswa->kuota}) sudah penuh. Pendaftar tidak dapat diterima."
                        );
                    }
                }

                return;
            }

            if (in_array($target, ['revisi', 'ditolak'], true)) {
                return;
            }
        });
    }
}
