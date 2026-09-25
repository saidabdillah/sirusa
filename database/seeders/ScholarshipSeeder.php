<?php

namespace Database\Seeders;

use App\Models\Fakultas;
use App\Models\Kampus;
use App\Models\Scholarship;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ScholarshipSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedActiveWithPengumuman();
        $this->seedActiveWithoutPengumuman();
        $this->seedInactive();
    }

    private function seedActiveWithPengumuman(): void
    {
        $data = [
            [
                'nama' => 'Beasiswa Pendidikan Kab. Balangan',
                'kampus' => 'Universitas Lambung Mangkurat',
                'kuota' => 50,
                'tingkat_gelar' => 'S1',
                'tanggal_mulai' => now()->subMonths(2),
                'tanggal_selesai' => now()->subMonth(),
                'ipk_minimal' => 3.00,
                'semester_minimal' => 3,
                'deskripsi' => 'Beasiswa penuh dari Pemerintah Kabupaten Balangan untuk studi S1. Termasuk biaya kuliah, biaya hidup, dan asrama.',
                'persyaratan' => "1. Warga Kabupaten Balangan\n2. Lulus SNBP/SNBT\n3. IPK minimal 3.0\n4. Belum menikah",
                'status' => 'aktif',
                'prodi' => ['Teknik Informatika', 'Manajemen', 'Akuntansi', 'Ilmu Administrasi Publik'],
            ],
            [
                'nama' => 'Beasiswa Prestasi Kab. Balangan',
                'kampus' => 'Universitas Islam Negeri Antasari',
                'kuota' => 30,
                'tingkat_gelar' => 'S1',
                'tanggal_mulai' => now()->subMonths(2),
                'tanggal_selesai' => now()->subWeek(),
                'ipk_minimal' => 3.50,
                'semester_minimal' => 4,
                'deskripsi' => 'Beasiswa sebagian untuk pelajar berprestasi dari Kabupaten Balangan.',
                'persyaratan' => "1. Warga Kabupaten Balangan\n2. Lulus seleksi masuk\n3. IPK minimal 3.5",
                'status' => 'aktif',
                'prodi' => ['Ekonomi Syariah', 'Pendidikan Agama Islam'],
            ],
        ];

        foreach ($data as $item) {
            $this->createScholarship($item);
        }
    }

    private function seedActiveWithoutPengumuman(): void
    {
        $data = [
            [
                'nama' => 'Beasiswa Teknologi Informasi',
                'kampus' => 'Politeknik Negeri Banjarmasin',
                'kuota' => 20,
                'tingkat_gelar' => 'S1',
                'tanggal_mulai' => now()->subWeek(),
                'tanggal_selesai' => now()->addMonths(3),
                'ipk_minimal' => 3.00,
                'semester_minimal' => 3,
                'deskripsi' => 'Beasiswa penuh untuk studi di bidang teknologi informasi.',
                'persyaratan' => "1. Warga Kabupaten Balangan\n2. Jurusan IT\n3. IPK minimal 3.0",
                'status' => 'aktif',
                'prodi' => ['Teknik Informatika', 'Sistem Informasi', 'Teknik Komputer'],
            ],
            [
                'nama' => 'Beasiswa Kesehatan',
                'kampus' => 'Universitas Muhammadiyah Banjarmasin',
                'kuota' => 15,
                'tingkat_gelar' => 'S1',
                'tanggal_mulai' => now()->subWeek(),
                'tanggal_selesai' => now()->addMonths(2),
                'ipk_minimal' => 3.50,
                'semester_minimal' => 4,
                'deskripsi' => 'Beasiswa penuh untuk studi kedokteran dan keperawatan.',
                'persyaratan' => "1. Warga Kabupaten Balangan\n2. Lulus seleksi masuk\n3. IPK minimal 3.5",
                'status' => 'aktif',
                'prodi' => ['Kedokteran Umum', 'Kesehatan Masyarakat', 'Gizi'],
            ],
            [
                'nama' => 'Beasiswa Unggulan Teknik',
                'kampus' => 'Universitas Lambung Mangkurat',
                'kuota' => 25,
                'tingkat_gelar' => 'S1',
                'tanggal_mulai' => now()->subWeek(),
                'tanggal_selesai' => now()->addMonth(),
                'ipk_minimal' => 3.25,
                'semester_minimal' => 3,
                'deskripsi' => 'Beasiswa penuh untuk mahasiswa berprestasi di bidang teknik.',
                'persyaratan' => "1. Warga Kalimantan\n2. IPK minimal 3.25\n3. Aktif di kegiatan kampus",
                'status' => 'aktif',
                'prodi' => ['Teknik Informatika', 'Teknik Elektro', 'Teknik Sipil'],
            ],
            [
                'nama' => 'Beasiswa Sosial Ekonomi',
                'kampus' => 'Universitas Lambung Mangkurat',
                'kuota' => 40,
                'tingkat_gelar' => 'S1',
                'tanggal_mulai' => now()->subWeek(),
                'tanggal_selesai' => now()->addMonths(4),
                'ipk_minimal' => 2.50,
                'semester_minimal' => 2,
                'deskripsi' => 'Beasiswa sebagian untuk mahasiswa kurang mampu.',
                'persyaratan' => "1. Warga Kabupaten Balangan\n2. Surat tidak mampu\n3. IPK minimal 2.5",
                'status' => 'aktif',
                'prodi' => ['Manajemen', 'Akuntansi', 'Ilmu Administrasi Publik', 'Ilmu Komunikasi'],
            ],
            [
                'nama' => 'Beasiswa Komunikasi & Media',
                'kampus' => 'Universitas Lambung Mangkurat',
                'kuota' => 15,
                'tingkat_gelar' => 'S1',
                'tanggal_mulai' => now()->subWeek(),
                'tanggal_selesai' => now()->addMonths(2),
                'ipk_minimal' => 3.00,
                'semester_minimal' => 3,
                'deskripsi' => 'Beasiswa untuk mahasiswa fakultas ilmu sosial dan politik.',
                'persyaratan' => "1. IPK minimal 3.0\n2. Aktif di organisasi",
                'status' => 'aktif',
                'prodi' => ['Ilmu Administrasi Publik', 'Ilmu Komunikasi'],
            ],
            [
                'nama' => 'Beasiswa Keagamaan',
                'kampus' => 'Universitas Islam Negeri Antasari',
                'kuota' => 20,
                'tingkat_gelar' => 'S1',
                'tanggal_mulai' => now()->subWeek(),
                'tanggal_selesai' => now()->addMonths(3),
                'ipk_minimal' => 3.00,
                'semester_minimal' => 3,
                'deskripsi' => 'Beasiswa penuh untuk studi di bidang keagamaan.',
                'persyaratan' => "1. IPK minimal 3.0\n2. Aktif di kegiatan keagamaan",
                'status' => 'aktif',
                'prodi' => ['Pendidikan Agama Islam', 'Pendidikan Bahasa Arab', 'Perbankan Syariah'],
            ],
        ];

        foreach ($data as $item) {
            $this->createScholarship($item);
        }
    }

    private function seedInactive(): void
    {
        $this->createScholarship([
            'nama' => 'Beasiswa non-Aktif',
            'kampus' => 'Politeknik Negeri Banjarmasin',
            'kuota' => 10,
            'tingkat_gelar' => 'S1',
            'tanggal_mulai' => now()->subMonths(4),
            'tanggal_selesai' => now()->subMonths(2),
            'ipk_minimal' => 3.00,
            'semester_minimal' => 3,
            'deskripsi' => 'Beasiswa yang sudah tidak aktif.',
            'persyaratan' => 'Sudah tidak menerima pendaftaran.',
            'status' => 'non-aktif',
            'prodi' => ['Sistem Informasi'],
        ]);
    }

    private function createScholarship(array $data): void
    {
        $prodiNames = $data['prodi'] ?? [];
        unset($data['prodi']);

        $kampus = Kampus::where('nama_kampus', $data['kampus'])->first();

        if (! $kampus) {
            return;
        }

        $data['kampus_id'] = $kampus->id;

        $scholarship = Scholarship::firstOrCreate(
            ['nama' => $data['nama']],
            $data,
        );

        $this->syncSnapshot($scholarship, $prodiNames);
    }

    private function syncSnapshot(Scholarship $scholarship, array $prodiNames): void
    {
        if ($scholarship->fakultas()->count() > 0) {
            return;
        }

        $masterProdis = $this->getMasterProdis($scholarship->kampus_id, $prodiNames);

        $grouped = $masterProdis->groupBy('fakultas_id');

        foreach ($grouped as $items) {
            $fakultas = $items->first()->fakultas;
            $record = $scholarship->fakultas()->create(['nama' => $fakultas->nama]);
            $record->prodi()->createMany(
                $items->map(fn ($prodi) => ['nama' => $prodi->nama])->all()
            );
        }
    }

    private function getMasterProdis(?int $kampusId, array $prodiNames): Collection
    {
        return Fakultas::where('kampus_id', $kampusId)
            ->with('prodi')
            ->get()
            ->flatMap(fn (Fakultas $fak) => $fak->prodi)
            ->filter(fn ($prodi) => in_array($prodi->nama, $prodiNames))
            ->values();
    }
}
