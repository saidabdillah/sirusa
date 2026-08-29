<?php

namespace App\Http\Requests\Applicant;

use App\Models\Scholarship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'beasiswa_id' => 'required|exists:beasiswa,id',
            'fakultas' => 'required|string|max:255',
            'prodi' => 'required|string|max:255',
            'ipk' => 'required|numeric|between:0,4',
            'semester' => 'required|integer|between:1,14',
            'dokumen_ktp' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_kk' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_surat_permohonan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_transkrip' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_surat_aktif' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_pas_foto' => 'required|file|mimes:jpg,jpeg,png|max:20480',
            'dokumen_surat_pernyataan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_sktm' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_bukti_ukt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'dokumen_prestasi.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ];
    }

    public function messages(): array
    {
        return [
            'beasiswa_id.required' => 'Beasiswa harus dipilih',
            'beasiswa_id.exists' => 'Beasiswa tidak ditemukan',
            'fakultas.required' => 'Fakultas harus diisi',
            'prodi.required' => 'Program Studi harus diisi',
            'ipk.required' => 'IPK harus diisi',
            'ipk.numeric' => 'IPK harus berupa angka',
            'ipk.between' => 'IPK harus antara 0 hingga 4',
            'semester.required' => 'Semester harus diisi',
            'semester.integer' => 'Semester harus berupa angka',
            'semester.between' => 'Semester harus antara 1 hingga 14',
            'dokumen_ktp.required' => 'Dokumen KTP harus diupload',
            'dokumen_ktp.mimes' => 'Format KTP harus pdf, jpg, jpeg, atau png',
            'dokumen_ktp.max' => 'Ukuran KTP maksimal 20MB',
            'dokumen_kk.required' => 'Dokumen KK harus diupload',
            'dokumen_kk.mimes' => 'Format KK harus pdf, jpg, jpeg, atau png',
            'dokumen_kk.max' => 'Ukuran KK maksimal 20MB',
            'dokumen_surat_permohonan.required' => 'Surat permohonan harus diupload',
            'dokumen_surat_permohonan.mimes' => 'Format surat permohonan harus pdf, jpg, jpeg, atau png',
            'dokumen_surat_permohonan.max' => 'Ukuran surat permohonan maksimal 20MB',
            'dokumen_transkrip.required' => 'Transkrip nilai harus diupload',
            'dokumen_transkrip.mimes' => 'Format transkrip harus pdf, jpg, jpeg, atau png',
            'dokumen_transkrip.max' => 'Ukuran transkrip maksimal 20MB',
            'dokumen_surat_aktif.required' => 'Surat aktif kuliah harus diupload',
            'dokumen_surat_aktif.mimes' => 'Format surat aktif harus pdf, jpg, jpeg, atau png',
            'dokumen_surat_aktif.max' => 'Ukuran surat aktif maksimal 20MB',
            'dokumen_pas_foto.required' => 'Pas foto harus diupload',
            'dokumen_pas_foto.mimes' => 'Format pas foto harus jpg, jpeg, atau png',
            'dokumen_pas_foto.max' => 'Ukuran pas foto maksimal 20MB',
            'dokumen_surat_pernyataan.required' => 'Surat pernyataan harus diupload',
            'dokumen_surat_pernyataan.mimes' => 'Format surat pernyataan harus pdf, jpg, jpeg, atau png',
            'dokumen_surat_pernyataan.max' => 'Ukuran surat pernyataan maksimal 20MB',
            'dokumen_sktm.required' => 'SKTM harus diupload',
            'dokumen_sktm.mimes' => 'Format SKTM harus pdf, jpg, jpeg, atau png',
            'dokumen_sktm.max' => 'Ukuran SKTM maksimal 20MB',
            'dokumen_bukti_ukt.required' => 'Bukti UKT/SPP harus diupload',
            'dokumen_bukti_ukt.mimes' => 'Format bukti UKT/SPP harus pdf, jpg, jpeg, atau png',
            'dokumen_bukti_ukt.max' => 'Ukuran bukti UKT/SPP maksimal 20MB',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $scholarship = Scholarship::find($this->input('beasiswa_id'));

            if (! $scholarship) {
                return;
            }

            if ($scholarship->isExpired()) {
                $validator->errors()->add('beasiswa_id', 'Pendaftaran beasiswa sudah ditutup.');

                return;
            }

            if ((float) $scholarship->ipk_minimal > 0) {
                $ipk = (float) $this->input('ipk');

                if ($ipk < (float) $scholarship->ipk_minimal) {
                    $validator->errors()->add(
                        'ipk',
                        "IPK minimal untuk beasiswa ini adalah {$scholarship->ipk_minimal}. IPK Anda belum memenuhi syarat."
                    );
                }
            }

            if ((int) $scholarship->semester_minimal > 0) {
                $semester = (int) $this->input('semester');

                if ($semester < (int) $scholarship->semester_minimal) {
                    $validator->errors()->add(
                        'semester',
                        "Semester minimal untuk beasiswa ini adalah {$scholarship->semester_minimal}. Semester Anda belum memenuhi syarat."
                    );
                }
            }
        });
    }
}
