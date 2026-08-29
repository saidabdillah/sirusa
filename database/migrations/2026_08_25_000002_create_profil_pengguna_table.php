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
            $table->string('nik', 16)->nullable();
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
            $table->string('foto_profil')->nullable();
            $table->enum('status_orang_tua', ['Lengkap', 'Yatim', 'Piatu', 'Yatim Piatu', 'Wali'])->nullable();
            $table->string('nama_ayah')->nullable();
            $table->enum('status_ayah', ['Hidup', 'Meninggal Dunia'])->nullable();
            $table->enum('pekerjaan_ayah', ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'])->nullable();
            $table->enum('penghasilan_ayah', ['< 1jt', '1-3jt', '3-5jt', '5-10jt', '> 10jt'])->nullable();
            $table->string('nama_ibu')->nullable();
            $table->enum('status_ibu', ['Hidup', 'Meninggal Dunia'])->nullable();
            $table->enum('pekerjaan_ibu', ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'])->nullable();
            $table->enum('penghasilan_ibu', ['< 1jt', '1-3jt', '3-5jt', '5-10jt', '> 10jt'])->nullable();
            $table->string('nama_wali')->nullable();
            $table->enum('pekerjaan_wali', ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'])->nullable();
            $table->enum('penghasilan_wali', ['< 1jt', '1-3jt', '3-5jt', '5-10jt', '> 10jt'])->nullable();
            $table->enum('hubungan_wali', ['Paman', 'Bibi', 'Kakek', 'Nenek', 'Lainnya'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_pengguna');
    }
};
