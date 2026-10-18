<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Opsi "Wali" menjadi "Famili Lain".
 *
 * Dua hal yang diubah bersamaan (keputusan pengguna: "ubah label dan nilainya
 * sekaligus di database"):
 *
 *  1. Nilai enum `ikut_kk`: 'wali' -> 'famili_lain'.
 *  2. Nama kolom boolean `kk_ikut_wali` -> `kk_ikut_famili_lain`, supaya nama
 *     kolomnya tidak lagi menyebut istilah yang sudah ditinggalkan.
 *
 * URUTANNYA PENTING dan tidak boleh disederhanakan: enum harus DIPERLUAS lebih
 * dulu supaya memuat nilai lama DAN nilai baru, baru datanya dipindahkan, baru
 * enum-nya dipersempit. Kalau `UPDATE` dijalankan selagi 'famili_lain' belum
 * ada di daftar enum, MySQL memotong nilainya ("Data truncated for column")
 * dan migrasinya gagal di tengah jalan.
 *
 * Perubahan enum ditulis dua jalur karena proyek ini berjalan di dua driver:
 * produksi memakai MySQL (`ALTER ... MODIFY`), rangkaian tes memakai SQLite
 * (lewat Blueprint, yang membangun ulang tabelnya).
 */
return new class extends Migration
{
    /** @var list<string> */
    private const LAMA = ['ayah', 'ibu', 'wali'];

    /** @var list<string> */
    private const BARU = ['ayah', 'ibu', 'famili_lain', 'kepala_keluarga'];

    public function up(): void
    {
        // 1. Perluas dulu: nilai lama + nilai baru sama-sama sah. Disatukan
        //    dengan `array_unique` karena MySQL MENOLAK enum bernilai ganda
        //    ("Column 'ikut_kk' has duplicated value") -- 'ayah' dan 'ibu' ada
        //    di kedua daftar.
        $this->ubahEnumIkutKk(array_values(array_unique([...self::LAMA, ...self::BARU])));

        // 2. Pindahkan datanya.
        DB::table('profil_pengguna')
            ->where('ikut_kk', 'wali')
            ->update(['ikut_kk' => 'famili_lain']);

        // 3. Baru persempit ke nilai baru saja.
        $this->ubahEnumIkutKk(self::BARU);

        // 4. Samakan nama kolom boolean turunannya.
        Schema::table('profil_pengguna', function (Blueprint $table) {
            $table->renameColumn('kk_ikut_wali', 'kk_ikut_famili_lain');
        });
    }

    public function down(): void
    {
        Schema::table('profil_pengguna', function (Blueprint $table) {
            $table->renameColumn('kk_ikut_famili_lain', 'kk_ikut_wali');
        });

        // Urutan terbalik, dengan alasan yang sama: perluas dulu.
        $this->ubahEnumIkutKk(array_values(array_unique([...self::BARU, ...self::LAMA])));

        DB::table('profil_pengguna')
            ->where('ikut_kk', 'famili_lain')
            ->update(['ikut_kk' => 'wali']);

        $this->ubahEnumIkutKk(self::LAMA);
    }

    /**
     * Ubah daftar nilai enum `ikut_kk` di driver mana pun.
     *
     * MySQL memakai `ALTER ... MODIFY` langsung; SQLite tidak punya ENUM dan
     * menyimpannya sebagai varchar ber-CHECK, jadi perubahannya lewat Blueprint
     * yang membangun ulang tabelnya. Kalau hanya salah satu jalur yang ditulis,
     * tes lulus di SQLite tapi produksi gagal -- atau sebaliknya.
     *
     * @param  list<string>  $nilai
     */
    private function ubahEnumIkutKk(array $nilai): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $daftar = implode(', ', array_map(fn (string $v) => "'{$v}'", $nilai));

            DB::statement("ALTER TABLE profil_pengguna MODIFY ikut_kk ENUM({$daftar}) NOT NULL DEFAULT 'ayah'");

            return;
        }

        Schema::table('profil_pengguna', function (Blueprint $table) use ($nilai) {
            $table->enum('ikut_kk', $nilai)->default('ayah')->change();
        });
    }
};
