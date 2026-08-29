<?php

namespace Database\Seeders;

use App\Models\Scholarship;
use Illuminate\Database\Seeder;

class ScholarshipSeeder extends Seeder
{
    public function run(): void
    {
        $scholarships = [
            [
                'nama' => 'Beasiswa Pendidikan Kab. Balangan',
                'kampus' => 'Universitas Lambung Mangkurat',
                'kuota' => 50,
                'tingkat_gelar' => 'S1',
                'cakupan' => 'penuh',
                'batas_waktu' => now()->addMonths(3),
                'deskripsi' => 'Beasiswa penuh dari Pemerintah Kabupaten Balangan untuk studi S1 di Universitas Lambung Mangkurat. Termasuk biaya kuliah, biaya hidup, dan asrama.',
                'persyaratan' => '1. Warga Kabupaten Balangan
2. Lulus SNBP/SNBT
3. IPK minimal 3.0
4. Belum menikah',
                'status' => 'aktif',
                'fakultas' => [
                    'Fakultas Teknik' => ['Teknik Informatika', 'Teknik Elektro', 'Teknik Sipil'],
                    'Fakultas Ekonomi' => ['Manajemen', 'Akuntansi'],
                    'Fakultas Ilmu Sosial dan Ilmu Politik' => ['Ilmu Administrasi Publik', 'Ilmu Komunikasi'],
                ],
            ],
            [
                'nama' => 'Beasiswa Prestasi Kab. Balangan',
                'kampus' => 'Universitas Islam Negeri Antasari',
                'kuota' => 30,
                'tingkat_gelar' => 'S1',
                'cakupan' => 'sebagian',
                'batas_waktu' => now()->addMonths(2),
                'deskripsi' => 'Beasiswa sebagian untuk pelajar berprestasi dari Kabupaten Balangan. Tunjangan biaya kuliah bulanan.',
                'persyaratan' => '1. Warga Kabupaten Balangan
2. Lulus seleksi masuk
3. IPK minimal 3.0',
                'status' => 'aktif',
                'fakultas' => [
                    'Fakultas Tarbiyah' => ['Pendidikan Agama Islam', 'Pendidikan Bahasa Arab'],
                    'Fakultas Ekonomi dan Bisnis Islam' => ['Ekonomi Syariah', 'Perbankan Syariah'],
                ],
            ],
            [
                'nama' => 'Beasiswa Teknologi Informasi',
                'kampus' => 'Politeknik Negeri Banjarmasin',
                'kuota' => 20,
                'tingkat_gelar' => 'S1',
                'cakupan' => 'penuh',
                'batas_waktu' => now()->addMonths(4),
                'deskripsi' => 'Beasiswa penuh untuk studi di program studi teknologi informasi. Termasuk biaya kuliah dan sertifikasi.',
                'persyaratan' => '1. Warga Kabupaten Balangan
2. Jurusan IT atau sejenisnya
3. IPK minimal 3.0',
                'status' => 'aktif',
                'fakultas' => [
                    'Teknologi Informasi' => ['Teknik Informatika', 'Sistem Informasi', 'Teknik Komputer'],
                ],
            ],
            [
                'nama' => 'Beasiswa Kesehatan',
                'kampus' => 'Universitas Muhammadiyah Banjarmasin',
                'kuota' => 15,
                'tingkat_gelar' => 'S1',
                'cakupan' => 'penuh',
                'batas_waktu' => now()->addMonths(1),
                'deskripsi' => 'Beasiswa penuh untuk studi kedokteran dan keperawatan. Termasuk biaya kuliah, asrama, dan praktikum.',
                'persyaratan' => '1. Warga Kabupaten Balangan
2. Lulus seleksi masuk
3. IPK minimal 3.5
4. Surat kesehatan',
                'status' => 'aktif',
                'fakultas' => [
                    'Fakultas Kedokteran' => ['Kedokteran Umum'],
                    'Fakultas Kesehatan Masyarakat' => ['Kesehatan Masyarakat', 'Gizi'],
                ],
            ],
        ];

        foreach ($scholarships as $data) {
            $fakultasData = $data['fakultas'] ?? [];
            unset($data['fakultas']);

            $scholarship = Scholarship::create($data);

            foreach ($fakultasData as $fakultasNama => $prodiList) {
                $fakultas = $scholarship->fakultas()->create(['nama' => $fakultasNama]);
                foreach ($prodiList as $prodiNama) {
                    $fakultas->prodi()->create(['nama' => $prodiNama]);
                }
            }
        }
    }
}
