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
            $table->enum('status', ['verifikasi', 'diterima', 'ditolak'])->default('verifikasi');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'beasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftar');
    }
};
