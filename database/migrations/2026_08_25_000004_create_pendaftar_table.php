<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beasiswa_id')->constrained('beasiswa')->cascadeOnDelete();
            $table->string('fakultas')->nullable();
            $table->string('prodi')->nullable();
            $table->decimal('ipk', 3, 2)->nullable();
            $table->unsignedInteger('semester')->nullable();
            $table->string('dokumen_ktp')->nullable();
            $table->string('dokumen_kk')->nullable();
            $table->string('dokumen_surat_aktif')->nullable();
            $table->string('dokumen_transkrip')->nullable();
            $table->string('dokumen_surat_permohonan')->nullable();
            $table->string('dokumen_pas_foto')->nullable();
            $table->json('dokumen_prestasi')->nullable();
            $table->string('dokumen_sktm')->nullable();
            $table->string('dokumen_bukti_ukt')->nullable();
            $table->string('dokumen_surat_pernyataan')->nullable();
            $table->enum('status', ['verifikasi', 'diterima', 'verifikasi_akhir', 'revisi', 'ditolak', 'selesai'])->default('verifikasi');
            $table->text('catatan')->nullable();
            $table->string('nomor_penetapan')->nullable();
            $table->date('tanggal_penetapan')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'beasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftar');
    }
};
