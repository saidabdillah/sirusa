<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota_keluarga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kartu_keluarga_id')->constrained('kartu_keluarga')->cascadeOnDelete();
            $table->string('nik', 16)->unique();
            $table->string('nama', 100);
            $table->enum('hubungan', ['AYAH', 'IBU', 'WALI', 'LAIN'])->default('AYAH');
            $table->enum('status', ['hidup', 'mati'])->default('hidup');
            $table->string('pekerjaan', 50)->nullable();
            $table->decimal('penghasilan', 12, 2)->nullable();
            $table->enum('pendidikan', ['SD', 'SMP', 'SMA', 'D1', 'D3', 'S1', 'S2', 'Lainnya'])->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->boolean('is_wali')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_keluarga');
    }
};
