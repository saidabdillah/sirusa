<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    private array $pekerjaan = ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh'];

    private array $penghasilan = ['< 1jt', '1-3jt', '3-5jt', '5-10jt', '> 10jt'];

    public function run(): void
    {
        $scholarships = Scholarship::with(['fakultas.prodi', 'kampus'])->where('status', 'aktif')->get();
        $users = $this->userData();

        foreach ($users as $i => $data) {
            $number = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['email' => "user{$number}@sirusa.test"],
                [
                    'username' => "user{$number}",
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'status' => 'aktif',
                ],
            );
            $user->assignRole('user');

            $prodi = $data['prodi'];
            $fakultas = $prodi?->fakultas;
            $nik = $this->nik($number);

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap' => $data['nama_lengkap'],
                    'nik' => $nik,
                    'tempat_lahir' => $data['tempat_lahir'],
                    'tanggal_lahir' => $data['tanggal_lahir'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'agama' => 'Islam',
                    'telepon' => '0812'.$number.str_repeat('0', 6),
                    'alamat' => $data['alamat'],
                    'provinsi' => 'Kalimantan Selatan',
                    'kabupaten_kota' => $data['kabupaten'],
                    'kecamatan' => $data['kecamatan'],
                    'desa_kelurahan' => $data['desa'],
                    'prodi_id' => $prodi?->id,
                    'ipk' => $data['ipk'],
                    'semester' => $data['semester'],
                    'status_orang_tua' => $data['status_orang_tua'],
                    'nama_ayah' => $data['nama_ayah'] ?? null,
                    'nik_ayah' => $data['nik_ayah'] ?? null,
                    'status_ayah' => $data['status_ayah'] ?? null,
                    'pekerjaan_ayah' => $data['pekerjaan_ayah'] ?? null,
                    'penghasilan_ayah' => $data['penghasilan_ayah'] ?? null,
                    'nama_ibu' => $data['nama_ibu'] ?? null,
                    'nik_ibu' => $data['nik_ibu'] ?? null,
                    'status_ibu' => $data['status_ibu'] ?? null,
                    'pekerjaan_ibu' => $data['pekerjaan_ibu'] ?? null,
                    'penghasilan_ibu' => $data['penghasilan_ibu'] ?? null,
                    'nama_wali' => $data['nama_wali'] ?? null,
                    'nik_wali' => $data['nik_wali'] ?? null,
                    'hubungan_wali' => $data['hubungan_wali'] ?? null,
                    'pekerjaan_wali' => $data['pekerjaan_wali'] ?? null,
                    'penghasilan_wali' => $data['penghasilan_wali'] ?? null,
                ],
            );

            foreach ($data['applicants'] as $scholarshipNama => $status) {
                $scholarship = $scholarships->firstWhere('nama', $scholarshipNama);

                if (! $scholarship) {
                    continue;
                }

                Applicant::updateOrCreate(
                    ['user_id' => $user->id, 'beasiswa_id' => $scholarship->id],
                    [
                        'fakultas' => $fakultas?->nama,
                        'prodi' => $prodi?->nama,
                        'ipk' => $data['ipk'],
                        'semester' => $data['semester'],
                        'status' => $status,
                    ],
                );
            }
        }
    }

    private function nik(string $number): string
    {
        return '6303'.str_pad($number, 12, '0', STR_PAD_LEFT);
    }

    private function ayah(array $data): array
    {
        return [
            'status_orang_tua' => 'Lengkap',
            'nama_ayah' => 'Ayah '.$data['nama_lengkap'],
            'nik_ayah' => '6303010000000001',
            'status_ayah' => 'Hidup',
            'pekerjaan_ayah' => $this->pekerjaan[array_rand($this->pekerjaan)],
            'penghasilan_ayah' => $this->penghasilan[array_rand($this->penghasilan)],
            'nama_ibu' => 'Ibu '.$data['nama_lengkap'],
            'nik_ibu' => '6303010000000002',
            'status_ibu' => 'Hidup',
            'pekerjaan_ibu' => $this->pekerjaan[array_rand($this->pekerjaan)],
            'penghasilan_ibu' => $this->penghasilan[array_rand($this->penghasilan)],
        ];
    }

    private function yatim(array $data): array
    {
        return [
            'status_orang_tua' => 'Yatim',
            'nama_ibu' => 'Ibu '.$data['nama_lengkap'],
            'nik_ibu' => '6303010000000002',
            'status_ibu' => 'Hidup',
            'pekerjaan_ibu' => $this->pekerjaan[array_rand($this->pekerjaan)],
            'penghasilan_ibu' => $this->penghasilan[array_rand($this->penghasilan)],
        ];
    }

    private function piatu(array $data): array
    {
        return [
            'status_orang_tua' => 'Piatu',
            'nama_ayah' => 'Ayah '.$data['nama_lengkap'],
            'nik_ayah' => '6303010000000001',
            'status_ayah' => 'Hidup',
            'pekerjaan_ayah' => $this->pekerjaan[array_rand($this->pekerjaan)],
            'penghasilan_ayah' => $this->penghasilan[array_rand($this->penghasilan)],
        ];
    }

    private function yatimPiatu(array $data): array
    {
        return [
            'status_orang_tua' => 'Yatim Piatu',
            'nama_wali' => 'Wali '.$data['nama_lengkap'],
            'nik_wali' => '6303010000000003',
            'hubungan_wali' => 'Paman',
            'pekerjaan_wali' => $this->pekerjaan[array_rand($this->pekerjaan)],
            'penghasilan_wali' => $this->penghasilan[array_rand($this->penghasilan)],
        ];
    }

    private function userData(): array
    {
        $prodi = function (string $kampusNama, string $prodiNama): ?object {
            $k = Kampus::where('nama_kampus', $kampusNama)->with('fakultas.prodi')->first();

            return $k?->fakultas->flatMap->prodi->firstWhere('nama', $prodiNama);
        };

        $rows = [
            ['nama_lengkap' => 'Muhammad Rizki Pratama', 'tempat_lahir' => 'Paringin', 'tanggal_lahir' => '2001-03-15', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Balangan', 'kecamatan' => 'Awayan', 'desa' => 'Pulantan', 'alamat' => 'Jl. Antasari No. 12', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Teknik Informatika', 'ipk' => 3.80, 'semester' => 5, 'status' => 'ayah', 'applicants' => ['Beasiswa Pendidikan Kab. Balangan' => 'diterima', 'Beasiswa Unggulan Teknik' => 'diterima']],
            ['nama_lengkap' => 'Siti Nurhaliza', 'tempat_lahir' => 'Barabai', 'tanggal_lahir' => '2001-07-22', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Paringin', 'desa' => 'Batumapai', 'alamat' => 'Jl. Merdeka No. 5', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Manajemen', 'ipk' => 3.65, 'semester' => 4, 'status' => 'ayah', 'applicants' => ['Beasiswa Pendidikan Kab. Balangan' => 'verifikasi', 'Beasiswa Sosial Ekonomi' => 'diterima']],
            ['nama_lengkap' => 'Ahmad Fadillah', 'tempat_lahir' => 'Banjarmasin', 'tanggal_lahir' => '1999-11-05', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Banjar', 'kecamatan' => 'Awayan', 'desa' => 'Lok Batu', 'alamat' => 'Jl. Sudirman No. 8', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Teknik Elektro', 'ipk' => 3.45, 'semester' => 6, 'status' => 'yatim', 'applicants' => ['Beasiswa Unggulan Teknik' => 'verifikasi']],
            ['nama_lengkap' => 'Nurul Aisyah', 'tempat_lahir' => 'Amuntai', 'tanggal_lahir' => '2000-01-30', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Halong', 'desa' => 'Muara Halayung', 'alamat' => 'Jl. Pahlawan No. 3', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Ilmu Administrasi Publik', 'ipk' => 3.92, 'semester' => 3, 'status' => 'piatu', 'applicants' => ['Beasiswa Pendidikan Kab. Balangan' => 'revisi', 'Beasiswa Komunikasi & Media' => 'verifikasi']],
            ['nama_lengkap' => 'Muhammad Rizky', 'tempat_lahir' => 'Banjarbaru', 'tanggal_lahir' => '2001-05-18', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Balangan', 'kecamatan' => 'Paringin Selatan', 'desa' => 'Bungaraya', 'alamat' => 'Jl. Kihajar Dewantara No. 7', 'kampus' => 'Universitas Islam Negeri Antasari', 'prodi' => 'Ekonomi Syariah', 'ipk' => 3.75, 'semester' => 4, 'status' => 'ayah', 'applicants' => ['Beasiswa Prestasi Kab. Balangan' => 'diterima', 'Beasiswa Keagamaan' => 'verifikasi']],
            ['nama_lengkap' => 'Aisyah Putri Ramadhani', 'tempat_lahir' => 'Kandangan', 'tanggal_lahir' => '2000-09-12', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Lampihong', 'desa' => 'Kayu Batu', 'alamat' => 'Jl. Meranti No. 14', 'kampus' => 'Universitas Islam Negeri Antasari', 'prodi' => 'Pendidikan Agama Islam', 'ipk' => 3.88, 'semester' => 5, 'status' => 'yatimPiatu', 'applicants' => ['Beasiswa Prestasi Kab. Balangan' => 'ditolak', 'Beasiswa Keagamaan' => 'diterima']],
            ['nama_lengkap' => 'Budi Santoso', 'tempat_lahir' => 'Banjarmasin', 'tanggal_lahir' => '2000-02-14', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Banjar', 'kecamatan' => 'Tebing Tinggi', 'desa' => 'Sungai Kupang', 'alamat' => 'Jl. Gatot Subroto No. 20', 'kampus' => 'Politeknik Negeri Banjarmasin', 'prodi' => 'Teknik Informatika', 'ipk' => 3.55, 'semester' => 3, 'status' => 'ayah', 'applicants' => ['Beasiswa Teknologi Informasi' => 'verifikasi']],
            ['nama_lengkap' => 'Dewi Kartika Sari', 'tempat_lahir' => 'Tanjung', 'tanggal_lahir' => '2001-04-08', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Paringin', 'desa' => 'Pulantan', 'alamat' => 'Jl. Padat Karya No. 11', 'kampus' => 'Politeknik Negeri Banjarmasin', 'prodi' => 'Sistem Informasi', 'ipk' => 3.70, 'semester' => 4, 'status' => 'yatim', 'applicants' => ['Beasiswa Teknologi Informasi' => 'diterima']],
            ['nama_lengkap' => 'Rina Marlina', 'tempat_lahir' => 'Kotabaru', 'tanggal_lahir' => '1999-12-01', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Kotabaru', 'kecamatan' => 'Paringin', 'desa' => 'Lok Batu', 'alamat' => 'Jl. Mawar No. 9', 'kampus' => 'Universitas Muhammadiyah Banjarmasin', 'prodi' => 'Kedokteran Umum', 'ipk' => 3.95, 'semester' => 7, 'status' => 'ayah', 'applicants' => ['Beasiswa Kesehatan' => 'diterima']],
            ['nama_lengkap' => 'Hendra Wijaya', 'tempat_lahir' => 'Marabahan', 'tanggal_lahir' => '2000-06-20', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Banjar', 'kecamatan' => 'Halong', 'desa' => 'Batumapai', 'alamat' => 'Jl. Melati No. 4', 'kampus' => 'Universitas Muhammadiyah Banjarmasin', 'prodi' => 'Kesehatan Masyarakat', 'ipk' => 3.30, 'semester' => 5, 'status' => 'yatim', 'applicants' => ['Beasiswa Kesehatan' => 'verifikasi']],
            ['nama_lengkap' => 'Fitriani Rahmawati', 'tempat_lahir' => 'Paringin', 'tanggal_lahir' => '2001-08-10', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Banua Lawas', 'desa' => 'Muara Halayung', 'alamat' => 'Jl. Kenanga No. 16', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Ilmu Komunikasi', 'ipk' => 3.60, 'semester' => 3, 'status' => 'piatu', 'applicants' => ['Beasiswa Komunikasi & Media' => 'diterima', 'Beasiswa Pendidikan Kab. Balangan' => 'ditolak']],
            ['nama_lengkap' => 'Andi Prasetyo', 'tempat_lahir' => 'Batulicin', 'tanggal_lahir' => '2000-10-25', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Tanah Bumbu', 'kecamatan' => 'Paringin Selatan', 'desa' => 'Kayu Batu', 'alamat' => 'Jl. Veteran No. 22', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Teknik Sipil', 'ipk' => 3.40, 'semester' => 6, 'status' => 'ayah', 'applicants' => ['Beasiswa Unggulan Teknik' => 'verifikasi']],
        ];

        return array_map(function ($row) use ($prodi) {
            $statusData = match ($row['status']) {
                'yatim' => $this->yatim($row),
                'piatu' => $this->piatu($row),
                'yatimPiatu' => $this->yatimPiatu($row),
                default => $this->ayah($row),
            };

            return array_merge($row, $statusData, [
                'prodi' => $prodi($row['kampus'], $row['prodi']),
            ]);
        }, $rows);
    }
}
