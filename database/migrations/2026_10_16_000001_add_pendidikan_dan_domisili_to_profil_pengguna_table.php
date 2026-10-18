<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data pendidikan dan domisili mahasiswa.
 *
 * Migrasi BARU, bukan suntingan migrasi lama: aplikasi ini sudah dipakai
 * (ada profil, berkas, dan riwayat verifikasi di produksi), jadi skema harus
 * berubah tanpa menyentuh data yang sudah ada. Semua kolom di bawah nullable
 * supaya baris lama tetap sah apa adanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_pengguna', function (Blueprint $table) {
            // Jenjang pendidikan. Rentang semester yang sah bergantung pada
            // nilai ini (lihat App\Support\JenjangPendidikan).
            $table->enum('jenjang', ['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'])
                ->nullable()
                ->after('prodi_id');

            // Tahun masuk kuliah. Tidak boleh di masa depan; batas bawahnya
            // dijaga di validasi, bukan di skema.
            $table->unsignedSmallInteger('tahun_masuk')->nullable()->after('jenjang');

            // Apakah mahasiswa ini kepala keluarga. Radio Ya/Tidak di form,
            // jadi boolean nullable: null = belum dijawab.
            $table->boolean('kepala_keluarga')->nullable()->after('tahun_masuk');

            // Dokumen PDDIKTI (bukti terdaftar di PDDIKTI).
            $table->string('dokumen_pddikti')->nullable()->after('dokumen_surat_pernyataan');

            // Sejak kapan mahasiswa berdomisili di wilayah persyaratan beasiswa.
            // Diisi petugas Catpil saat memutuskan `setuju`, bukan oleh
            // mahasiswa, dan tidak boleh disamakan dengan tanggal KTP atau
            // tanggal registrasi.
            $table->date('berdomisili_sejak')->nullable()->after('catatan_kesra');
        });
    }

    public function down(): void
    {
        Schema::table('profil_pengguna', function (Blueprint $table) {
            $table->dropColumn([
                'jenjang',
                'tahun_masuk',
                'kepala_keluarga',
                'dokumen_pddikti',
                'berdomisili_sejak',
            ]);
        });
    }
};
