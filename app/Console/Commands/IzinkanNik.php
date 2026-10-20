<?php

namespace App\Console\Commands;

use App\Models\NikDiizinkan;
use Illuminate\Console\Command;

/**
 * Mengisi daftar NIK yang boleh langsung aktif saat mendaftar.
 *
 * Argumennya boleh berupa satu NIK (16 digit) atau path ke berkas CSV/TXT
 * berisi satu NIK per baris -- format yang dihasilkan Excel saat
 * "Simpan sebagai CSV". Baris judul ("nik") dan NIK yang sudah ada dilewati,
 * jadi perintah ini aman dijalankan berulang.
 */
class IzinkanNik extends Command
{
    protected $signature = 'nik:izinkan
                            {sumber : NIK 16 digit, atau path berkas CSV/TXT berisi satu NIK per baris}
                            {--hapus : Hapus NIK dari daftar, bukan menambah}';

    protected $description = 'Tambah/hapus NIK yang boleh langsung aktif saat mendaftar';

    public function handle(): int
    {
        $sumber = (string) $this->argument('sumber');

        $daftarNik = is_file($sumber)
            ? $this->bacaBerkas($sumber)
            : [$this->rapikan($sumber)];

        if ($daftarNik === []) {
            $this->error('Tidak ada NIK yang bisa dibaca dari sumber itu.');

            return self::FAILURE;
        }

        $hasil = $this->option('hapus')
            ? $this->hapus($daftarNik)
            : $this->tambah($daftarNik);

        $this->table(['Keterangan', 'Jumlah'], $hasil);

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $daftarNik
     * @return list<array{0: string, 1: int}>
     */
    private function tambah(array $daftarNik): array
    {
        $baru = 0;
        $sudahAda = 0;
        $tidakValid = 0;

        foreach ($daftarNik as $nik) {
            if (preg_match('/^\d{16}$/', $nik) !== 1) {
                $tidakValid++;

                continue;
            }

            if (NikDiizinkan::query()->where('nik', $nik)->exists()) {
                $sudahAda++;

                continue;
            }

            NikDiizinkan::create(['nik' => $nik]);
            $baru++;
        }

        return [
            ['Ditambahkan', $baru],
            ['Sudah ada', $sudahAda],
            ['Dilewati (bukan 16 digit)', $tidakValid],
        ];
    }

    /**
     * @param  list<string>  $daftarNik
     * @return list<array{0: string, 1: int}>
     */
    private function hapus(array $daftarNik): array
    {
        $terhapus = NikDiizinkan::query()->whereIn('nik', $daftarNik)->delete();

        return [['Dihapus', $terhapus]];
    }

    /**
     * @return list<string>
     */
    private function bacaBerkas(string $path): array
    {
        $isi = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return array_values(array_filter(
            array_map(fn (string $baris) => $this->rapikan($baris), $isi),
            fn (string $nik) => $nik !== '',
        ));
    }

    /**
     * Buang spasi, tanda kutip CSV, dan pemisah yang ikut terbaca.
     *
     * NIK dari Excel sering terbawa sebagai `"6302...",` atau `6302... ;`.
     * Baris judul dibiarkan apa adanya -- nanti tersaring sendiri karena
     * bukan 16 digit.
     */
    private function rapikan(string $nilai): string
    {
        $nilai = trim($nilai);
        $nilai = trim($nilai, "\"'");
        // Pisahkan hanya pada pemisah kolom CSV. Spasi TIDAK dipakai sebagai
        // pemisah: NIK sering ditulis "6302 0000 0000 0001" dan spasinya
        // harus dibuang, bukan memotong NIK-nya.
        $nilai = preg_split('/[,;\t]/', $nilai)[0] ?? '';
        $nilai = trim($nilai, "\"' ");

        // Excel kadang menyimpan NIK sebagai 6,302E+15 atau menambahkan
        // apostrof di depan supaya dianggap teks.
        return preg_replace('/\D/', '', $nilai) ?? '';
    }
}
