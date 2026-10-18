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
            $table->enum('status', ['verifikasi', 'diterima', 'ditolak', 'dibatalkan'])->default('verifikasi');
            $table->text('catatan')->nullable();

            // Momen keputusan Kesra, terpisah dari `updated_at` yang ikut berubah
            // setiap kali baris disentuh. `updated_at` tidak bisa dipakai karena
            // keputusan revisi juga memutusnya, jadi tanggal yang tampil di blok
            // keputusan harus berasal dari penulisan yang disengaja.
            $table->timestamp('diputuskan_at')->nullable();
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('users')->nullOnDelete();

            // Kapan hasil keputusan ini DIUMUMKAN ke mahasiswa. Keputusan Kesra
            // tetap disimpan lebih dulu (`status` + `diputuskan_at`), tetapi
            // mahasiswa baru melihatnya setelah kolom ini terisi lewat aksi
            // "Umumkan Hasil" per beasiswa. Tanpa kolom ini, mahasiswa yang
            // dibuka berbarengan akan melihat hasil pada waktu berbeda-beda.
            $table->timestamp('decision_at')->nullable();

            $table->timestamps();
            $table->unique(['user_id', 'beasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftar');
    }
};
