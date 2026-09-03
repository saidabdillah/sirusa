<?php

namespace Database\Seeders;

use App\Models\Kampus;
use Illuminate\Database\Seeder;

class KampusSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Universitas Lambung Mangkurat' => [
                'Fakultas Teknik' => ['Teknik Informatika', 'Teknik Elektro', 'Teknik Sipil'],
                'Fakultas Ekonomi dan Bisnis' => ['Manajemen', 'Akuntansi'],
                'Fakultas Ilmu Sosial dan Ilmu Politik' => ['Ilmu Administrasi Publik', 'Ilmu Komunikasi'],
            ],
            'Universitas Islam Negeri Antasari' => [
                'Fakultas Tarbiyah dan Keguruan' => ['Pendidikan Agama Islam', 'Pendidikan Bahasa Arab'],
                'Fakultas Ekonomi dan Bisnis Islam' => ['Ekonomi Syariah', 'Perbankan Syariah'],
            ],
            'Politeknik Negeri Banjarmasin' => [
                'Teknologi Informasi' => ['Teknik Informatika', 'Sistem Informasi', 'Teknik Komputer'],
            ],
            'Universitas Muhammadiyah Banjarmasin' => [
                'Fakultas Kedokteran' => ['Kedokteran Umum'],
                'Fakultas Kesehatan Masyarakat' => ['Kesehatan Masyarakat', 'Gizi'],
            ],
        ];

        foreach ($data as $namaKampus => $fakultasList) {
            $kampus = Kampus::firstOrCreate(['nama_kampus' => $namaKampus]);

            foreach ($fakultasList as $namaFakultas => $prodiList) {
                $fakultas = $kampus->fakultas()->firstOrCreate(['nama' => $namaFakultas]);

                foreach ($prodiList as $namaProdi) {
                    $fakultas->prodi()->firstOrCreate(['nama' => $namaProdi]);
                }
            }
        }
    }
}
