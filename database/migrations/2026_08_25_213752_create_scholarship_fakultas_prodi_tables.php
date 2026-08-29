<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beasiswa_fakultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beasiswa_id')->constrained('beasiswa')->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('beasiswa_prodi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fakultas_id')->constrained('beasiswa_fakultas')->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswa_prodi');
        Schema::dropIfExists('beasiswa_fakultas');
    }
};
