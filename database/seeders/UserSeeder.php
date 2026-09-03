<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            [
                'username' => 'ahmad',
                'email' => 'ahmad@sirusa.com',
                'nama_lengkap' => 'Ahmad Fauzi',
                'nik' => '6303010101950001',
                'tempat_lahir' => 'Banjarmasin',
                'tanggal_lahir' => '1995-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Islam',
                'telepon' => '081234567890',
                'alamat' => 'Jl. A. Yani KM 5.5',
                'provinsi' => 'Kalimantan Selatan',
                'kabupaten_kota' => 'Banjarmasin',
                'kecamatan' => 'Banjarmasin Timur',
                'desa_kelurahan' => 'Pekapuran Laut',
                'prodi' => 'Teknik Informatika',
                'kampus' => 'Universitas Lambung Mangkurat',
                'ipk' => 3.75,
                'semester' => 5,
                'beasiswa' => 'Beasiswa Pendidikan Kab. Balangan',
                'status' => 'diterima',
            ],
            [
                'username' => 'siti',
                'email' => 'siti@sirusa.com',
                'nama_lengkap' => 'Siti Aminah',
                'nik' => '6302010101960002',
                'tempat_lahir' => 'Balangan',
                'tanggal_lahir' => '1996-03-15',
                'jenis_kelamin' => 'Perempuan',
                'agama' => 'Islam',
                'telepon' => '081298765432',
                'alamat' => 'Jl. Pangeran Antasari',
                'provinsi' => 'Kalimantan Selatan',
                'kabupaten_kota' => 'Balangan',
                'kecamatan' => 'Awayan',
                'desa_kelurahan' => 'Pulantan',
                'prodi' => 'Ekonomi Syariah',
                'kampus' => 'Universitas Islam Negeri Antasari',
                'ipk' => 3.85,
                'semester' => 4,
                'beasiswa' => 'Beasiswa Prestasi Kab. Balangan',
                'status' => 'verifikasi',
            ],
            [
                'username' => 'budi',
                'email' => 'budi@sirusa.com',
                'nama_lengkap' => 'Budi Santoso',
                'nik' => '6303010101970003',
                'tempat_lahir' => 'Banjarbaru',
                'tanggal_lahir' => '1997-07-20',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Islam',
                'telepon' => '081355667788',
                'alamat' => 'Jl. Ahmad Yani',
                'provinsi' => 'Kalimantan Selatan',
                'kabupaten_kota' => 'Banjarbaru',
                'kecamatan' => 'Landasan Ulin',
                'desa_kelurahan' => 'Sungai Ulin',
                'prodi' => 'Sistem Informasi',
                'kampus' => 'Politeknik Negeri Banjarmasin',
                'ipk' => 3.60,
                'semester' => 3,
                'beasiswa' => 'Beasiswa Teknologi Informasi',
                'status' => 'verifikasi',
            ],
        ];

        foreach ($users as $data) {
            $prodi = Prodi::query()
                ->whereHas('fakultas.kampus', fn ($query) => $query->where('nama_kampus', $data['kampus']))
                ->where('nama', $data['prodi'])
                ->first();

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'username' => $data['username'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'status' => 'aktif',
                ]
            );
            $user->assignRole('user');

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap' => $data['nama_lengkap'],
                    'nik' => $data['nik'],
                    'tempat_lahir' => $data['tempat_lahir'],
                    'tanggal_lahir' => $data['tanggal_lahir'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'agama' => $data['agama'],
                    'telepon' => $data['telepon'],
                    'alamat' => $data['alamat'],
                    'provinsi' => $data['provinsi'],
                    'kabupaten_kota' => $data['kabupaten_kota'],
                    'kecamatan' => $data['kecamatan'],
                    'desa_kelurahan' => $data['desa_kelurahan'],
                    'prodi_id' => $prodi?->id,
                    'ipk' => $data['ipk'],
                    'semester' => $data['semester'],
                    'status_orang_tua' => 'Lengkap',
                    'nama_ayah' => 'Ayah '.$data['nama_lengkap'],
                    'status_ayah' => 'Hidup',
                    'pekerjaan_ayah' => 'Wiraswasta',
                    'penghasilan_ayah' => '1-3jt',
                    'nik_ayah' => substr($data['nik'], 0, 16),
                    'nama_ibu' => 'Ibu '.$data['nama_lengkap'],
                    'status_ibu' => 'Hidup',
                    'pekerjaan_ibu' => 'Petani',
                    'penghasilan_ibu' => '< 1jt',
                    'nik_ibu' => substr($data['nik'], 0, 16),
                ]
            );

            $scholarship = Scholarship::query()->where('nama', $data['beasiswa'])->first();

            if ($scholarship) {
                Applicant::updateOrCreate(
                    ['user_id' => $user->id, 'beasiswa_id' => $scholarship->id],
                    [
                        'fakultas' => $scholarship->fakultas()->whereHas('prodi', fn ($query) => $query->where('nama', $data['prodi']))->first()?->nama,
                        'prodi' => $data['prodi'],
                        'ipk' => $data['ipk'],
                        'semester' => $data['semester'],
                        'status' => $data['status'],
                    ]
                );
            }
        }
    }
}
