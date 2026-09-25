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
            $table->foreignId('kampus_id')->nullable()->constrained('kampus')->nullOnDelete();
            $table->unsignedInteger('kuota')->default(0);
            $table->enum('tingkat_gelar', ['S1', 'S2', 'S3']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('deskripsi');
            $table->text('persyaratan')->nullable();
            $table->decimal('ipk_minimal', 3, 2)->default(0);
            $table->unsignedInteger('semester_minimal')->default(0);
            $table->enum('status', ['aktif', 'non-aktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswa');
    }
};
