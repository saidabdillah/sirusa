<?php

namespace App\Http\Requests\Profil;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya mahasiswa yang punya profil isian + dokumen. Akun staf
        // (super_admin/kesra/kampus/capil) tidak punya baris profil, jadi form
        // ini tidak boleh bisa disimpan untuk mereka meski formnya disembunyikan
        // di halaman profil.
        return $this->user()->isMahasiswa();
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
        $pakaiWali = $this->input('ikut_kk') === 'wali';

        $pekerjaan = 'in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya';

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
            'nama_kampus' => ['required', 'string', 'max:255'],
            'fakultas' => ['required', 'string', 'max:255'],
            'prodi_id' => ['required', 'exists:prodi,id'],
            'ipk' => ['required', 'numeric', 'between:0,4'],
            'semester' => ['required', 'integer', 'between:1,14'],
            'ukt' => ['required', 'numeric', 'min:0'],
            'desil' => ['required', 'integer', 'between:1,10'],
            'ikut_kk' => ['required', 'in:ayah,ibu,wali'],

            // Ketiga blok (ayah, ibu, wali) selalu tampil di form dan selalu bertanda *,
            // jadi wali hanya wajib bila KK-nya memang mengikuti wali.
            'nama_ayah' => ['required', 'string', 'max:255'],
            'nik_ayah' => ['required', 'digits:16'],
            'pekerjaan_ayah' => ['required', $pekerjaan],
            'nama_ibu' => ['required', 'string', 'max:255'],
            'nik_ibu' => ['required', 'digits:16'],
            'pekerjaan_ibu' => ['required', $pekerjaan],
            'nama_wali' => $pakaiWali ? ['required', 'string', 'max:255'] : ['nullable'],
            'nik_wali' => $pakaiWali ? ['required', 'digits:16'] : ['nullable'],
            'pekerjaan_wali' => $pakaiWali ? ['required', $pekerjaan] : ['nullable'],
            'hubungan_wali' => $pakaiWali ? ['required', 'in:Paman,Bibi,Kakek,Nenek,Lainnya'] : ['nullable'],

            'foto_profil' => $this->dokumen('foto_profil', 'image', 'jpg,jpeg,png'),
            'dokumen_ktp' => $this->dokumen('dokumen_ktp'),
            'dokumen_kk' => $this->dokumen('dokumen_kk'),
            'dokumen_desil' => $this->dokumen('dokumen_desil'),
            'dokumen_sktm' => $this->dokumen('dokumen_sktm'),
            'dokumen_transkrip' => $this->dokumen('dokumen_transkrip'),
            'dokumen_surat_aktif' => $this->dokumen('dokumen_surat_aktif'),
            'dokumen_surat_pernyataan' => $this->dokumen('dokumen_surat_pernyataan'),
            'dokumen_bukti_ukt' => $this->dokumen('dokumen_bukti_ukt'),
            'ktp_ayah' => $this->dokumen('ktp_ayah'),
            'ktp_ibu' => $this->dokumen('ktp_ibu'),
            'ktp_wali' => $pakaiWali ? $this->dokumen('ktp_wali') : ['nullable'],
            'kk_wali' => $pakaiWali ? $this->dokumen('kk_wali') : ['nullable'],
            'dokumen_prestasi' => ['nullable', 'array'],
            'dokumen_prestasi.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Field berkas bertanda * pada form profil: wajib diunggah, kecuali filenya
     * sudah tersimpan di profil (dokumen yang sudah ada tidak diunggah ulang).
     *
     * @return array<int, string>
     */
    private function dokumen(string $field, string $tipe = 'file', string $ekstensi = 'pdf,jpg,jpeg,png'): array
    {
        return [
            filled($this->user()?->profile?->{$field}) ? 'nullable' : 'required',
            $tipe,
            'mimes:'.$ekstensi,
            'max:2048',
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
            'tempat_lahir.required' => 'Tempat lahir harus diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir harus diisi.',
            'tanggal_lahir.date' => 'Tanggal lahir tidak valid.',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih.',
            'agama.required' => 'Agama harus dipilih.',
            'telepon.required' => 'Telepon harus diisi.',
            'alamat.required' => 'Alamat harus diisi.',
            'kecamatan.required' => 'Kecamatan harus dipilih.',
            'desa_kelurahan.required' => 'Desa/Kelurahan harus dipilih.',
            'nama_kampus.required' => 'Nama kampus harus dipilih.',
            'fakultas.required' => 'Fakultas harus dipilih.',
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
            'nama_ayah.required' => 'Nama ayah harus diisi.',
            'nik_ayah.required' => 'NIK ayah harus diisi.',
            'nik_ayah.digits' => 'NIK ayah harus terdiri dari 16 digit.',
            'pekerjaan_ayah.required' => 'Pekerjaan ayah harus dipilih.',
            'nama_ibu.required' => 'Nama ibu harus diisi.',
            'nik_ibu.required' => 'NIK ibu harus diisi.',
            'nik_ibu.digits' => 'NIK ibu harus terdiri dari 16 digit.',
            'pekerjaan_ibu.required' => 'Pekerjaan ibu harus dipilih.',
            'nama_wali.required' => 'Nama wali harus diisi.',
            'nik_wali.required' => 'NIK wali harus diisi.',
            'nik_wali.digits' => 'NIK wali harus terdiri dari 16 digit.',
            'pekerjaan_wali.required' => 'Pekerjaan wali harus dipilih.',
            'hubungan_wali.required' => 'Hubungan dengan wali harus dipilih.',
            'foto_profil.required' => 'Pas foto 3x4 wajib diunggah.',
            'foto_profil.image' => 'Pas foto harus berupa gambar.',
            'foto_profil.mimes' => 'Format pas foto harus JPG, JPEG, atau PNG.',
            'foto_profil.max' => 'Ukuran pas foto maksimal 2MB.',
            'dokumen_ktp.required' => 'KTP wajib diunggah.',
            'dokumen_kk.required' => 'Kartu Keluarga wajib diunggah.',
            'dokumen_desil.required' => 'Dokumen Desil wajib diunggah.',
            'dokumen_sktm.required' => 'SKTM wajib diunggah.',
            'dokumen_transkrip.required' => 'Transkrip wajib diunggah.',
            'dokumen_surat_aktif.required' => 'Surat Aktif Kuliah wajib diunggah.',
            'dokumen_surat_pernyataan.required' => 'Surat Pernyataan wajib diunggah.',
            'dokumen_bukti_ukt.required' => 'Bukti UKT wajib diunggah.',
            'ktp_ayah.required' => 'KTP Ayah wajib diunggah.',
            'ktp_ibu.required' => 'KTP Ibu wajib diunggah.',
            'ktp_wali.required' => 'KTP Wali wajib diunggah.',
            'kk_wali.required' => 'KK Wali wajib diunggah.',
        ];
    }
}
