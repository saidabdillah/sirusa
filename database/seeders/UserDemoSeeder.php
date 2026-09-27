<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Fakultas;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Satu akun mahasiswa (`role: user`) yang datanya sudah terisi penuh, supaya
 * bisa langsung dipakai untuk menguji alur login -> profil -> verifikasi ->
 * pendaftaran tanpa harus mengetik data manual.
 *
 * Credentials: username `user`, NIK `6300000000000001`, password `password`.
 *
 * Sifat penting:
 *  - Idempoten. Dipakai `updateOrCreate` dengan kunci yang stabil, jadi
 *    menjalankan seeder berkali-kali tidak menduplikasi akun, profil,
 *    pendaftar, atau role.
 *  - Tidak menabrak akun admin. Kunci `users` adalah `username`, yang berbeda
 *    dari username admin (`superadmin`, `kesra`, `kampus`, `capil`).
 *  - Butuh master data. Kalau kampus/prodi/beasiswa belum ada, seeder
 *    `KampusSeeder` dan `ScholarshipSeeder` dipanggil sendiri, sehingga
 *    seeder ini bisa dijalankan sendiri tanpa urutan tertentu.
 */
class UserDemoSeeder extends Seeder
{
    private const USERNAME = 'user';

    private const NIK = '6300000000000001';

    private const EMAIL = 'user@sirusa.test';

    private const PASSWORD = 'password';

    private const NAMA_KAMPUS = 'Universitas Lambung Mangkurat';

    private const NAMA_FAKULTAS = 'Fakultas Teknik';

    private const NAMA_PRODI = 'Teknik Informatika';

    public function run(): void
    {
        // `Role::firstOrCreate` -- kalau `user` sudah dibuat oleh seeder lain
        // (mis. `DatabaseSeeder`), role itu yang dipakai, bukan duplikat.
        $role = Role::firstOrCreate(['name' => 'user']);

        $prodi = $this->prodi();

        $user = User::updateOrCreate(
            ['username' => self::USERNAME],
            [
                'email' => self::EMAIL,
                // Cast `hashed` pada model `User` yang bertanggung jawab
                // menghashing-nya, jadi jangan `Hash::make()` di sini atau
                // hasilnya dobel-hash.
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
                'status' => 'aktif',
            ]
        );

        $user->roles()->sync([$role->id]);

        $profile = $this->profile($user, $prodi);

        // Status `verifikasi` (bukan `diterima`) supaya akun demo melewati
        // gerbang "sudah disetujui Capil" tanpa menunggu keputusan manual,
        // tapi tetap membuka jalan pendaftaran beasiswa.
        $this->pendaftar($user, $profile, $this->beasiswaAktif());
    }

    private function prodi(): Prodi
    {
        if (! Kampus::where('nama_kampus', self::NAMA_KAMPUS)->exists()) {
            $this->call(KampusSeeder::class);
        }

        $kampus = Kampus::firstOrCreate(['nama_kampus' => self::NAMA_KAMPUS]);

        $fakultas = Fakultas::firstOrCreate([
            'kampus_id' => $kampus->id,
            'nama' => self::NAMA_FAKULTAS,
        ]);

        return Prodi::firstOrCreate([
            'fakultas_id' => $fakultas->id,
            'nama' => self::NAMA_PRODI,
        ]);
    }

    private function profile(User $user, Prodi $prodi): UserProfile
    {
        return UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lengkap' => 'User Demo',
                'nik' => self::NIK,
                'nim' => '2465100001',
                'no_kk' => '6300000000000002',
                'tempat_lahir' => 'Paringin',
                'tanggal_lahir' => '2004-01-15',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Islam',
                'telepon' => '081234567890',
                'alamat' => 'Jl. Merdeka No. 10',
                'provinsi' => 'Kalimantan Selatan',
                'kabupaten_kota' => 'Kabupaten Balangan',
                'kecamatan' => 'Paringin',
                'desa_kelurahan' => 'Paringin Kurdak',
                'prodi_id' => $prodi->id,
                'ipk' => '3.45',
                'semester' => 5,
                'ukt' => '1500000',
                'desil' => 2,
                'ikut_kk' => 'ayah',
                'kk_ikut_wali' => 0,
                'nama_ayah' => 'Bapak Demo',
                'pekerjaan_ayah' => 'Petani',
                'nik_ayah' => '6300000000000003',
                'nama_ibu' => 'Ibu Demo',
                'pekerjaan_ibu' => 'Tidak Bekerja',
                'nik_ibu' => '6300000000000004',
                'nama_wali' => null,
                'pekerjaan_wali' => null,
                'hubungan_wali' => null,
                'nik_wali' => null,
                // `verif_*` sengaja tidak disentuh: nilainya `menunggu` di level
                // kolom, jadi akun demo mulai dari kondisi netral dan bukan
                // `terverifikasi`.
            ]
        );
    }

    private function beasiswaAktif(): ?Scholarship
    {
        if (! Scholarship::where('status', 'aktif')->exists()) {
            $this->call(ScholarshipSeeder::class);
        }

        $beasiswa = Scholarship::where('status', 'aktif')->orderBy('id')->first();

        if (! $beasiswa) {
            $this->command?->warn('Tidak ada beasiswa aktif; akun demo dibuat tanpa pendaftar.');
        }

        return $beasiswa;
    }

    private function pendaftar(User $user, UserProfile $profile, ?Scholarship $beasiswa): void
    {
        if (! $beasiswa) {
            return;
        }

        $prodi = $profile->prodi;

        // Ada unique key `pendaftar (user_id, beasiswa_id)`, jadi aman diulang.
        Applicant::updateOrCreate(
            [
                'user_id' => $user->id,
                'beasiswa_id' => $beasiswa->id,
            ],
            [
                'fakultas' => $prodi?->fakultas?->nama,
                'prodi' => $prodi?->nama,
                'ipk' => $profile->ipk,
                'semester' => $profile->semester,
                'status' => 'verifikasi',
                'catatan' => null,
            ]
        );
    }
}
