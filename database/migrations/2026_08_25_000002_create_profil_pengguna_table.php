<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_pengguna', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_lengkap')->nullable();
            $table->string('nik', 16)->nullable()->unique();
            $table->string('nim')->nullable()->unique();
            $table->string('no_kk', 16)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->enum('agama', ['Islam', 'Kristen', 'Katholik', 'Hindu', 'Buddha', 'Konghucu'])->nullable();
            $table->string('telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('desa_kelurahan')->nullable();
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();

            // Data pendidikan. Rentang semester yang sah bergantung pada
            // `jenjang` (lihat App\Support\JenjangPendidikan); `tahun_masuk`
            // tidak boleh di masa depan dan batas bawahnya dijaga validasi,
            // bukan skema.
            $table->enum('jenjang', ['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'])->nullable();
            $table->unsignedSmallInteger('tahun_masuk')->nullable();

            // Apakah mahasiswa ini kepala keluarga. Radio Ya/Tidak di form,
            // jadi boolean nullable: null = belum dijawab.
            $table->boolean('kepala_keluarga')->nullable();

            $table->decimal('ipk', 3, 2)->nullable();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->decimal('ukt', 10, 2)->nullable();

            // Opsi "Wali" sudah ditinggalkan dan diganti "Famili Lain"; opsi
            // keempat `kepala_keluarga` memakai kolom boolean di atas.
            $table->enum('ikut_kk', ['ayah', 'ibu', 'famili_lain', 'kepala_keluarga'])->default('ayah');
            $table->boolean('kk_ikut_famili_lain')->default(false);

            $table->string('nama_ayah')->nullable();
            $table->enum('pekerjaan_ayah', ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'])->nullable();
            $table->string('nik_ayah', 16)->nullable();
            $table->string('nama_ibu')->nullable();
            $table->enum('pekerjaan_ibu', ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'])->nullable();
            $table->string('nik_ibu', 16)->nullable();
            $table->string('nama_wali')->nullable();
            $table->enum('pekerjaan_wali', ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'])->nullable();
            $table->enum('hubungan_wali', ['Paman', 'Bibi', 'Kakek', 'Nenek', 'Lainnya'])->nullable();
            $table->string('nik_wali', 16)->nullable();

            $table->unsignedTinyInteger('desil')->nullable();

            $table->string('foto_profil')->nullable();
            $table->string('dokumen_ktp')->nullable();
            $table->string('dokumen_kk')->nullable();
            $table->string('dokumen_desil')->nullable();
            $table->string('dokumen_sktm')->nullable();
            $table->json('dokumen_prestasi')->nullable();
            $table->string('dokumen_transkrip')->nullable();
            $table->string('dokumen_surat_aktif')->nullable();
            $table->string('dokumen_surat_pernyataan')->nullable();
            // Bukti terdaftar di PDDIKTI (menggantikan dokumen transkrip).
            $table->string('dokumen_pddikti')->nullable();
            $table->string('dokumen_bukti_ukt')->nullable();
            $table->string('ktp_ayah')->nullable();
            $table->string('ktp_ibu')->nullable();
            $table->string('ktp_wali')->nullable();
            $table->string('kk_wali')->nullable();

            $table->enum('verif_catpil', ['menunggu', 'setuju', 'revisi', 'tolak'])->default('menunggu');
            $table->enum('verif_kampus', ['menunggu', 'setuju', 'revisi', 'tolak'])->default('menunggu');
            $table->enum('verif_kesra', ['menunggu', 'setuju', 'revisi', 'tolak'])->default('menunggu');
            $table->text('catatan_catpil')->nullable();
            $table->text('catatan_kampus')->nullable();
            $table->text('catatan_kesra')->nullable();

            // Sejak kapan mahasiswa berdomisili di wilayah persyaratan
            // beasiswa. Diisi petugas Catpil saat memutuskan `setuju`, bukan
            // oleh mahasiswa, dan tidak boleh disamakan dengan tanggal KTP
            // atau tanggal registrasi.
            $table->date('berdomisili_sejak')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_pengguna');
    }
};
