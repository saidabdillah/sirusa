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
        return true;
    }

    public function rules(): array
    {
        return [
            'beasiswa_id' => 'required|exists:beasiswa,id',
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
            'status_orang_tua' => 'required|in:Lengkap,Yatim,Piatu,Yatim Piatu',
            'ktp_ayah' => 'required_if:status_orang_tua,Lengkap,Piatu|nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'ktp_ibu' => 'required_if:status_orang_tua,Lengkap,Yatim|nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'ktp_wali' => 'required_if:status_orang_tua,Yatim Piatu|nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'kk_wali' => 'required_if:status_orang_tua,Yatim Piatu|nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'beasiswa_id.required' => 'Beasiswa harus dipilih',
            'beasiswa_id.exists' => 'Beasiswa tidak ditemukan',
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
            'status_orang_tua.required' => 'Status orang tua harus dipilih',
            'ktp_ayah.required_if' => 'KTP ayah harus diupload',
            'ktp_ayah.mimes' => 'Format KTP ayah harus pdf, jpg, jpeg, atau png',
            'ktp_ayah.max' => 'Ukuran KTP ayah maksimal 2MB',
            'ktp_ibu.required_if' => 'KTP ibu harus diupload',
            'ktp_ibu.mimes' => 'Format KTP ibu harus pdf, jpg, jpeg, atau png',
            'ktp_ibu.max' => 'Ukuran KTP ibu maksimal 2MB',
            'ktp_wali.required_if' => 'KTP wali harus diupload',
            'ktp_wali.mimes' => 'Format KTP wali harus pdf, jpg, jpeg, atau png',
            'ktp_wali.max' => 'Ukuran KTP wali maksimal 2MB',
            'kk_wali.required_if' => 'Kartu keluarga wali harus diupload',
            'kk_wali.mimes' => 'Format kartu keluarga wali harus pdf, jpg, jpeg, atau png',
            'kk_wali.max' => 'Ukuran kartu keluarga wali maksimal 2MB',
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

            $user = $this->user();
            if (! $user) {
                return;
            }

            $alreadyApplied = Applicant::where('user_id', $user->id)
                ->where('beasiswa_id', $scholarship->id)
                ->exists();

            if ($alreadyApplied) {
                $validator->errors()->add('beasiswa_id', 'Anda sudah mendaftar beasiswa ini.');

                return;
            }

            $profile = $user->profile;

            if (! $profile || ! $profile->prodi_id) {
                $validator->errors()->add('beasiswa_id', 'Profil Anda belum memiliki Program Studi. Silakan lengkapi profil Anda terlebih dahulu.');

                return;
            }

            if (! $scholarship->allowsProdi($profile)) {
                $validator->errors()->add('beasiswa_id', 'Program Studi Anda tidak termasuk dalam beasiswa ini.');

                return;
            }

            if ((float) $profile->ipk < (float) $scholarship->ipk_minimal) {
                $validator->errors()->add(
                    'ipk',
                    "IPK minimal untuk beasiswa ini adalah {$scholarship->ipk_minimal}. IPK Anda belum memenuhi syarat."
                );
            }

            if ((int) $profile->semester < (int) $scholarship->semester_minimal) {
                $validator->errors()->add(
                    'semester',
                    "Semester minimal untuk beasiswa ini adalah {$scholarship->semester_minimal}. Semester Anda belum memenuhi syarat."
                );
            }
        });
    }
}
