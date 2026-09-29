<?php

namespace App\Exports;

use App\Models\User;
use App\Models\UserProfile;
use App\Support\VerifikasiAntrean;
use DateTimeInterface;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Dasar untuk unduhan Excel antrean verifikasi.
 *
 * Isi unduhan sengaja diambil dari `VerifikasiAntrean` yang sama dengan daftar
 * di layar, bukan dari query terpisah. Kalau nanti aturannya berubah, daftar
 * dan unduhan ikut berubah bersamaan — dan scoping per kampus admin otomatis
 * ikut terbawa, karena `scopedKampusId()` sudah dipakai di sana.
 *
 * Subclass hanya menentukan kolomnya; pemformatan sel, gaya header, dan nama
 * file ditangani di sini.
 *
 * ## Angka panjang tetap ditulis sebagai teks
 *
 * NIK, NIM, dan No. Kartu Keluarga adalah nomor identitas, bukan angka yang
 * mau dijumlahkan. NIK 16 digit, jadi nilainya dibiarkan sebagai string dan
 * `DefaultValueBinder` bawaan PhpSpreadsheet sudah menjaganya dua kali:
 *
 *  - nilai yang diawali `0` (dan bukan `0.`) dikembalikan sebagai teks, jadi
 *    nol di depan tidak hilang;
 *  - nilai yang lebih besar dari 999999999999999 dikembalikan sebagai teks,
 *    jadi batas 15 digit bermakna milik Excel tidak memotong digit terakhir.
 *
 * Yang TIDAK boleh dilakukan: memformat kolom ini dengan `number_format`, atau
 * memaksanya jadi angka "agar bisa dijumlahkan". Keduanya menghapus tepat data
 * yang membuat kolom ini berguna. Sebaliknya, nilai yang memang harus bisa
 * dijumlahkan (IPK, semester, UKT) ditulis sebagai angka mentah -- bukan
 * string berisi pemisah ribuan -- supaya masih bisa disaring di Excel.
 */
abstract class VerifikasiExport
{
    public function __construct(
        private readonly VerifikasiAntrean $antrean,
        private readonly ?string $filter = null,
    ) {}

    /**
     * @return list<string>
     */
    abstract public function headings(): array;

    /**
     * Nilai satu baris. Indeks kolom harus sama dengan urutan `headings()`.
     *
     * @return list<string|int|float|null>
     */
    abstract public function row(User $user, UserProfile $profile, int $nomor): array;

    /**
     * Prefix nama file, mis. `verifikasi-kampus`.
     */
    abstract public function slug(): string;

    /**
     * Antrean yang sama persis dengan yang dibuka admin, filter ikut terbawa.
     *
     * @return Collection<int, User>
     */
    public function users(): Collection
    {
        return $this->antrean->antrean($this->filter);
    }

    public function fileName(): string
    {
        $parts = [$this->slug()];

        if ($this->filter !== null) {
            $parts[] = $this->filter;
        }

        $parts[] = now()->format('Y-m-d');

        return implode('-', $parts);
    }

    /**
     * Filter yang sedang berlaku, supaya subclass bisa ikut mempersempit datanya.
     *
     * Untuk Kesra, filter ini adalah status pendaftaran, bukan status profil. Kalau
     * unduhannya tidak ikut menyaring, file Excel dan daftar di layar akan
     * berbeda isi padahal keduanya dari tombol yang sama.
     */
    protected function filter(): ?string
    {
        return $this->filter;
    }

    /**
     * Antrean yang sama dengan yang dibuka admin, supaya subclass bisa menggantinya
     * dengan sumber data yang relevan tahapnya tanpa menggali property private.
     */
    protected function antrean(): VerifikasiAntrean
    {
        return $this->antrean;
    }

    public function toSpreadsheet(): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle($this->antrean->actorLabel());

        $this->isiBaris($sheet);
        $this->gayaHeader($sheet);

        return $book;
    }

    /**
     * @param  Worksheet<int, Cell>  $sheet
     */
    private function isiBaris(Worksheet $sheet): void
    {
        $sheet->fromArray($this->headings(), null, 'A1');

        $users = $this->users();

        foreach ($users as $nomor => $user) {
            $profile = $user->profile;

            if (! $profile) {
                continue;
            }

            $sheet->fromArray(
                $this->row($user, $profile, $nomor + 1),
                null,
                'A'.($nomor + 2),
            );
        }

        $lastRow = $users->count() + 1;
        $lastColumn = $this->hurufKolom(count($this->headings()));

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(24);

        $sheet->freezePane('A2');

        // Baris kosong tetap dilaporkan supaya angka di sheet bisa dipercaya
        // saat antrean sedang kosong.
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
    }

    /**
     * @param  Worksheet<int, Cell>  $sheet
     */
    private function gayaHeader(Worksheet $sheet): void
    {
        $jumlahKolom = count($this->headings());
        $lastColumn = $this->hurufKolom($jumlahKolom);

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF219653']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        ]);

        // Diulang lewat indeks, bukan `range('A', $lastColumn)`. `range()`
        // hanya bisa menghitung huruf tunggal, jadi begitu kolom melewati `Z`
        // (26 kolom) pemanggilannya melempar ErrorException dan seluruh
        // unduhan jadi 500.
        for ($column = 1; $column <= $jumlahKolom; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    /**
     * Kolom spread sheet memakai huruf, sedangkan array data memakai indeks.
     */
    private function hurufKolom(int $count): string
    {
        return Coordinate::stringFromColumnIndex(max($count, 1));
    }

    protected function tanggal(?DateTimeInterface $date, string $format = 'd/m/Y'): string
    {
        return $date ? $date->format($format) : '-';
    }

    /**
     * Nilai teks, dengan pagar untuk sel kosong supaya kolom tidak terlihat
     * berlubang saat disaring di Excel.
     */
    protected function teks(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return trim((string) $value);
    }

    /**
     * Nama berkas dari path lampiran yang diunggah mahasiswa.
     *
     * Disimpan sebagai path relatif di bawah `storage/app/public`, jadi nama
     * berkasnya saja sudah cukup untuk pemeriksa mencocokkannya dengan berkas
     * yang diunggah tanpa membocorkan struktur folder server.
     */
    protected function namaBerkas(?string $path): string
    {
        if ($path === null || trim($path) === '') {
            return '-';
        }

        return basename(trim($path));
    }
}
