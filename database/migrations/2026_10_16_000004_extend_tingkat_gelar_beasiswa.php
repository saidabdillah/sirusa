<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas jenjang beasiswa dari S1-S3 menjadi D1-S3.
 *
 * Tanpa ini, beasiswa untuk mahasiswa D3 (mis. vokasi) tidak bisa dibuat sama
 * sekali: `tingkat_gelar` menolak nilainya, sedangkan data mahasiswa sudah
 * punya jenjang D1-D4.
 *
 * Dua jalur karena proyek berjalan di dua driver: MySQL memakai
 * `ALTER ... MODIFY`, SQLite (dipakai rangkaian tes) tidak mendukungnya dan
 * enum-nya sudah longgar -- tidak ada CHECK constraint yang perlu diubah.
 */
return new class extends Migration
{
    private const NILAI = ['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];

    public function up(): void
    {
        $this->setEnum(self::NILAI);
    }

    public function down(): void
    {
        // Kembali ke rentang lama. Baris ber-jenjang D1-D4 akan ditolak MySQL
        // di mode ketat, jadi baris seperti itu dinormalkan lebih dulu supaya
        // rollback tidak menggantung.
        DB::table('beasiswa')
            ->whereIn('tingkat_gelar', ['D1', 'D2', 'D3', 'D4'])
            ->update(['tingkat_gelar' => 'S1']);

        $this->setEnum(['S1', 'S2', 'S3']);
    }

    /**
     * @param  list<string>  $nilai
     */
    private function setEnum(array $nilai): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $daftar = implode(', ', array_map(fn (string $v) => "'{$v}'", $nilai));

        DB::statement("ALTER TABLE beasiswa MODIFY tingkat_gelar ENUM({$daftar}) NOT NULL");
    }
};
