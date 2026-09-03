<?php

namespace App\Exports;

use App\Models\Applicant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PendaftarExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(protected Request $request) {}

    public function query(): Builder
    {
        return Applicant::with(['user.profile', 'beasiswa'])
            ->when($this->request->filled('status'), function ($query) {
                $query->where('status', $this->request->string('status'));
            })
            ->when($this->request->filled('beasiswa_id'), function ($query) {
                $query->where('beasiswa_id', $this->request->integer('beasiswa_id'));
            })
            ->latest();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Lengkap',
            'NIK',
            'Beasiswa',
            'Kampus',
            'Fakultas',
            'Prodi',
            'IPK',
            'Semester',
            'Status',
            'Telepon',
            'Tanggal Daftar',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Agama',
            'Alamat',
            'Provinsi',
            'Kabupaten/Kota',
            'Kecamatan',
            'Desa/Kelurahan',
            'Status Orang Tua',
            'Nama Ayah',
            'Status Ayah',
            'Pekerjaan Ayah',
            'Penghasilan Ayah',
            'NIK Ayah',
            'Nama Ibu',
            'Status Ibu',
            'Pekerjaan Ibu',
            'Penghasilan Ibu',
            'NIK Ibu',
            'Nama Wali',
            'Hubungan Wali',
            'Pekerjaan Wali',
            'Penghasilan Wali',
            'NIK Wali',
        ];
    }

    public function map($applicant): array
    {
        static $index = 0;

        $profile = $applicant->user?->profile;

        return [
            ++$index,
            $profile?->nama_lengkap ?? $applicant->user?->username ?? '-',
            $profile?->nik ?? '-',
            $applicant->beasiswa->nama ?? '-',
            $applicant->beasiswa->kampus ?? '-',
            $applicant->fakultas ?? '-',
            $applicant->prodi ?? '-',
            $applicant->ipk ?? '-',
            $applicant->semester ?? '-',
            $applicant->getStatusLabelAttribute(),
            $profile?->telepon ?? '-',
            $applicant->created_at?->format('d M Y') ?? '-',
            $profile?->tempat_lahir ?? '-',
            $profile?->tanggal_lahir?->format('d M Y') ?? '-',
            $profile?->jenis_kelamin ?? '-',
            $profile?->agama ?? '-',
            $profile?->alamat ?? '-',
            $profile?->provinsi ?? '-',
            $profile?->kabupaten_kota ?? '-',
            $profile?->kecamatan ?? '-',
            $profile?->desa_kelurahan ?? '-',
            $profile?->status_orang_tua ?? '-',
            $profile?->nama_ayah ?? '-',
            $profile?->status_ayah ?? '-',
            $profile?->pekerjaan_ayah ?? '-',
            $profile?->penghasilan_ayah ?? '-',
            $profile?->nik_ayah ?? '-',
            $profile?->nama_ibu ?? '-',
            $profile?->status_ibu ?? '-',
            $profile?->pekerjaan_ibu ?? '-',
            $profile?->penghasilan_ibu ?? '-',
            $profile?->nik_ibu ?? '-',
            $profile?->nama_wali ?? '-',
            $profile?->hubungan_wali ?? '-',
            $profile?->pekerjaan_wali ?? '-',
            $profile?->penghasilan_wali ?? '-',
            $profile?->nik_wali ?? '-',
        ];
    }
}
