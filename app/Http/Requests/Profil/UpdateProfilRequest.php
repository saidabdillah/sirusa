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
            'nik' => 'nullable|string|size:16',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'agama' => 'nullable|in:Islam,Kristen,Katholik,Hindu,Buddha,Konghucu',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'kecamatan' => 'required|string|max:255',
            'desa_kelurahan' => 'required|string|max:255',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status_orang_tua' => 'nullable|in:Lengkap,Yatim,Piatu,Yatim Piatu,Wali',
            'nama_ayah' => 'nullable|string|max:255',
            'status_ayah' => 'nullable|in:Hidup,Meninggal Dunia',
            'pekerjaan_ayah' => 'nullable|in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya',
            'penghasilan_ayah' => 'nullable|in:< 1jt,1-3jt,3-5jt,5-10jt,> 10jt',
            'nama_ibu' => 'nullable|string|max:255',
            'status_ibu' => 'nullable|in:Hidup,Meninggal Dunia',
            'pekerjaan_ibu' => 'nullable|in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya',
            'penghasilan_ibu' => 'nullable|in:< 1jt,1-3jt,3-5jt,5-10jt,> 10jt',
            'nama_wali' => 'nullable|string|max:255',
            'pekerjaan_wali' => 'nullable|in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya',
            'penghasilan_wali' => 'nullable|in:< 1jt,1-3jt,3-5jt,5-10jt,> 10jt',
            'hubungan_wali' => 'nullable|in:Paman,Bibi,Kakek,Nenek,Lainnya',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap harus diisi',
            'nik.size' => 'NIK harus 16 digit',
            'foto_profil.image' => 'File harus berupa gambar',
            'foto_profil.mimes' => 'Format gambar harus jpg, jpeg, atau png',
            'foto_profil.max' => 'Ukuran gambar maksimal 2MB',
        ];
    }
}
