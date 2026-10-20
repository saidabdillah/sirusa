<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daftar NIK yang boleh langsung aktif saat mendaftar.
        //
        // Alur normal: akun mahasiswa dibuat `non-aktif` dan harus diaktifkan
        // admin. Untuk mahasiswa yang memang sudah terdata (mis. penerima
        // beasiswa dari data yayasan), NIK-nya didaftarkan di sini lebih dulu
        // supaya begitu ia mendaftar sendiri akunnya langsung aktif tanpa
        // menunggu admin.
        //
        // `nik` unik: satu NIK hanya boleh muncul sekali, sama seperti
        // `profil_pengguna.nik`.
        Schema::create('nik_diizinkan', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nik_diizinkan');
    }
};
