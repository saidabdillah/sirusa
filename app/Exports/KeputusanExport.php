<?php

namespace App\Exports;

use App\Models\Applicant;
use App\Models\UserProfile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Unduhan Excel daftar keputusan Kesra (pendaftar berstatus `diterima`).
 *
 * Isinya dibangun dari koleksi yang sama dengan halaman Keputusan
 * (`KeputusanPendaftaranController::disetujui()`), jadi unduhan tidak mungkin
 * menyimpang dari daftar di layar. Kolomnya persis baris tabel halaman itu.
 */
class KeputusanExport
{
    public function __construct(private readonly Collection $pendaftaran) {}

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'NIM',
            'Kampus',
            'Fakultas',
            'Program Studi',
            'Beasiswa',
            'Diputuskan',
        ];
    }

    public function fileName(): string
    {
        return 'keputusan-'.now()->format('Y-m-d');
    }

    public function toSpreadsheet(): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Keputusan');

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

        $nomor = 0;

        foreach ($this->pendaftaran as $applicant) {
            $nomor++;
            $profile = $applicant->user?->profile;

            $sheet->fromArray(
                $this->row($applicant, $profile, $nomor),
                null,
                'A'.($nomor + 1),
            );
        }

        $lastRow = max($nomor + 1, 1);
        $lastColumn = $this->hurufKolom(count($this->headings()));

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
    }

    /**
     * @return list<string|int|null>
     */
    private function row(Applicant $applicant, ?UserProfile $profile, int $nomor): array
    {
        return [
            $nomor,
            $this->teks($profile?->nama_lengkap ?? $applicant->user?->username),
            $this->teks($profile?->nim),
            $this->teks($profile?->prodi?->fakultas?->kampus?->nama_kampus),
            $this->teks($profile?->prodi?->fakultas?->nama),
            $this->teks($applicant->prodi ?? $profile?->prodi?->nama),
            $this->teks($applicant->beasiswa?->nama),
            $applicant->diputuskan_at?->format('d/m/Y H:i') ?? '-',
        ];
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

        // Diulang lewat indeks, bukan `range('A', $lastColumn)`: `range()` hanya
        // bisa menghitung huruf tunggal dan melempar ErrorException begitu
        // kolom melewati `Z`.
        for ($column = 1; $column <= $jumlahKolom; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    private function hurufKolom(int $count): string
    {
        return Coordinate::stringFromColumnIndex(max($count, 1));
    }

    /**
     * Nilai teks dengan pagar untuk sel kosong, supaya kolom identitas tidak
     * terlihat berlubang saat disaring.
     */
    private function teks(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return trim((string) $value);
    }
}
