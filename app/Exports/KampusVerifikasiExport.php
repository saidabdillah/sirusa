<?php

namespace App\Exports;

use App\Models\User;
use App\Models\UserProfile;

/**
 * Unduhan untuk tahap Kampus: yang diperiksa adalah kelayakan akademik, jadi
 * data akademik jadi isi utamanya -- tapi identitas dasar dan berkas yang
 * menyertainya ikut dibawa, karena pemeriksa kampus harus memastikan profil
 * yang dinilai secara akademik itu memang profil orang yang sama.
 *
 * Cakupannya mengikuti `VerifikasiAntrean`, jadi admin kampus hanya mengunduh
 * mahasiswa kampusnya sendiri.
 */
class KampusVerifikasiExport extends VerifikasiExport
{
    public function slug(): string
    {
        return 'verifikasi-kampus';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Lengkap',
            'NIM',
            'NIK',
            'No. Kartu Keluarga',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Telepon',
            'Provinsi',
            'Kabupaten/Kota',
            'Kecamatan',
            'Desa/Kelurahan',
            'Alamat',
            'Email',
            'Program Studi',
            'Fakultas',
            'Kampus',
            'IPK',
            'Semester',
            'UKT/SPP',
            'Bukti Pembayaran UKT/SPP',
            'Desil',
            'Kartu Keluarga Diikuti',
            'Nama Orang Tua/Wali',
            'NIK Orang Tua/Wali',
            'Pekerjaan Orang Tua/Wali',
            'Status Capil',
            'Status Kampus',
            'Catatan Verifikasi',
        ];
    }

    public function row(User $user, UserProfile $profile, int $nomor): array
    {
        $orangTua = $profile->orangTuaDipilih();

        return [
            $nomor,
            $this->teks($profile->nama_lengkap),
            $this->teks($profile->nim),
            $this->teks($profile->nik),
            $this->teks($profile->no_kk),
            $this->teks($profile->jenis_kelamin),
            $this->teks($profile->tempat_lahir),
            $this->tanggal($profile->tanggal_lahir),
            $this->teks($profile->telepon),
            $this->teks($profile->provinsi),
            $this->teks($profile->kabupaten_kota),
            $this->teks($profile->kecamatan),
            $this->teks($profile->desa_kelurahan),
            $this->teks($profile->alamat),
            $this->teks($user->email),
            $this->teks($profile->prodi?->nama),
            $this->teks($profile->prodi?->fakultas?->nama),
            $this->teks($profile->prodi?->fakultas?->kampus?->nama_kampus),
            // Angka mentah, bukan string "3,50", supaya bisa dijumlahkan di Excel.
            $profile->ipk ?? '-',
            $profile->semester ?? '-',
            $profile->ukt ?? '-',
            $this->namaBerkas($profile->dokumen_bukti_ukt),
            $profile->desil ?: '-',
            // Blok orang tua ikut membawa "Kartu Keluarga Diikuti" supaya
            // nama/NIK/pekerjaan di sebelahnya tidak ambigu: tanpa kolom ini
            // pembaca tidak tahu data itu milik ayah, ibu, atau wali.
            $orangTua['label'],
            $this->teks($orangTua['nama']),
            $this->teks($orangTua['nik']),
            $this->teks($orangTua['pekerjaan']),
            $profile->verifStageDecision('capil')['label'],
            $profile->verifStageDecision('kampus')['label'],
            $this->teks($profile->catatan_kampus),
        ];
    }
}
