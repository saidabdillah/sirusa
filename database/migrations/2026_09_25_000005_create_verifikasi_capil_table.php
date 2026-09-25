<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_capil', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pengajuan_id')->nullable()->constrained('pendaftar')->onDelete('set null');
            $table->unsignedBigInteger('mahasiswa_id')->nullable()->constrained('profil_pengguna')->onDelete('set null');
            $table->unsignedBigInteger('verifier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['PENDING', 'VALID', 'TIDAK_VALID', 'PERLU_PERBAIKAN']);
            $table->text('catatan')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::table('verifikasi_capil', function (Blueprint $table) {
            $table->index(['pengajuan_id']);
            $table->index(['mahasiswa_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_capil');
    }
};
