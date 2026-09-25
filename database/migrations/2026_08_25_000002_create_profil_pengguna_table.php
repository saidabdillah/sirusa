<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_pengguna', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_lengkap')->nullable();
            $table->string('nik', 16)->nullable()->unique();
            $table->string('nim')->nullable()->unique();
            $table->string('no_kk', 16)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->enum('agama', ['Islam', 'Kristen', 'Katholik', 'Hindu', 'Buddha', 'Konghucu'])->nullable();
            $table->string('telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('desa_kelurahan')->nullable();
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->decimal('ipk', 3, 2)->nullable();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->decimal('ukt', 10, 2)->nullable();

            $table->enum('ikut_kk', ['ayah', 'ibu', 'wali'])->default('ayah');
            $table->boolean('kk_ikut_wali')->default(false);

            $table->string('nama_ayah')->nullable();
            $table->enum('pekerjaan_ayah', ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'])->nullable();
            $table->string('nik_ayah', 16)->nullable();
            $table->string('nama_ibu')->nullable();
            $table->enum('pekerjaan_ibu', ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'])->nullable();
            $table->string('nik_ibu', 16)->nullable();
            $table->string('nama_wali')->nullable();
            $table->enum('pekerjaan_wali', ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'])->nullable();
            $table->enum('hubungan_wali', ['Paman', 'Bibi', 'Kakek', 'Nenek', 'Lainnya'])->nullable();
            $table->string('nik_wali', 16)->nullable();

            $table->unsignedTinyInteger('desil')->nullable();

            $table->string('foto_profil')->nullable();
            $table->string('dokumen_ktp')->nullable();
            $table->string('dokumen_kk')->nullable();
            $table->string('dokumen_desil')->nullable();
            $table->string('dokumen_sktm')->nullable();
            $table->json('dokumen_prestasi')->nullable();
            $table->string('dokumen_transkrip')->nullable();
            $table->string('dokumen_surat_aktif')->nullable();
            $table->string('dokumen_surat_pernyataan')->nullable();
            $table->string('dokumen_bukti_ukt')->nullable();
            $table->string('ktp_ayah')->nullable();
            $table->string('ktp_ibu')->nullable();
            $table->string('ktp_wali')->nullable();
            $table->string('kk_wali')->nullable();

            $table->enum('verif_capil', ['menunggu', 'setuju', 'revisi', 'tolak'])->default('menunggu');
            $table->enum('verif_kampus', ['menunggu', 'setuju', 'revisi', 'tolak'])->default('menunggu');
            $table->enum('verif_kesra', ['menunggu', 'setuju', 'revisi', 'tolak'])->default('menunggu');
            $table->text('catatan_capil')->nullable();
            $table->text('catatan_kampus')->nullable();
            $table->text('catatan_kesra')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_pengguna');
    }
};
