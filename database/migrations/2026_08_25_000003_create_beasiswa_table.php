<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beasiswa', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kampus');
            $table->unsignedInteger('kuota')->default(0);
            $table->enum('tingkat_gelar', ['S1', 'S2', 'S3']);
            $table->enum('cakupan', ['penuh', 'sebagian']);
            $table->date('batas_waktu');
            $table->text('deskripsi');
            $table->text('persyaratan')->nullable();
            $table->enum('status', ['aktif', 'non-aktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswa');
    }
};
