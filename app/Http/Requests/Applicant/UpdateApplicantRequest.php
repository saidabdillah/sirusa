<?php

namespace App\Http\Requests\Applicant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'in:verifikasi,diterima,revisi,ditolak',
            ],
            'catatan' => [
                'nullable',
                'string',
            ],
            'fakultas' => [
                'nullable',
                'string',
                'max:255',
            ],
            'prodi' => [
                'nullable',
                'string',
                'max:255',
            ],
            'ipk' => [
                'nullable',
                'numeric',
                'between:0,4',
            ],
            'semester' => [
                'nullable',
                'integer',
                'between:1,14',
            ],
            'dokumen_ktp' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_kk' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_akta' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_surat_permohonan' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_transkrip' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_surat_aktif' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_pas_foto' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_prestasi.*' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_surat_pernyataan' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_sktm' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_bukti_ukt' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status tidak valid.',
            'ipk.numeric' => 'IPK harus berupa angka.',
            'ipk.between' => 'IPK harus berada antara 0 sampai 4.',
            'semester.integer' => 'Semester harus berupa angka.',
            'semester.between' => 'Semester harus berada antara 1 sampai 14.',
            'dokumen_ktp.mimes' => 'Format KTP harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_ktp.max' => 'Ukuran KTP maksimal 2MB.',
            'dokumen_kk.mimes' => 'Format KK harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_kk.max' => 'Ukuran KK maksimal 2MB.',
            'dokumen_akta.mimes' => 'Format akta kelahiran harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_akta.max' => 'Ukuran akta kelahiran maksimal 2MB.',
            'dokumen_surat_permohonan.mimes' => 'Format surat permohonan harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_surat_permohonan.max' => 'Ukuran surat permohonan maksimal 2MB.',
            'dokumen_transkrip.mimes' => 'Format transkrip harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_transkrip.max' => 'Ukuran transkrip maksimal 2MB.',
            'dokumen_surat_aktif.mimes' => 'Format surat aktif kuliah harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_surat_aktif.max' => 'Ukuran surat aktif kuliah maksimal 2MB.',
            'dokumen_pas_foto.mimes' => 'Format pas foto harus JPG, JPEG, atau PNG.',
            'dokumen_pas_foto.max' => 'Ukuran pas foto maksimal 2MB.',
            'dokumen_prestasi.*.mimes' => 'Format dokumen prestasi harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_prestasi.*.max' => 'Ukuran setiap dokumen prestasi maksimal 2MB.',
            'dokumen_surat_pernyataan.mimes' => 'Format surat pernyataan harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_surat_pernyataan.max' => 'Ukuran surat pernyataan maksimal 2MB.',
            'dokumen_sktm.mimes' => 'Format SKTM harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_sktm.max' => 'Ukuran SKTM maksimal 2MB.',
            'dokumen_bukti_ukt.mimes' => 'Format bukti UKT/SPP harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_bukti_ukt.max' => 'Ukuran bukti UKT/SPP maksimal 2MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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
                $validator->errors()->add(
                    'status',
                    'Status sudah final (Ditolak) dan tidak dapat diubah lagi.'
                );

                return;
            }

            if ($target === 'verifikasi') {
                return;
            }

            if ($target === 'diterima') {
                if (! in_array($current, ['verifikasi', 'revisi'], true)) {
                    $validator->errors()->add(
                        'status',
                        'Status Diterima hanya dapat diatur dari Verifikasi atau Revisi.'
                    );

                    return;
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

            if ($target === 'revisi') {
                return;
            }

            if ($target === 'ditolak') {
                return;
            }
        });
    }
}
