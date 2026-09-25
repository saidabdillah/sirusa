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

    private const TARGET_PENERIMA = 50;

    private const PENDIDIKAN_BEASISWA = 'Beasiswa Pendidikan Kab. Balangan';

    private const KAMPUS_ULM = 'Universitas Lambung Mangkurat';

    private const PRODI_PENDIDIKAN = ['Teknik Informatika', 'Manajemen', 'Akuntansi', 'Ilmu Administrasi Publik'];

    private array $namaLaki = ['Muhammad', 'Ahmad', 'Rizky', 'Budi', 'Andi', 'Sandi', 'Donny', 'Fajar', 'Hendra', 'Ilham', 'Joko', 'Kevin', 'Lukman', 'Nanda', 'Oki', 'Putra', 'Rahmat', 'Surya', 'Taufik', 'Yoga', 'Zainal', 'Arif', 'Bayu', 'Dimas', 'Eko'];

    private array $namaPerempuan = ['Siti', 'Nurul', 'Aisyah', 'Dewi', 'Rina', 'Fitri', 'Indah', 'Jasmine', 'Kartika', 'Laila', 'Mega', 'Nabila', 'Putri', 'Ratna', 'Sri', 'Tuti', 'Utami', 'Wulan', 'Yuni', 'Zahra', 'Anisa', 'Bunga', 'Citra', 'Dian', 'Elvira'];

    private array $namaBelakang = ['Pratama', 'Saputra', 'Ramadhan', 'Santoso', 'Wijaya', 'Marlina', 'Maharani', 'Putri', 'Kurniawan', 'Nugroho', 'Firmansyah', 'Hidayat', 'Permata', 'Anggraini', 'Lestari', 'Sulistyo', 'Wibowo', 'Prasetyo', 'Rahmawati', 'Haryanti', 'Gunawan', 'Siregar', 'Napitupulu', 'Saragih', 'Halim'];

    private array $tempatLahir = ['Paringin', 'Barabai', 'Banjarmasin', 'Amuntai', 'Kandangan', 'Tanjung', 'Marabahan', 'Batulicin', 'Kotabaru', 'Banjarbaru'];

    private array $kecamatanDesa = [
        ['kecamatan' => 'Awayan', 'desa' => 'Pulantan'],
        ['kecamatan' => 'Paringin', 'desa' => 'Batumapai'],
        ['kecamatan' => 'Awayan', 'desa' => 'Lok Batu'],
        ['kecamatan' => 'Halong', 'desa' => 'Muara Halayung'],
        ['kecamatan' => 'Paringin Selatan', 'desa' => 'Kayu Batu'],
        ['kecamatan' => 'Paringin', 'desa' => 'Bungaraya'],
        ['kecamatan' => 'Tebing Tinggi', 'desa' => 'Sungai Kupang'],
        ['kecamatan' => 'Awayan', 'desa' => 'Ambakiang'],
    ];

    private array $verifikasi = [
        'user01' => ['menunggu', 'setuju', 'setuju'],
        'user02' => ['setuju', 'setuju', 'menunggu'],
        'user03' => ['setuju', 'menunggu', 'menunggu'],
        'user04' => ['revisi', 'menunggu', 'menunggu'],
        'user05' => ['setuju', 'setuju', 'setuju'],
        'user06' => ['menunggu', 'menunggu', 'menunggu'],
        'user07' => ['setuju', 'menunggu', 'menunggu'],
        'user08' => ['setuju', 'setuju', 'setuju'],
        'user09' => ['setuju', 'setuju', 'setuju'],
        'user10' => ['menunggu', 'menunggu', 'menunggu'],
        'user11' => ['setuju', 'setuju', 'menunggu'],
        'user12' => ['menunggu', 'menunggu', 'menunggu'],
    ];

    public function run(): void
    {
        $scholarships = Scholarship::with(['fakultas.prodi', 'kampus'])->where('status', 'aktif')->get();
        $users = $this->userData();

        foreach ($users as $i => $data) {
            $number = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            $key = "user{$number}";

            $user = User::firstOrCreate(
                ['email' => "{$key}@sirusa.test"],
                [
                    'username' => $key,
                    'password' => '12345678',
                    'email_verified_at' => now(),
                    'status' => 'aktif',
                ],
            );
            $user->assignRole('user');

            $prodi = $data['prodi'];
            $fakultas = $prodi?->fakultas;
            $nik = $this->nik($number);
            $verif = $this->verifikasi[$key];

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap' => $data['nama_lengkap'],
                    'nik' => $nik,
                    'no_kk' => '6303'.str_pad((string) (200 + $i), 12, '0', STR_PAD_LEFT),
                    'nim' => $data['nim'] ?? null,
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
                    'ukt' => $data['ukt'],
                    'desil' => $data['desil'],
                    'ikut_kk' => $data['ikut_kk'],
                    'kk_ikut_wali' => $data['kk_ikut_wali'],
                    'nama_ayah' => $data['nama_ayah'],
                    'nik_ayah' => $data['nik_ayah'],
                    'pekerjaan_ayah' => $data['pekerjaan_ayah'],
                    'nama_ibu' => $data['nama_ibu'],
                    'nik_ibu' => $data['nik_ibu'],
                    'pekerjaan_ibu' => $data['pekerjaan_ibu'],
                    'nama_wali' => $data['nama_wali'] ?? null,
                    'nik_wali' => $data['nik_wali'] ?? null,
                    'hubungan_wali' => $data['hubungan_wali'] ?? null,
                    'pekerjaan_wali' => $data['pekerjaan_wali'] ?? null,
                    'foto_profil' => "profil/{$key}/foto_profil.jpg",
                    'dokumen_ktp' => "profil/{$key}/dokumen_ktp.jpg",
                    'dokumen_kk' => "profil/{$key}/dokumen_kk.jpg",
                    'dokumen_desil' => "profil/{$key}/dokumen_desil.jpg",
                    'dokumen_sktm' => "profil/{$key}/dokumen_sktm.jpg",
                    'dokumen_transkrip' => "profil/{$key}/dokumen_transkrip.pdf",
                    'dokumen_surat_aktif' => "profil/{$key}/dokumen_surat_aktif.pdf",
                    'dokumen_surat_pernyataan' => "profil/{$key}/dokumen_surat_pernyataan.pdf",
                    'dokumen_bukti_ukt' => "profil/{$key}/dokumen_bukti_ukt.pdf",
                    'ktp_ayah' => "profil/{$key}/ktp_ayah.jpg",
                    'ktp_ibu' => "profil/{$key}/ktp_ibu.jpg",
                    'ktp_wali' => $data['kk_ikut_wali'] ? "profil/{$key}/ktp_wali.jpg" : null,
                    'kk_wali' => $data['kk_ikut_wali'] ? "profil/{$key}/kk_wali.jpg" : null,
                    'verif_capil' => $verif[0],
                    'verif_kampus' => $verif[1],
                    'verif_kesra' => $verif[2],
                    'catatan_capil' => $verif[0] === 'revisi' ? 'NIK dan nama tidak sesuai dengan data KK' : null,
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

        $this->seedManyPenerima();
    }

    private function nik(string $number): string
    {
        return '6303'.str_pad($number, 12, '0', STR_PAD_LEFT);
    }

    private function seedManyPenerima(): void
    {
        $kampus = Kampus::with('fakultas.prodi')
            ->where('nama_kampus', self::KAMPUS_ULM)
            ->first();

        if (! $kampus) {
            return;
        }

        $allowedProdis = $kampus->fakultas->flatMap->prodi
            ->filter(fn ($prodi) => in_array($prodi->nama, self::PRODI_PENDIDIKAN))
            ->values();

        if ($allowedProdis->isEmpty()) {
            return;
        }

        $scholarship = Scholarship::where('nama', self::PENDIDIKAN_BEASISWA)->first();

        if (! $scholarship) {
            return;
        }

        $existing = $scholarship->penerima()->count();

        for ($n = 1; $n <= self::TARGET_PENERIMA - $existing; $n++) {
            $key = "penerima{$n}";
            $user = User::firstOrCreate(
                ['email' => "{$key}@sirusa.test"],
                [
                    'username' => $key,
                    'password' => '12345678',
                    'email_verified_at' => now(),
                    'status' => 'aktif',
                ],
            );
            $user->assignRole('user');

            $prodi = $allowedProdis[$n % $allowedProdis->count()];
            $fakultas = $prodi->fakultas;
            $gender = $n % 2 === 0 ? 'Laki-laki' : 'Perempuan';
            $nameIndex = (int) floor($n / 2);
            $firstName = $gender === 'Laki-laki'
                ? $this->namaLaki[$nameIndex % count($this->namaLaki)]
                : $this->namaPerempuan[$nameIndex % count($this->namaPerempuan)];
            $fullName = $firstName.' '.$this->namaBelakang[$n % count($this->namaBelakang)];
            $location = $this->kecamatanDesa[$n % count($this->kecamatanDesa)];
            $ipk = round(3 + (($n % 10) / 10), 2);
            $semester = 3 + ($n % 6);

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap' => $fullName,
                    'nik' => '6303'.str_pad((string) (1000 + $n), 12, '0', STR_PAD_LEFT),
                    'no_kk' => '6303'.str_pad((string) (3000 + $n), 12, '0', STR_PAD_LEFT),
                    'nim' => '2310'.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
                    'tempat_lahir' => $this->tempatLahir[$n % count($this->tempatLahir)],
                    'tanggal_lahir' => now()->subYears(21)->subDays($n)->toDateString(),
                    'jenis_kelamin' => $gender,
                    'agama' => 'Islam',
                    'telepon' => '0813'.str_pad((string) $n, 8, '0', STR_PAD_LEFT),
                    'alamat' => 'Jl. Pahlawan No. '.$n,
                    'provinsi' => 'Kalimantan Selatan',
                    'kabupaten_kota' => 'Balangan',
                    'kecamatan' => $location['kecamatan'],
                    'desa_kelurahan' => $location['desa'],
                    'prodi_id' => $prodi->id,
                    'ipk' => $ipk,
                    'semester' => $semester,
                    'ukt' => 2500000,
                    'desil' => ($n % 10) + 1,
                    'ikut_kk' => 'ayah',
                    'kk_ikut_wali' => false,
                    'nama_ayah' => 'Ayah '.$fullName,
                    'nik_ayah' => '6303'.str_pad((string) (4000 + $n), 12, '0', STR_PAD_LEFT),
                    'pekerjaan_ayah' => $this->pekerjaan[$n % count($this->pekerjaan)],
                    'nama_ibu' => 'Ibu '.$fullName,
                    'nik_ibu' => '6303'.str_pad((string) (5000 + $n), 12, '0', STR_PAD_LEFT),
                    'pekerjaan_ibu' => $this->pekerjaan[($n + 2) % count($this->pekerjaan)],
                    'foto_profil' => "profil/{$key}/foto_profil.jpg",
                    'dokumen_ktp' => "profil/{$key}/dokumen_ktp.jpg",
                    'dokumen_kk' => "profil/{$key}/dokumen_kk.jpg",
                    'dokumen_desil' => "profil/{$key}/dokumen_desil.jpg",
                    'dokumen_sktm' => "profil/{$key}/dokumen_sktm.jpg",
                    'dokumen_transkrip' => "profil/{$key}/dokumen_transkrip.pdf",
                    'dokumen_surat_aktif' => "profil/{$key}/dokumen_surat_aktif.pdf",
                    'dokumen_surat_pernyataan' => "profil/{$key}/dokumen_surat_pernyataan.pdf",
                    'dokumen_bukti_ukt' => "profil/{$key}/dokumen_bukti_ukt.pdf",
                    'ktp_ayah' => "profil/{$key}/ktp_ayah.jpg",
                    'ktp_ibu' => "profil/{$key}/ktp_ibu.jpg",
                    'verif_capil' => 'setuju',
                    'verif_kampus' => 'setuju',
                    'verif_kesra' => 'setuju',
                ],
            );

            Applicant::updateOrCreate(
                ['user_id' => $user->id, 'beasiswa_id' => $scholarship->id],
                [
                    'fakultas' => $fakultas->nama,
                    'prodi' => $prodi->nama,
                    'ipk' => $ipk,
                    'semester' => $semester,
                    'status' => 'diterima',
                ],
            );
        }
    }

    private function ayahFields(array $data): array
    {
        return [
            'nama_ayah' => 'Ayah '.$data['nama_lengkap'],
            'nik_ayah' => '6303010000000001',
            'pekerjaan_ayah' => $this->pekerjaan[array_rand($this->pekerjaan)],
            'nama_ibu' => 'Ibu '.$data['nama_lengkap'],
            'nik_ibu' => '6303010000000002',
            'pekerjaan_ibu' => $this->pekerjaan[array_rand($this->pekerjaan)],
            'nama_wali' => null,
            'nik_wali' => null,
            'hubungan_wali' => null,
            'pekerjaan_wali' => null,
        ];
    }

    private function ibuOnlyFields(array $data): array
    {
        return [
            'nama_ayah' => null,
            'nik_ayah' => null,
            'pekerjaan_ayah' => null,
            'nama_ibu' => 'Ibu '.$data['nama_lengkap'],
            'nik_ibu' => '6303010000000002',
            'pekerjaan_ibu' => $this->pekerjaan[array_rand($this->pekerjaan)],
            'nama_wali' => null,
            'nik_wali' => null,
            'hubungan_wali' => null,
            'pekerjaan_wali' => null,
        ];
    }

    private function ayahOnlyFields(array $data): array
    {
        return [
            'nama_ayah' => 'Ayah '.$data['nama_lengkap'],
            'nik_ayah' => '6303010000000001',
            'pekerjaan_ayah' => $this->pekerjaan[array_rand($this->pekerjaan)],
            'nama_ibu' => null,
            'nik_ibu' => null,
            'pekerjaan_ibu' => null,
            'nama_wali' => null,
            'nik_wali' => null,
            'hubungan_wali' => null,
            'pekerjaan_wali' => null,
        ];
    }

    private function waliOnlyFields(array $data): array
    {
        return [
            'nama_ayah' => null,
            'nik_ayah' => null,
            'pekerjaan_ayah' => null,
            'nama_ibu' => null,
            'nik_ibu' => null,
            'pekerjaan_ibu' => null,
            'nama_wali' => 'Wali '.$data['nama_lengkap'],
            'nik_wali' => '6303010000000003',
            'hubungan_wali' => 'Paman',
            'pekerjaan_wali' => $this->pekerjaan[array_rand($this->pekerjaan)],
        ];
    }

    private function ayah(array $data): array
    {
        return array_merge([
            'ikut_kk' => 'ayah',
            'kk_ikut_wali' => false,
        ], $this->ayahFields($data));
    }

    private function ibu(array $data): array
    {
        return array_merge([
            'ikut_kk' => 'ibu',
            'kk_ikut_wali' => false,
        ], $this->ayahFields($data));
    }

    private function ceraiAyah(array $data): array
    {
        return array_merge([
            'ikut_kk' => 'ayah',
            'kk_ikut_wali' => false,
        ], $this->ayahFields($data));
    }

    private function ceraiIbu(array $data): array
    {
        return array_merge([
            'ikut_kk' => 'ibu',
            'kk_ikut_wali' => false,
        ], $this->ayahFields($data));
    }

    private function yatim(array $data): array
    {
        return array_merge([
            'ikut_kk' => 'ibu',
            'kk_ikut_wali' => false,
        ], $this->ibuOnlyFields($data));
    }

    private function piatu(array $data): array
    {
        return array_merge([
            'ikut_kk' => 'ayah',
            'kk_ikut_wali' => false,
        ], $this->ayahOnlyFields($data));
    }

    private function kkWali(array $data): array
    {
        return array_merge([
            'ikut_kk' => 'wali',
            'kk_ikut_wali' => true,
        ], $this->waliOnlyFields($data));
    }

    private function userData(): array
    {
        $prodi = function (string $kampusNama, string $prodiNama): ?object {
            $k = Kampus::where('nama_kampus', $kampusNama)->with('fakultas.prodi')->first();

            return $k?->fakultas->flatMap->prodi->firstWhere('nama', $prodiNama);
        };

        $rows = [
            ['key' => 'user01', 'nama_lengkap' => 'Muhammad Rizki Pratama', 'tempat_lahir' => 'Paringin', 'tanggal_lahir' => '2001-03-15', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Balangan', 'kecamatan' => 'Awayan', 'desa' => 'Pulantan', 'alamat' => 'Jl. Antasari No. 12', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Teknik Informatika', 'ipk' => 3.80, 'semester' => 5, 'ukt' => 2500000, 'desil' => 3, 'status' => 'ayah', 'applicants' => ['Beasiswa Pendidikan Kab. Balangan' => 'diterima', 'Beasiswa Unggulan Teknik' => 'diterima']],
            ['key' => 'user02', 'nama_lengkap' => 'Siti Nurhaliza', 'tempat_lahir' => 'Barabai', 'tanggal_lahir' => '2001-07-22', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Paringin', 'desa' => 'Batumapai', 'alamat' => 'Jl. Merdeka No. 5', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Manajemen', 'ipk' => 3.65, 'semester' => 4, 'ukt' => 2000000, 'desil' => 5, 'status' => 'ayah', 'applicants' => ['Beasiswa Pendidikan Kab. Balangan' => 'verifikasi', 'Beasiswa Sosial Ekonomi' => 'diterima']],
            ['key' => 'user03', 'nama_lengkap' => 'Ahmad Fadillah', 'tempat_lahir' => 'Banjarmasin', 'tanggal_lahir' => '1999-11-05', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Banjar', 'kecamatan' => 'Awayan', 'desa' => 'Lok Batu', 'alamat' => 'Jl. Sudirman No. 8', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Teknik Elektro', 'ipk' => 3.45, 'semester' => 6, 'ukt' => 3000000, 'desil' => 7, 'status' => 'ayah', 'applicants' => ['Beasiswa Unggulan Teknik' => 'verifikasi']],
            ['key' => 'user04', 'nama_lengkap' => 'Nurul Aisyah', 'tempat_lahir' => 'Amuntai', 'tanggal_lahir' => '2000-01-30', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Halong', 'desa' => 'Muara Halayung', 'alamat' => 'Jl. Pahlawan No. 3', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Ilmu Administrasi Publik', 'ipk' => 3.92, 'semester' => 3, 'ukt' => 1500000, 'desil' => 2, 'status' => 'ayah', 'applicants' => ['Beasiswa Pendidikan Kab. Balangan' => 'verifikasi', 'Beasiswa Komunikasi & Media' => 'verifikasi']],
            ['key' => 'user05', 'nama_lengkap' => 'Muhammad Rizky', 'tempat_lahir' => 'Banjarbaru', 'tanggal_lahir' => '2001-05-18', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Balangan', 'kecamatan' => 'Paringin Selatan', 'desa' => 'Bungaraya', 'alamat' => 'Jl. Kihajar Dewantara No. 7', 'kampus' => 'Universitas Islam Negeri Antasari', 'prodi' => 'Ekonomi Syariah', 'ipk' => 3.75, 'semester' => 4, 'ukt' => 2200000, 'desil' => 4, 'status' => 'ibu', 'applicants' => ['Beasiswa Prestasi Kab. Balangan' => 'diterima', 'Beasiswa Keagamaan' => 'verifikasi']],
            ['key' => 'user06', 'nama_lengkap' => 'Aisyah Putri Ramadhani', 'tempat_lahir' => 'Kandangan', 'tanggal_lahir' => '2000-09-12', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Lampihong', 'desa' => 'Kayu Batu', 'alamat' => 'Jl. Meranti No. 14', 'kampus' => 'Universitas Islam Negeri Antasari', 'prodi' => 'Pendidikan Agama Islam', 'ipk' => 3.88, 'semester' => 5, 'ukt' => 1800000, 'desil' => 1, 'status' => 'kkWali', 'applicants' => ['Beasiswa Prestasi Kab. Balangan' => 'verifikasi', 'Beasiswa Keagamaan' => 'diterima']],
            ['key' => 'user07', 'nama_lengkap' => 'Budi Santoso', 'tempat_lahir' => 'Banjarmasin', 'tanggal_lahir' => '2000-02-14', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Banjar', 'kecamatan' => 'Tebing Tinggi', 'desa' => 'Sungai Kupang', 'alamat' => 'Jl. Gatot Subroto No. 20', 'kampus' => 'Politeknik Negeri Banjarmasin', 'prodi' => 'Teknik Informatika', 'ipk' => 3.55, 'semester' => 3, 'ukt' => 2600000, 'desil' => 8, 'status' => 'piatu', 'applicants' => ['Beasiswa Teknologi Informasi' => 'verifikasi']],
            ['key' => 'user08', 'nama_lengkap' => 'Dewi Kartika Sari', 'tempat_lahir' => 'Tanjung', 'tanggal_lahir' => '2001-04-08', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Paringin', 'desa' => 'Pulantan', 'alamat' => 'Jl. Padat Karya No. 11', 'kampus' => 'Politeknik Negeri Banjarmasin', 'prodi' => 'Sistem Informasi', 'ipk' => 3.70, 'semester' => 4, 'ukt' => 1900000, 'desil' => 6, 'status' => 'ayah', 'applicants' => ['Beasiswa Teknologi Informasi' => 'diterima']],
            ['key' => 'user09', 'nama_lengkap' => 'Rina Marlina', 'tempat_lahir' => 'Kotabaru', 'tanggal_lahir' => '1999-12-01', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Kotabaru', 'kecamatan' => 'Paringin', 'desa' => 'Lok Batu', 'alamat' => 'Jl. Mawar No. 9', 'kampus' => 'Universitas Muhammadiyah Banjarmasin', 'prodi' => 'Kedokteran Umum', 'ipk' => 3.95, 'semester' => 7, 'ukt' => 8000000, 'desil' => 9, 'status' => 'kkWali', 'applicants' => ['Beasiswa Kesehatan' => 'diterima']],
            ['key' => 'user10', 'nama_lengkap' => 'Hendra Wijaya', 'tempat_lahir' => 'Marabahan', 'tanggal_lahir' => '2000-06-20', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Banjar', 'kecamatan' => 'Halong', 'desa' => 'Batumapai', 'alamat' => 'Jl. Melati No. 4', 'kampus' => 'Universitas Muhammadiyah Banjarmasin', 'prodi' => 'Kesehatan Masyarakat', 'ipk' => 3.30, 'semester' => 5, 'ukt' => 2100000, 'desil' => 6, 'status' => 'yatim', 'applicants' => ['Beasiswa Kesehatan' => 'verifikasi']],
            ['key' => 'user11', 'nama_lengkap' => 'Fitriani Rahmawati', 'tempat_lahir' => 'Paringin', 'tanggal_lahir' => '2001-08-10', 'jenis_kelamin' => 'Perempuan', 'kabupaten' => 'Balangan', 'kecamatan' => 'Banua Lawas', 'desa' => 'Muara Halayung', 'alamat' => 'Jl. Kenanga No. 16', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Ilmu Komunikasi', 'ipk' => 3.60, 'semester' => 3, 'ukt' => 2300000, 'desil' => 4, 'status' => 'cerai-ibu', 'applicants' => ['Beasiswa Komunikasi & Media' => 'diterima', 'Beasiswa Pendidikan Kab. Balangan' => 'verifikasi']],
            ['key' => 'user12', 'nama_lengkap' => 'Andi Prasetyo', 'tempat_lahir' => 'Batulicin', 'tanggal_lahir' => '2000-10-25', 'jenis_kelamin' => 'Laki-laki', 'kabupaten' => 'Tanah Bumbu', 'kecamatan' => 'Paringin Selatan', 'desa' => 'Kayu Batu', 'alamat' => 'Jl. Veteran No. 22', 'kampus' => 'Universitas Lambung Mangkurat', 'prodi' => 'Teknik Sipil', 'ipk' => 3.40, 'semester' => 6, 'ukt' => 2700000, 'desil' => 5, 'status' => 'cerai-ayah', 'applicants' => ['Beasiswa Unggulan Teknik' => 'verifikasi']],
        ];

        return array_map(function ($row, $i) use ($prodi) {
            $statusData = match ($row['status']) {
                'ibu' => $this->ibu($row),
                'cerai-ayah' => $this->ceraiAyah($row),
                'cerai-ibu' => $this->ceraiIbu($row),
                'yatim' => $this->yatim($row),
                'piatu' => $this->piatu($row),
                'kkWali' => $this->kkWali($row),
                default => $this->ayah($row),
            };

            return array_merge($row, $statusData, [
                'nim' => '2010'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'prodi' => $prodi($row['kampus'], $row['prodi']),
            ]);
        }, $rows, array_keys($rows));
    }
}
