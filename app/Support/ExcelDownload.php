<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mengirim workbook PhpSpreadsheet langsung ke browser.
 *
 * Aplikasi ini memakai `phpoffice/phpspreadsheet` tanpa wrapper
 * `maatwebsite/excel`, jadi unduhan disusun sendiri. Sheet ditulis ke
 * `php://output` supaya file besar tidak pernah menyentuh disk.
 */
class ExcelDownload
{
    private const MIME_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public static function response(Spreadsheet $book, string $fileName): StreamedResponse
    {
        $writer = new Xlsx($book);
        $safeName = self::safeFileName($fileName);

        return response()->streamDownload(
            function () use ($writer): void {
                $writer->save('php://output');
            },
            $safeName,
            [
                'Content-Type' => self::MIME_XLSX,
                'Cache-Control' => 'max-age=0',
            ],
        );
    }

    /**
     * Nama file dari teks bebas harus dibersihkan supaya tidak bisa memutus
     * header `Content-Disposition` (mis. nama kampus berisi newline).
     */
    private static function safeFileName(string $fileName): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $fileName);
        $safe = trim((string) $safe, '-.');

        return ($safe !== '' ? $safe : 'export').'.xlsx';
    }
}
