<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KRS dan KHS per semester, masing-masing dengan berkas DAN nilainya.
 *
 * KRS dan KHS sengaja dua baris berbeda (`jenis`), bukan dua kolom di satu
 * baris: berkasnya berbeda, nilainya berbeda, dan semesternya bisa berbeda.
 * Menggabungkannya ke satu baris akan membuat nilai KRS dan nilai KHS saling
 * menimpa.
 *
 * Kunci unik (user, jenis, semester) adalah inti aturan "jangan menimpa
 * dokumen semester sebelumnya": mengunggah ulang semester yang SAMA
 * memperbarui baris itu, sedangkan semester lain selalu menambah baris baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_akademik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('jenis', ['krs', 'khs']);
            $table->unsignedTinyInteger('semester');
            // Berkas disimpan lewat disk aplikasi yang sudah dipakai dokumen
            // profil lain, jadi hanya path-nya yang disimpan di sini.
            $table->string('file')->nullable();
            // Skala nilai mengikuti aturan akademik yang SUDAH dipakai aplikasi
            // (IPK 0-4 di `profil_pengguna.ipk`), bukan skala baru.
            $table->decimal('nilai', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'jenis', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_akademik');
    }
};
