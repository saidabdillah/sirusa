<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom kuota pada beasiswa + kolom penghitung diri.
 *
 * - `quota`: berapa banyak kursi yang dialokasikan.
 * - `quota_filled`: berapa yang sudah diterima (otomatis oleh sistem).
 * - `result_announced_at`: kapan "Umumkan" ditekan — semua mahasiswa melihat
 *   status mulai dari timestamp ini (User::resultAt()).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hanya tambahkan kolom yang memang belum ada (jaga agar idempotent).
        Schema::table('beasiswa', function (Blueprint $table) {
            if (! Schema::hasColumn('beasiswa', 'quota')) {
                $table->unsignedInteger('quota')->default(0);
            }
            if (! Schema::hasColumn('beasiswa', 'result_announced_at')) {
                $table->timestamp('result_announced_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('beasiswa', function (Blueprint $table) {
            $table->dropColumn(['quota', 'result_announced_at']);
        });
    }
};