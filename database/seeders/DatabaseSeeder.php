<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Buat roles
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'capil']);
        Role::firstOrCreate(['name' => 'kampus']);
        Role::firstOrCreate(['name' => 'kesra']);
        Role::firstOrCreate(['name' => 'user']);

        // Buat user super_admin
        $superAdminUser = User::firstOrCreate(
            ['email' => 'msaidabdillah18@gmail.com'],
            [
                'username' => 'superadmin',
                'password' => '12345678',
                'status' => 'aktif',
            ]
        );
        $superAdminUser->assignRole('super_admin');

        // Buat user admin kesra
        $adminKesra = User::firstOrCreate(
            ['email' => 'kesra@sirusa.com'],
            [
                'username' => 'kesra',
                'password' => '12345678',
                'status' => 'aktif',
            ]
        );
        $adminKesra->assignRole('kesra');

        // Buat user admin kampus
        $adminKampus = User::firstOrCreate(
            ['email' => 'kampus@sirusa.com'],
            [
                'username' => 'kampus',
                'password' => '12345678',
                'status' => 'aktif',
            ]
        );
        $adminKampus->assignRole('kampus');

        // Buat user admin capil
        $adminCapil = User::firstOrCreate(
            ['email' => 'capil@sirusa.com'],
            [
                'username' => 'capil',
                'password' => '12345678',
                'status' => 'aktif',
            ]
        );
        $adminCapil->assignRole('capil');

        // Seeder data demo (katalog kampus, beasiswa contoh, user mahasiswa)
        // dinonaktifkan — aktifkan kembali bila perlu data contoh:
        // $this->call(KampusSeeder::class);
        //
        // Tautkan demo admin kampus ke kampus ULM (idempoten) supaya verifikasi
        // kampus pada sesi demo hanya menampilkan data prodi miliknya.
        // $ulm = Kampus::query()->where('nama_kampus', 'Universitas Lambung Mangkurat')->first();
        // if ($ulm) {
        //     $adminKampus->update(['kampus_id' => $ulm->id]);
        // }
        // $this->call(ScholarshipSeeder::class);
        // $this->call(UserSeeder::class);

        // Menu & hak akses default — WAJIB tetap dijalankan: tanpa ini sidebar
        // kosong dan semua rute yang tercakup menu akan 403.
        $this->call(MenuSeeder::class);
    }
}
