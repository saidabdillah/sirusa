<?php

namespace Database\Seeders;

use App\Models\Kampus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Buat roles
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'catpil']);
        Role::firstOrCreate(['name' => 'kampus']);
        Role::firstOrCreate(['name' => 'kesra']);
        Role::firstOrCreate(['name' => 'user']);

        // Satu-satunya akun yang dibuat seeder: super admin. Akun staf lain
        // (kesra/kampus/catpil) sengaja TIDAK dibuat di sini -- akun staf dibuat
        // super admin lewat menu Pengguna, karena akun kampus wajib ditautkan ke
        // kampusnya masing-masing.
        //
        // Kunci idempotensinya `username` (bukan email): seeder versi lama memakai
        // email pribadi, dan kunci berbasis email akan membuat baris superadmin
        // BARU -- yang langsung ditolak unique key `users.username`. Email
        // disamakan terpisah supaya akun lama ikut pindah tanpa menyentuh kata
        // sandi yang mungkin sudah diganti pemiliknya.
        $superAdmin = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'email' => 'superadmin@email.com',
                'password' => '12345678',
                'status' => 'aktif',
            ]
        );

        if ($superAdmin->email !== 'superadmin@email.com') {
            $superAdmin->update(['email' => 'superadmin@email.com']);
        }

        $superAdmin->assignRole('super_admin');

        // Hapus sisa akun staf lama (kesra/kampus/catpil) yang dibuat seeder versi
        // sebelumnya. Pemetaan kampus admin demo sudah ikut terhapus bersama
        // akunnya, jadi tidak ada penautan `kampus_id` lagi di sini.
        $this->buangAkunStafLama();

        // Seeder data demo yang lain (50 user mahasiswa, jumlah tiap iterasi)
        // dinonaktifkan — aktifkan kembali bila perlu data contoh:
        // $this->call(UserSeeder::class);
        // Menu & hak akses default — WAJIB tetap dijalankan: tanpa ini sidebar
        // kosong dan semua rute yang tercakup menu akan 403.
        $this->call(MenuSeeder::class);

        // Permission aksi (read/create/edit/update/delete) untuk modul CRUD.
        // Dijalankan SETELAH MenuSeeder supaya role-nya sudah pasti ada.
        $this->call(PermissionSeeder::class);
    }

    /**
     * Buang akun staf bawaan seeder yang tidak lagi dibuat di sini.
     *
     * Daftarnya dipatok ke username akun seeder versi lama, BUKAN
     * "semua user selain superadmin": akun staf yang dibuat sendiri oleh super
     * admin (atau akun mahasiswa hasil `UserSeeder`) tidak boleh ikut terhapus
     * setiap kali seeder dijalankan. Akun yang sudah dipakai memutuskan
     * pendaftaran aman -- FK `pendaftar.diputuskan_oleh` memakai
     * `nullOnDelete()`, jadi riwayatnya tinggal kehilangan nama pemutusnya.
     */
    private function buangAkunStafLama(): void
    {
        $usernames = ['kesra', 'kampus', 'catpil'];

        $jumlah = User::query()
            ->whereIn('username', $usernames)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'user'))
            ->count();

        if ($jumlah === 0) {
            return;
        }

        // Hapus satu per satu. `DatabaseSeeder` memakai `WithoutModelEvents`, jadi
        // event `deleting` milik Spatie (yang biasanya mencabut baris
        // `model_has_roles`) TIDAK jalan -- role harus dicabut manual dulu supaya
        // tidak meninggalkan baris role yatim yang menunjuk user yang sudah hilang.
        $daftar = User::query()
            ->whereIn('username', $usernames)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'user'))
            ->get();

        $daftar->each(function (User $user): void {
            $user->roles()->detach();
            $user->delete();
        });

        // Cache permission/role dibuang supaya daftar akun di UI langsung segar.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info("Akun staf bawaan seeder lama dihapus: {$jumlah} (kesra/kampus/catpil).");
    }
}
