<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // $this->call(KampusSeeder::class);
        // $this->call(ScholarshipSeeder::class);

        // Buat roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $user = Role::firstOrCreate(['name' => 'user']);

        // Buat permissions
        $manageUsers = Permission::firstOrCreate(['name' => 'manage-users']);
        $manageScholarships = Permission::firstOrCreate(['name' => 'manage-scholarships']);
        $manageApplicants = Permission::firstOrCreate(['name' => 'manage-applicants']);
        $viewScholarships = Permission::firstOrCreate(['name' => 'view-scholarships']);
        $applyScholarship = Permission::firstOrCreate(['name' => 'apply-scholarship']);

        // Assign permissions ke roles
        $superAdmin->syncPermissions([$manageUsers, $manageScholarships, $manageApplicants, $viewScholarships, $applyScholarship]);
        $admin->syncPermissions([$manageScholarships, $manageApplicants, $viewScholarships]);
        $user->syncPermissions([$viewScholarships, $applyScholarship]);

        // Buat user super_admin
        $superAdminUser = User::firstOrCreate(
            ['email' => 'msaidabdillah18@gmail.com'],
            [
                'username' => 'superadmin',
                'password' => 'password',
                'status' => 'aktif',
            ]
        );
        $superAdminUser->assignRole('super_admin');

        // Buat user admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@sirusa.com'],
            [
                'username' => 'admin',
                'password' => 'password',
                'status' => 'aktif',
            ]
        );
        $adminUser->assignRole('admin');

        // Buat 3 user demo dengan profil lengkap + pendaftar contoh
        // $this->call(UserSeeder::class);
    }
}
