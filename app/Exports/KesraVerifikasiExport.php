<?php

namespace App\Exports;

use App\Models\Applicant;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Collection;

/**
 * Unduhan untuk tahap Kesra: tahap akhir, jadi isinya gabungan identitas, data
 * akademik, dan pendaftaran yang sedang diputus -- ditambah keputusan dua
 * tahap sebelumnya, karena pemutus akhir perlu tahu dasar keputusannya.
 *
 * Satu mahasiswa bisa punya lebih dari satu pendaftaran, jadi kolom
 * pendaftarannya digabung per baris, bukan menambah baris.
 */
class KesraVerifikasiExport extends VerifikasiExport
{
    public function slug(): string
    {
        return 'verifikasi-kesra';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Lengkap',
            'NIK',
            'No. Kartu Keluarga',
            'NIM',
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
            'Beasiswa',
            'Status Pendaftaran',
            'Catatan Pendaftaran',
            'Status Catpil',
            'Status Kampus',
            'Status Kesra',
            'Catatan Verifikasi',
        ];
    }

    public function row(User $user, UserProfile $profile, int $nomor): array
    {
        $orangTua = $profile->orangTuaDipilih();
        $pendaftaran = $this->pendaftaran($user);

        return [
            $nomor,
            $this->teks($profile->nama_lengkap),
            $this->teks($profile->nik),
            $this->teks($profile->no_kk),
            $this->teks($profile->nim),
            $this->teks($profile->jenis_kelamin),
            $this->teks($profile->tempat_lahir),
            $this->tanggal($profile->tanggal_lahir),
            $this->teks($profile->agama),
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
            $profile->ipk ?? '-',
            $profile->semester ?? '-',
            $profile->ukt ?? '-',
            $this->namaBerkas($profile->dokumen_bukti_ukt),
            $profile->desil ?: '-',
            $orangTua['label'],
            $this->teks($orangTua['nama']),
            $this->teks($orangTua['nik']),
            $this->teks($orangTua['pekerjaan']),
            $this->teks($pendaftaran->pluck('beasiswa')->filter()->map->nama->implode('; ')),
            $this->teks($pendaftaran->map->statusLabel()->implode('; ')),
            $this->teks($pendaftaran->pluck('catatan')->filter()->implode('; ')),
            $profile->verifStageDecision('catpil')['label'],
            $profile->verifStageDecision('kampus')['label'],
            $profile->verifStageDecision('kesra')['label'],
            $this->teks($profile->catatan_kesra),
        ];
    }

    /**
     * Baris unduhan Kesra diambil dari antrean PENDAFTARAN, bukan antrean profil.
     *
     * `VerifikasiExport::users()` bawaannya memfilter profil lewat
     * `canVerifStage()`, yang hanya berarti "sudah lolos Capil dan Kampus" --
     * termasuk pendaftaran yang keputusannya sudah dicetak berminggu lalu. Itu
     * bukan isi antrean Kesra, dan unduhan yang berbeda dari daftar yang dibaca
     * admin adalah sumber kesalahan yang mahal.
     */
    public function users(): Collection
    {
        return $this->antrean()->pendaftarUsers($this->filter());
    }

    /**
     * Pendaftaran milik satu orang, dipersempit ke status yang sedang difilter
     * supaya kolom "Status Pendaftaran" dan "Catatan Pendaftaran" menggambarkan
     * antrean yang sama dengan yang diunduh.
     *
     * @return Collection<int, Applicant>
     */
    private function pendaftaran(User $user): Collection
    {
        $applicants = $user->relationLoaded('applicants')
            ? $user->applicants
            : $user->applicants()->with('beasiswa')->get();

        return $this->filter() !== null
            ? $applicants->where('status', $this->filter())->values()
            : $applicants;
    }
}
