<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Struktur pengumuman serentak.
 *
 * Fase 1: Kesra memutuskan satu per satu (verifikasi/lihat). Keputusannya
 * disimpan mentah di `applicant.status` lama — BUKAN langsung terlihat.
 *
 * Fase 2: Kesra menekan "Umumkan Hasil". Sistem menyalin semua keputusan
 * ke tabel `beasiswa_results` dengan `result_at = sekarang()`. Dari tadi
 * keputusan tidak dapat diubah, dan seluruh mahasiswa lihat hasil yang sama
 * secara serentak (lihat User::resultAt()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beasiswa_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beasiswa_id')->constrained()->cascadeOnDelete();
            // Keputusan Kesra: diterima / ditolak / cadangan / dll.
            $table->string('status');
            // Waktu "Umumkan" — semua mahasiswa lihat hasil mulai dari ini.
            $table->timestamp('result_at');
            $table->text('catatan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswa_results');
    }
};