<?php

namespace App\Exports;

use App\Models\Applicant;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Unduhan Excel daftar penerima beasiswa (pendaftar berstatus `diterima`).
 *
 * Isinya dibangun dari koleksi yang sama dengan daftar di layar
 * (`PenerimaBeasiswaController::penerima()`), jadi unduhan tidak mungkin
 * menyimpang dari halaman. Aturan kolomnya sama dengan ekspor verifikasi:
 * NIK/NIM/No. KK tetap teks, IPK/semester/UKT/desil tetap angka mentah.
 */
class PenerimaExport
{
    public function __construct(private readonly Collection $penerima) {}

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Nama Lengkap',
            'NIK',
            'No. Kartu Keluarga',
            'NIM',
            'Jenis Kelamin',
            'Telepon',
            'Email',
            'Program Studi',
            'Fakultas',
            'Kampus',
            'IPK',
            'Semester',
            'UKT/SPP',
            'Desil',
            'Beasiswa',
            'Tanggal Pendaftaran',
        ];
    }

    public function fileName(): string
    {
        return 'penerima-beasiswa-'.now()->format('Y-m-d');
    }

    public function toSpreadsheet(): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Penerima');

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

        foreach ($this->penerima as $applicant) {
            $nomor++;
            $profile = $applicant->user?->profile;

            $sheet->fromArray(
                $this->row($applicant, $nomor),
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
     * @return list<string|int|float|null>
     */
    private function row(Applicant $applicant, int $nomor): array
    {
        $profile = $applicant->user?->profile;

        return [
            $nomor,
            $this->teks($profile?->nama_lengkap),
            $this->teks($profile?->nik),
            $this->teks($profile?->no_kk),
            $this->teks($profile?->nim),
            $this->teks($profile?->jenis_kelamin),
            $this->teks($profile?->telepon),
            $this->teks($applicant->user?->email),
            $this->teks($profile?->prodi?->nama),
            $this->teks($profile?->prodi?->fakultas?->nama),
            $this->teks($profile?->prodi?->fakultas?->kampus?->nama_kampus),
            $profile?->ipk ?? '-',
            $profile?->semester ?? '-',
            $profile?->ukt ?? '-',
            $profile?->desil ?: '-',
            $this->teks($applicant->beasiswa?->nama),
            $applicant->created_at?->format('d/m/Y') ?? '-',
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
