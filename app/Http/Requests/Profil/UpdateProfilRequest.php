<?php

namespace App\Http\Requests\Profil;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ikut_kk' => $this->input('ikut_kk', 'ayah'),
            'ukt' => str_replace('.', '', (string) $this->input('ukt')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16'],
            'no_kk' => ['required', 'digits:16'],
            'nim' => ['nullable', 'string', 'max:30'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['required', 'in:Islam,Kristen,Katholik,Hindu,Buddha,Konghucu'],
            'telepon' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'provinsi' => ['nullable', 'string', 'max:255'],
            'kabupaten_kota' => ['nullable', 'string', 'max:255'],
            'kecamatan' => ['required', 'string', 'max:255'],
            'desa_kelurahan' => ['required', 'string', 'max:255'],
            'prodi_id' => ['required', 'exists:prodi,id'],
            'ipk' => ['required', 'numeric', 'between:0,4'],
            'semester' => ['required', 'integer', 'between:1,14'],
            'ukt' => ['required', 'numeric', 'min:0'],
            'desil' => ['required', 'integer', 'between:1,10'],
            'ikut_kk' => ['required', 'in:ayah,ibu,wali'],
            'nama_ayah' => ['required_if:ikut_kk,ayah', 'nullable', 'string', 'max:255'],
            'nik_ayah' => ['required_if:ikut_kk,ayah', 'nullable', 'digits:16'],
            'pekerjaan_ayah' => ['required_if:ikut_kk,ayah', 'nullable', 'in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya'],
            'nama_ibu' => ['required_if:ikut_kk,ibu', 'nullable', 'string', 'max:255'],
            'nik_ibu' => ['required_if:ikut_kk,ibu', 'nullable', 'digits:16'],
            'pekerjaan_ibu' => ['required_if:ikut_kk,ibu', 'nullable', 'in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya'],
            'nama_wali' => ['required_if:ikut_kk,wali', 'nullable', 'string', 'max:255'],
            'nik_wali' => ['required_if:ikut_kk,wali', 'nullable', 'digits:16'],
            'pekerjaan_wali' => ['required_if:ikut_kk,wali', 'nullable', 'in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya'],
            'hubungan_wali' => ['required_if:ikut_kk,wali', 'nullable', 'in:Paman,Bibi,Kakek,Nenek,Lainnya'],
            'foto_profil' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'dokumen_ktp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_kk' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_desil' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_sktm' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_transkrip' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_surat_aktif' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_surat_pernyataan' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_bukti_ukt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_prestasi' => ['nullable', 'array'],
            'dokumen_prestasi.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'ktp_ayah' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'ktp_ibu' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'ktp_wali' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'kk_wali' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap harus diisi.',
            'nik.required' => 'NIK harus diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit.',
            'no_kk.required' => 'Nomor Kartu Keluarga harus diisi.',
            'no_kk.digits' => 'Nomor Kartu Keluarga harus terdiri dari 16 digit.',
            'nama_kampus.required' => 'Nama kampus harus dipilih.',
            'prodi_id.required' => 'Program studi harus dipilih.',
            'prodi_id.exists' => 'Program studi tidak valid.',
            'ipk.required' => 'IPK harus diisi.',
            'ipk.numeric' => 'IPK harus berupa angka.',
            'ipk.between' => 'IPK harus antara 0 dan 4.',
            'semester.required' => 'Semester harus diisi.',
            'semester.integer' => 'Semester harus berupa angka.',
            'semester.between' => 'Semester harus antara 1 dan 14.',
            'ukt.required' => 'UKT harus diisi.',
            'ukt.numeric' => 'UKT harus berupa angka.',
            'desil.required' => 'Desil harus dipilih.',
            'desil.integer' => 'Desil harus berupa angka.',
            'desil.between' => 'Desil harus antara 1 dan 10.',
            'ikut_kk.required' => 'Ikut KK harus dipilih.',
            'nama_ayah.required_if' => 'Nama ayah harus diisi.',
            'nik_ayah.required_if' => 'NIK ayah harus diisi.',
            'nik_ayah.digits' => 'NIK ayah harus terdiri dari 16 digit.',
            'pekerjaan_ayah.required_if' => 'Pekerjaan ayah harus dipilih.',
            'nama_ibu.required_if' => 'Nama ibu harus diisi.',
            'nik_ibu.required_if' => 'NIK ibu harus diisi.',
            'nik_ibu.digits' => 'NIK ibu harus terdiri dari 16 digit.',
            'pekerjaan_ibu.required_if' => 'Pekerjaan ibu harus dipilih.',
            'nama_wali.required_if' => 'Nama wali harus diisi.',
            'nik_wali.required_if' => 'NIK wali harus diisi.',
            'nik_wali.digits' => 'NIK wali harus terdiri dari 16 digit.',
            'pekerjaan_wali.required_if' => 'Pekerjaan wali harus dipilih.',
            'hubungan_wali.required_if' => 'Hubungan dengan wali harus dipilih.',
            'foto_profil.image' => 'Pas foto harus berupa gambar.',
            'foto_profil.mimes' => 'Format pas foto harus JPG, JPEG, atau PNG.',
            'foto_profil.max' => 'Ukuran pas foto maksimal 2MB.',
        ];
    }
}
