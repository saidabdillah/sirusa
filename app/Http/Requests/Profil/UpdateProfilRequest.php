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
            'nama_lengkap' => [
                'required',
                'string',
                'max:255',
            ],
            'nik' => [
                'required',
                'digits:16',
            ],
            'tempat_lahir' => [
                'required',
                'string',
                'max:255',
            ],
            'tanggal_lahir' => [
                'required',
                'date',
            ],
            'jenis_kelamin' => [
                'required',
                'in:Laki-laki,Perempuan',
            ],
            'agama' => [
                'required',
                'in:Islam,Kristen,Katholik,Hindu,Buddha,Konghucu',
            ],
            'telepon' => [
                'required',
                'string',
                'max:20',
            ],
            'alamat' => [
                'required',
                'string',
            ],
            'provinsi' => [
                'nullable',
                'string',
                'max:255',
            ],
            'kabupaten_kota' => [
                'nullable',
                'string',
                'max:255',
            ],
            'kecamatan' => [
                'required',
                'string',
                'max:255',
            ],
            'desa_kelurahan' => [
                'required',
                'string',
                'max:255',
            ],
            'nama_kampus' => [
                'required',
                'string',
                'max:255',
            ],

            'fakultas' => [
                'required',
                'string',
                'max:255',
            ],
            'prodi_id' => [
                'required',
                'exists:prodi,id',
            ],
            'ipk' => [
                'required',
                'numeric',
                'between:0,4',
            ],
            'semester' => [
                'required',
                'integer',
                'between:1,14',
            ],
            'foto_profil' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
            'status_orang_tua' => [
                'required',
                'in:Lengkap,Yatim,Piatu,Yatim Piatu',
            ],
            'nama_ayah' => [
                'required_if:status_orang_tua,Lengkap,Piatu',
                'nullable',
                'string',
                'max:255',
            ],
            'nik_ayah' => [
                'required_if:status_orang_tua,Lengkap,Piatu',
                'nullable',
                'digits:16',
            ],
            'pekerjaan_ayah' => [
                'required_if:status_orang_tua,Lengkap,Piatu',
                'nullable',
                'in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya',
            ],
            'penghasilan_ayah' => [
                'required_if:status_orang_tua,Lengkap,Piatu',
                'nullable',
                'in:< 1jt,1-3jt,3-5jt,5-10jt,> 10jt',
            ],
            'nama_ibu' => [
                'required_if:status_orang_tua,Lengkap,Yatim',
                'nullable',
                'string',
                'max:255',
            ],
            'nik_ibu' => [
                'nullable',
                'digits:16',
            ],
            'pekerjaan_ibu' => [
                'required_if:status_orang_tua,Lengkap,Yatim',
                'nullable',
                'in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya',
            ],
            'penghasilan_ibu' => [
                'required_if:status_orang_tua,Lengkap,Yatim',
                'nullable',
                'in:< 1jt,1-3jt,3-5jt,5-10jt,> 10jt',
            ],
            'nama_wali' => [
                'required_if:status_orang_tua,Yatim Piatu',
                'nullable',
                'string',
                'max:255',
            ],
            'nik_wali' => [
                'required_if:status_orang_tua,Yatim Piatu',
                'nullable',
                'digits:16',
            ],
            'pekerjaan_wali' => [
                'required_if:status_orang_tua,Yatim Piatu',
                'nullable',
                'in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya',
            ],
            'penghasilan_wali' => [
                'required_if:status_orang_tua,Yatim Piatu',
                'nullable',
                'in:< 1jt,1-3jt,3-5jt,5-10jt,> 10jt',
            ],
            'hubungan_wali' => [
                'required_if:status_orang_tua,Yatim Piatu',
                'nullable',
                'in:Paman,Bibi,Kakek,Nenek,Lainnya',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap harus diisi.',
            'nik.required' => 'NIK harus diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit.',
            'tempat_lahir.required' => 'Tempat lahir harus diisi.',
            'tempat_lahir.max' => 'Tempat lahir maksimal 255 karakter.',
            'tanggal_lahir.required' => 'Tanggal lahir harus diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'agama.required' => 'Agama harus dipilih.',
            'agama.in' => 'Agama tidak valid.',
            'telepon.required' => 'Nomor telepon harus diisi.',
            'telepon.max' => 'Nomor telepon maksimal 20 karakter.',
            'alamat.required' => 'Alamat harus diisi.',
            'provinsi.max' => 'Provinsi maksimal 255 karakter.',
            'kabupaten_kota.max' => 'Kabupaten/Kota maksimal 255 karakter.',
            'kecamatan.required' => 'Kecamatan harus dipilih.',
            'desa_kelurahan.required' => 'Desa/Kelurahan harus dipilih.',
            'fakultas.required' => 'Fakultas harus dipilih.',
            'nama_kampus.required' => 'Nama kampus harus dipilih.',
            'prodi_id.required' => 'Program studi harus dipilih.',
            'prodi_id.exists' => 'Program studi tidak valid.',
            'ipk.required' => 'IPK harus diisi.',
            'ipk.numeric' => 'IPK harus berupa angka.',
            'ipk.between' => 'IPK harus antara 0 dan 4.',
            'semester.required' => 'Semester harus diisi.',
            'semester.integer' => 'Semester harus berupa angka.',
            'semester.between' => 'Semester harus antara 1 dan 14.',
            'foto_profil.image' => 'File foto profil harus berupa gambar.',
            'foto_profil.mimes' => 'Format foto profil harus JPG, JPEG, atau PNG.',
            'foto_profil.max' => 'Ukuran foto profil maksimal 2MB.',
            'status_orang_tua.required' => 'Status orang tua harus dipilih.',
            'status_orang_tua.in' => 'Status orang tua tidak valid.',
            'nama_ayah.required_if' => 'Nama ayah harus diisi.',
            'nik_ayah.required_if' => 'NIK ayah harus diisi.',
            'nik_ayah.digits' => 'NIK ayah harus terdiri dari 16 digit.',
            'pekerjaan_ayah.required_if' => 'Pekerjaan ayah harus dipilih.',
            'pekerjaan_ayah.in' => 'Pekerjaan ayah tidak valid.',
            'penghasilan_ayah.required_if' => 'Penghasilan ayah harus dipilih.',
            'penghasilan_ayah.in' => 'Penghasilan ayah tidak valid.',
            'nama_ibu.required_if' => 'Nama ibu harus diisi.',
            'nik_ibu.digits' => 'NIK ibu harus terdiri dari 16 digit.',
            'pekerjaan_ibu.required_if' => 'Pekerjaan ibu harus dipilih.',
            'pekerjaan_ibu.in' => 'Pekerjaan ibu tidak valid.',
            'penghasilan_ibu.required_if' => 'Penghasilan ibu harus dipilih.',
            'penghasilan_ibu.in' => 'Penghasilan ibu tidak valid.',
            'nama_wali.required_if' => 'Nama wali harus diisi.',
            'nik_wali.required_if' => 'NIK wali harus diisi.',
            'nik_wali.digits' => 'NIK wali harus terdiri dari 16 digit.',
            'pekerjaan_wali.required_if' => 'Pekerjaan wali harus dipilih.',
            'pekerjaan_wali.in' => 'Pekerjaan wali tidak valid.',
            'penghasilan_wali.required_if' => 'Penghasilan wali harus dipilih.',
            'penghasilan_wali.in' => 'Penghasilan wali tidak valid.',
            'hubungan_wali.required_if' => 'Hubungan dengan wali harus dipilih.',
            'hubungan_wali.in' => 'Hubungan dengan wali tidak valid.',
        ];
    }
}
