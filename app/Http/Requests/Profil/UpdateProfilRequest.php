<?php

namespace App\Http\Requests\Profil;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_lengkap' => 'required|string|max:255',
            'nik' => 'required|string|size:16',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'agama' => 'required|in:Islam,Kristen,Katholik,Hindu,Buddha,Konghucu',
            'telepon' => 'required|string|max:20',
            'alamat' => 'required|string',
            'provinsi' => 'nullable|string|max:255',
            'kabupaten_kota' => 'nullable|string|max:255',
            'kecamatan' => 'required|string|max:255',
            'desa_kelurahan' => 'required|string|max:255',
            'prodi_id' => 'required|exists:prodi,id',
            'ipk' => 'required|numeric|between:0,4',
            'semester' => 'required|integer|between:1,14',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status_orang_tua' => 'required|in:Lengkap,Yatim,Piatu,Yatim Piatu',
            'nama_ayah' => 'required_if:status_orang_tua,Lengkap,Piatu|string|max:255',
            'nik_ayah' => 'required_if:status_orang_tua,Lengkap,Piatu|string|size:16',
            'pekerjaan_ayah' => 'required_if:status_orang_tua,Lengkap,Piatu|in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya',
            'penghasilan_ayah' => 'required_if:status_orang_tua,Lengkap,Piatu|in:< 1jt,1-3jt,3-5jt,5-10jt,> 10jt',
            'nama_ibu' => 'required_if:status_orang_tua,Lengkap,Yatim|string|max:255',
            'nik_ibu' => 'required_if:status_orang_tua,Lengkap,Yatim|string|size:16',
            'pekerjaan_ibu' => 'required_if:status_orang_tua,Lengkap,Yatim|in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya',
            'penghasilan_ibu' => 'required_if:status_orang_tua,Lengkap,Yatim|in:< 1jt,1-3jt,3-5jt,5-10jt,> 10jt',
            'nama_wali' => 'required_if:status_orang_tua,Yatim Piatu|string|max:255',
            'nik_wali' => 'required_if:status_orang_tua,Yatim Piatu|string|size:16',
            'pekerjaan_wali' => 'required_if:status_orang_tua,Yatim Piatu|in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya',
            'penghasilan_wali' => 'required_if:status_orang_tua,Yatim Piatu|in:< 1jt,1-3jt,3-5jt,5-10jt,> 10jt',
            'hubungan_wali' => 'required_if:status_orang_tua,Yatim Piatu|in:Paman,Bibi,Kakek,Nenek,Lainnya',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap harus diisi',
            'nik.required' => 'NIK harus diisi',
            'nik.size' => 'NIK harus 16 digit',
            'tempat_lahir.required' => 'Tempat lahir harus diisi',
            'tanggal_lahir.required' => 'Tanggal lahir harus diisi',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih',
            'agama.required' => 'Agama harus dipilih',
            'telepon.required' => 'Telepon harus diisi',
            'alamat.required' => 'Alamat harus diisi',
            'kecamatan.required' => 'Kecamatan harus dipilih',
            'desa_kelurahan.required' => 'Desa/Kelurahan harus dipilih',
            'prodi_id.required' => 'Program studi harus diisi',
            'ipk.required' => 'IPK harus diisi',
            'ipk.between' => 'IPK harus antara 0 dan 4',
            'semester.required' => 'Semester harus diisi',
            'semester.between' => 'Semester harus antara 1 dan 14',
            'foto_profil.image' => 'File harus berupa gambar',
            'foto_profil.mimes' => 'Format gambar harus jpg, jpeg, atau png',
            'foto_profil.max' => 'Ukuran gambar maksimal 2MB',
            'status_orang_tua.required' => 'Status orang tua harus dipilih',
            'nama_ayah.required_if' => 'Nama ayah harus diisi',
            'nik_ayah.required_if' => 'NIK ayah harus diisi',
            'nik_ayah.size' => 'NIK ayah harus 16 digit',
            'pekerjaan_ayah.required_if' => 'Pekerjaan ayah harus dipilih',
            'penghasilan_ayah.required_if' => 'Penghasilan ayah harus dipilih',
            'nama_ibu.required_if' => 'Nama ibu harus diisi',
            'nik_ibu.required_if' => 'NIK ibu harus diisi',
            'nik_ibu.size' => 'NIK ibu harus 16 digit',
            'pekerjaan_ibu.required_if' => 'Pekerjaan ibu harus dipilih',
            'penghasilan_ibu.required_if' => 'Penghasilan ibu harus dipilih',
            'nama_wali.required_if' => 'Nama wali harus diisi',
            'nik_wali.required_if' => 'NIK wali harus diisi',
            'nik_wali.size' => 'NIK wali harus 16 digit',
            'pekerjaan_wali.required_if' => 'Pekerjaan wali harus dipilih',
            'penghasilan_wali.required_if' => 'Penghasilan wali harus dipilih',
            'hubungan_wali.required_if' => 'Hubungan wali harus dipilih',
        ];
    }
}
