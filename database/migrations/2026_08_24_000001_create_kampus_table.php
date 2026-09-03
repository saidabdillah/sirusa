<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kampus')->unique();
            $table->timestamps();
        });

        Schema::create('fakultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kampus_id')->constrained('kampus')->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();

            $table->unique(['kampus_id', 'nama']);
        });

        Schema::create('prodi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fakultas_id')->constrained('fakultas')->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();

            $table->unique(['fakultas_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prodi');
        Schema::dropIfExists('fakultas');
        Schema::dropIfExists('kampus');
    }
};
