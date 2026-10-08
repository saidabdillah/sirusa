<?php

namespace App\Exports;

use App\Models\User;
use App\Models\UserProfile;

/**
 * Unduhan untuk tahap Catpil: yang diperiksa adalah identitas dan data
 * kependudukan, jadi kolom akademiknya (NIM, IPK, UKT, dokumen kampus)
 * sengaja tidak ikut. Yang ditambahkan justru rincian wilayah -- provinsi
 * sampai desa -- dan data orang tua/wali yang dipilih, karena keduanya bahan
 * yang dibandingkan pemeriksa Catpil dengan berkas KTP dan KK.
 */
class CatpilVerifikasiExport extends VerifikasiExport
{
    public function slug(): string
    {
        return 'verifikasi-catpil';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Lengkap',
            'NIK',
            'No. Kartu Keluarga',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Agama',
            'Telepon',
            'Provinsi',
            'Kabupaten/Kota',
            'Kecamatan',
            'Desa/Kelurahan',
            'Alamat',
            'Email',
            'Kartu Keluarga Diikuti',
            'Nama Orang Tua/Wali',
            'NIK Orang Tua/Wali',
            'Pekerjaan Orang Tua/Wali',
            'Desil',
            'Status Catpil',
            'Catatan Verifikasi',
        ];
    }

    public function row(User $user, UserProfile $profile, int $nomor): array
    {
        $orangTua = $profile->orangTuaDipilih();

        return [
            $nomor,
            $this->teks($profile->nama_lengkap),
            $this->teks($profile->nik),
            $this->teks($profile->no_kk),
            $this->teks($profile->jenis_kelamin),
            $this->teks($profile->tempat_lahir),
            $this->tanggal($profile->tanggal_lahir),
            $this->teks($profile->agama),
            $this->teks($profile->telepon),
            $this->teks($profile->provinsi),
            $this->teks($profile->kabupaten_kota),
            $this->teks($profile->kecamatan),
            $this->teks($profile->desa_kelurahan),
            // `alamat` mentah, bukan accessor `alamat_lengkap`: wilayahnya
            // sudah punya kolom sendiri di atas.
            $this->teks($profile->alamat),
            $this->teks($user->email),
            $orangTua['label'],
            $this->teks($orangTua['nama']),
            $this->teks($orangTua['nik']),
            $this->teks($orangTua['pekerjaan']),
            $profile->desil ?: '-',
            $profile->verifStageDecision('catpil')['label'],
            $this->teks($profile->catatan_catpil),
        ];
    }
}
