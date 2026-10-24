<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cakupan beasiswa: SATU tabel cakupan berisi id prodi, bukan nama salinan.
 *
 * Sebelumnya `beasiswa` terikat satu `kampus_id`, lalu `beasiswa_fakultas` dan
 * `beasiswa_prodi` menyimpan NAMA sebagai salinan. Akibatnya satu beasiswa hanya
 * bisa satu kampus, dan nama fakultas yang diubah di Master Data membuat cakupan
 * lama melenceng tanpa ada yang tahu.
 *
 * Bentuk baru (permintaan pengguna): satu beasiswa boleh mencakup BANYAK kampus,
 * BANYAK fakultas, dan BANYAK prodi, karena cakupannya disimpan sebagai daftar
 * id prodi. Kampus dan fakultas tidak lagi disimpan sebagai pilihan tersendiri --
 * keduanya hanya penyaring di form (tombol "pilih semua" mencentang anaknya),
 * sehingga tidak ada dua sumber kebenaran yang bisa bertentangan.
 *
 * Konsekuensi yang diterima pengguna: prodi yang ditambahkan SETELAH beasiswa
 * dibuat tidak otomatis ikut tercakup; admin menyunting beasiswanya.
 *
 * Kolom `beasiswa.kampus` (teks) DIPERTAHANKAN sebagai ringkasan untuk daftar
 * dan pencarian, diisi ulang oleh controller dari kampus-kampus prodi terpilih.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini sudah dibuat oleh skrip penyelamat data (lihat catatan di
        // .ai/rules/beasiswa.md), supaya cakupan lama bisa disalin SEBELUM
        // tabel lamanya dibuang. Migrasi karena itu melewatinya kalau ada.
        if (! Schema::hasTable('beasiswa_cakupan')) {
            Schema::create('beasiswa_cakupan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('beasiswa_id')->constrained('beasiswa')->cascadeOnDelete();
                // Prodi asli dari Master Data: menghapus prodi ikut mencabut
                // cakupannya, dan nama fakultas/kampus selalu dibaca dari sumbernya.
                $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['beasiswa_id', 'prodi_id']);
            });
        }

        // Kampus tunggal tidak lagi membatasi: cakupan ditentukan daftar prodi.
        Schema::table('beasiswa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kampus_id');
        });

        Schema::dropIfExists('beasiswa_prodi');
        Schema::dropIfExists('beasiswa_fakultas');
    }

    public function down(): void
    {
        Schema::table('beasiswa', function (Blueprint $table) {
            $table->foreignId('kampus_id')->nullable()->constrained('kampus')->nullOnDelete();
        });

        Schema::dropIfExists('beasiswa_cakupan');
    }
};
