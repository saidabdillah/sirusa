<?php

namespace App\Http\Requests\Applicant;

use App\Models\Applicant;
use App\Models\Scholarship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'beasiswa_id' => [
                'required',
                'exists:beasiswa,id',
            ],
            'dokumen_ktp' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_kk' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_akta' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_surat_permohonan' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_transkrip' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_surat_aktif' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_pas_foto' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_surat_pernyataan' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_sktm' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_bukti_ukt' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'dokumen_prestasi.*' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'status_orang_tua' => [
                'required',
                'in:Lengkap,Yatim,Piatu,Yatim Piatu',
            ],
            'ktp_ayah' => [
                'required_if:status_orang_tua,Lengkap,Piatu',
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'ktp_ibu' => [
                'required_if:status_orang_tua,Lengkap,Yatim',
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'ktp_wali' => [
                'required_if:status_orang_tua,Yatim Piatu',
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'kk_wali' => [
                'required_if:status_orang_tua,Yatim Piatu',
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
            'beasiswa_id.required' => 'Beasiswa harus dipilih.',
            'beasiswa_id.exists' => 'Beasiswa tidak ditemukan.',
            'dokumen_ktp.required' => 'Dokumen KTP harus diupload.',
            'dokumen_ktp.file' => 'Dokumen KTP harus berupa file.',
            'dokumen_ktp.mimes' => 'Format KTP harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_ktp.max' => 'Ukuran KTP maksimal 2MB.',
            'dokumen_kk.required' => 'Dokumen KK harus diupload.',
            'dokumen_kk.file' => 'Dokumen KK harus berupa file.',
            'dokumen_kk.mimes' => 'Format KK harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_kk.max' => 'Ukuran KK maksimal 2MB.',
            'dokumen_akta.required' => 'Akta kelahiran harus diupload.',
            'dokumen_akta.file' => 'Akta kelahiran harus berupa file.',
            'dokumen_akta.mimes' => 'Format akta kelahiran harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_akta.max' => 'Ukuran akta kelahiran maksimal 2MB.',
            'dokumen_surat_permohonan.required' => 'Surat permohonan harus diupload.',
            'dokumen_surat_permohonan.file' => 'Surat permohonan harus berupa file.',
            'dokumen_surat_permohonan.mimes' => 'Format surat permohonan harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_surat_permohonan.max' => 'Ukuran surat permohonan maksimal 2MB.',
            'dokumen_transkrip.required' => 'Transkrip nilai harus diupload.',
            'dokumen_transkrip.file' => 'Transkrip nilai harus berupa file.',
            'dokumen_transkrip.mimes' => 'Format transkrip harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_transkrip.max' => 'Ukuran transkrip maksimal 2MB.',
            'dokumen_surat_aktif.required' => 'Surat aktif kuliah harus diupload.',
            'dokumen_surat_aktif.file' => 'Surat aktif kuliah harus berupa file.',
            'dokumen_surat_aktif.mimes' => 'Format surat aktif kuliah harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_surat_aktif.max' => 'Ukuran surat aktif kuliah maksimal 2MB.',
            'dokumen_pas_foto.required' => 'Pas foto harus diupload.',
            'dokumen_pas_foto.file' => 'Pas foto harus berupa file.',
            'dokumen_pas_foto.mimes' => 'Format pas foto harus JPG, JPEG, atau PNG.',
            'dokumen_pas_foto.max' => 'Ukuran pas foto maksimal 2MB.',
            'dokumen_surat_pernyataan.required' => 'Surat pernyataan harus diupload.',
            'dokumen_surat_pernyataan.file' => 'Surat pernyataan harus berupa file.',
            'dokumen_surat_pernyataan.mimes' => 'Format surat pernyataan harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_surat_pernyataan.max' => 'Ukuran surat pernyataan maksimal 2MB.',
            'dokumen_sktm.required' => 'SKTM harus diupload.',
            'dokumen_sktm.file' => 'SKTM harus berupa file.',
            'dokumen_sktm.mimes' => 'Format SKTM harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_sktm.max' => 'Ukuran SKTM maksimal 2MB.',
            'dokumen_bukti_ukt.required' => 'Bukti UKT/SPP harus diupload.',
            'dokumen_bukti_ukt.file' => 'Bukti UKT/SPP harus berupa file.',
            'dokumen_bukti_ukt.mimes' => 'Format bukti UKT/SPP harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_bukti_ukt.max' => 'Ukuran bukti UKT/SPP maksimal 2MB.',
            'dokumen_prestasi.*.file' => 'Dokumen prestasi harus berupa file.',
            'dokumen_prestasi.*.mimes' => 'Format dokumen prestasi harus PDF, JPG, JPEG, atau PNG.',
            'dokumen_prestasi.*.max' => 'Ukuran setiap dokumen prestasi maksimal 2MB.',
            'status_orang_tua.required' => 'Status orang tua harus dipilih.',
            'status_orang_tua.in' => 'Status orang tua tidak valid.',
            'ktp_ayah.required_if' => 'KTP ayah harus diupload.',
            'ktp_ayah.file' => 'KTP ayah harus berupa file.',
            'ktp_ayah.mimes' => 'Format KTP ayah harus PDF, JPG, JPEG, atau PNG.',
            'ktp_ayah.max' => 'Ukuran KTP ayah maksimal 2MB.',
            'ktp_ibu.required_if' => 'KTP ibu harus diupload.',
            'ktp_ibu.file' => 'KTP ibu harus berupa file.',
            'ktp_ibu.mimes' => 'Format KTP ibu harus PDF, JPG, JPEG, atau PNG.',
            'ktp_ibu.max' => 'Ukuran KTP ibu maksimal 2MB.',
            'ktp_wali.required_if' => 'KTP wali harus diupload.',
            'ktp_wali.file' => 'KTP wali harus berupa file.',
            'ktp_wali.mimes' => 'Format KTP wali harus PDF, JPG, JPEG, atau PNG.',
            'ktp_wali.max' => 'Ukuran KTP wali maksimal 2MB.',
            'kk_wali.required_if' => 'Kartu Keluarga wali harus diupload.',
            'kk_wali.file' => 'Kartu Keluarga wali harus berupa file.',
            'kk_wali.mimes' => 'Format Kartu Keluarga wali harus PDF, JPG, JPEG, atau PNG.',
            'kk_wali.max' => 'Ukuran Kartu Keluarga wali maksimal 2MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();

            if (! $user) {
                $validator->errors()->add(
                    'beasiswa_id',
                    'Anda harus login terlebih dahulu.'
                );

                return;
            }

            $scholarship = Scholarship::find(
                $this->input('beasiswa_id')
            );

            if (! $scholarship) {
                return;
            }

            if ($scholarship->isExpired()) {
                $validator->errors()->add(
                    'beasiswa_id',
                    'Pendaftaran beasiswa sudah ditutup.'
                );

                return;
            }

            $alreadyApplied = Applicant::query()
                ->where('user_id', $user->id)
                ->where('beasiswa_id', $scholarship->id)
                ->exists();

            if ($alreadyApplied) {
                $validator->errors()->add(
                    'beasiswa_id',
                    'Anda sudah mendaftar beasiswa ini.'
                );

                return;
            }

            $profile = $user->profile;

            if (! $profile) {
                $validator->errors()->add(
                    'beasiswa_id',
                    'Profil Anda belum lengkap. Silakan lengkapi profil terlebih dahulu.'
                );

                return;
            }

            if (! $profile->prodi_id) {
                $validator->errors()->add(
                    'beasiswa_id',
                    'Profil Anda belum memiliki Program Studi. Silakan lengkapi profil terlebih dahulu.'
                );

                return;
            }

            if (! $scholarship->allowsProdi($profile)) {
                $validator->errors()->add(
                    'beasiswa_id',
                    'Program Studi Anda tidak termasuk dalam beasiswa ini.'
                );
            }

            if (
                $scholarship->ipk_minimal !== null
                && (float) $profile->ipk < (float) $scholarship->ipk_minimal
            ) {
                $validator->errors()->add(
                    'ipk',
                    "IPK minimal untuk beasiswa ini adalah {$scholarship->ipk_minimal}. IPK Anda belum memenuhi syarat."
                );
            }

            if (
                $scholarship->semester_minimal !== null
                && (int) $profile->semester < (int) $scholarship->semester_minimal
            ) {
                $validator->errors()->add(
                    'semester',
                    "Semester minimal untuk beasiswa ini adalah {$scholarship->semester_minimal}. Semester Anda belum memenuhi syarat."
                );
            }
        });
    }
}
