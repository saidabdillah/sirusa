<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verifikasi PAKET SEMESTER untuk penerima beasiswa.
 *
 * Fase seleksi (Catpil/Kampus paralel lalu Kesra memutuskan) tetap memakai
 * kolom `profil_pengguna.verif_*` dan TIDAK disentuh oleh tabel ini. Tabel ini
 * hanya menampung siklus lanjutan yang berulang tiap semester: Kampus
 * memeriksa KRS/KHS semester itu, lalu Kesra menutupnya.
 *
 * Satu baris per (pendaftar, semester). Menempel ke `pendaftar`, bukan ke
 * `profil_pengguna`, karena siklusnya hanya berlaku bagi mahasiswa yang SUDAH
 * diterima pada beasiswa tertentu.
 *
 * Status `dicabut` sengaja ada di sini: pencabutan beasiswa oleh Kesra dicatat
 * sebagai keputusan pada semester tempat pencabutan terjadi, beserta alasannya,
 * supaya ada jejak siapa mencabut dan kapan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_semester', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->constrained('pendaftar')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester');

            // Tahap Kampus: memeriksa keaslian & kelengkapan KRS/KHS semester
            // ini. Tahap Kesra hanya berjalan setelah Kampus `setuju`.
            $table->enum('status_kampus', ['belum_lengkap', 'menunggu', 'setuju', 'revisi'])
                ->default('belum_lengkap');
            $table->text('catatan_kampus')->nullable();
            $table->timestamp('diputuskan_kampus_at')->nullable();
            $table->foreignId('diputuskan_kampus_oleh')->nullable()->constrained('users')->nullOnDelete();

            // Tahap Kesra: menutup semester (lanjut / tahan) atau mencabut
            // beasiswa.
            $table->enum('status_kesra', ['menunggu', 'setuju', 'tahan', 'dicabut'])
                ->default('menunggu');
            $table->text('catatan_kesra')->nullable();
            $table->timestamp('diputuskan_kesra_at')->nullable();
            $table->foreignId('diputuskan_kesra_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Satu keputusan per pendaftar per semester. Mengunggah ulang
            // memperbarui baris ini, bukan menambah baris kedua.
            $table->unique(['pendaftar_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_semester');
    }
};
