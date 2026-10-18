<?php

namespace App\Support;

/**
 * Aturan jenjang pendidikan dan rentang semester yang sah.
 *
 * Satu sumber kebenaran untuk tiga tempat sekaligus: pilihan dropdown di form
 * (lewat `semesterOptions()`), validasi backend (`semesterValid()`), dan
 * tampilan detail. Kalau rentangnya ditulis ulang di tiap tempat, cepat atau
 * lambat ada satu yang tertinggal -- dan itu berarti mahasiswa bisa menyimpan
 * semester yang tidak ada.
 *
 * Rentangnya mengikuti ketentuan yang diminta pengguna, bukan hasil hitungan
 * dari tahun masuk: semester tetap dipilih mahasiswa.
 */
final class JenjangPendidikan
{
    /**
     * Jenjang yang boleh dipilih, berurutan dari yang terendah.
     *
     * @var list<string>
     */
    public const JENJANG = ['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];

    /**
     * Jumlah semester untuk tiap jenjang.
     *
     * @var array<string, int>
     */
    public const JUMLAH_SEMESTER = [
        'D1' => 2,
        'D2' => 4,
        'D3' => 6,
        'D4' => 8,
        'S1' => 8,
        'S2' => 4,
        'S3' => 6,
    ];

    /**
     * Batas tahun masuk terlama yang masih masuk akal. Dipakai validasi supaya
     * angka yang jelas salah ketik (mis. 1800) tidak lolos.
     */
    public const TAHUN_TERLAMA = 1945;

    public static function valid(string $jenjang): bool
    {
        return in_array($jenjang, self::JENJANG, true);
    }

    /**
     * Jumlah semester jenjang ini, atau null kalau jenjangnya tidak dikenal.
     */
    public static function jumlahSemester(?string $jenjang): ?int
    {
        if ($jenjang === null || ! self::valid($jenjang)) {
            return null;
        }

        return self::JUMLAH_SEMESTER[$jenjang];
    }

    /**
     * Daftar semester yang sah untuk jenjang ini: [1, 2, ..., N].
     *
     * Mengembalikan array kosong bila jenjangnya belum dipilih atau tidak
     * dikenal -- pemanggil (view) memakai itu untuk menonaktifkan dropdown
     * semester sebelum jenjang dipilih.
     *
     * @return list<int>
     */
    public static function semesterOptions(?string $jenjang): array
    {
        $jumlah = self::jumlahSemester($jenjang);

        return $jumlah === null ? [] : range(1, $jumlah);
    }

    /**
     * Apakah pasangan jenjang + semester ini sah.
     *
     * Inilah penjaga backend: mahasiswa D1 yang mengirim semester 3 lewat
     * request manual (melewati dropdown yang sudah dinonaktifkan) tetap ditolak.
     */
    public static function semesterValid(?string $jenjang, mixed $semester): bool
    {
        $jumlah = self::jumlahSemester($jenjang);

        if ($jumlah === null || ! is_numeric($semester)) {
            return false;
        }

        $semester = (int) $semester;

        return $semester >= 1 && $semester <= $jumlah;
    }

    /**
     * Rentang tahun masuk yang diterima: dari TAHUN_TERLAMA sampai tahun ini.
     * Tahun depan dan seterusnya ditolak karena belum terjadi.
     *
     * @return array{0: int, 1: int}
     */
    public static function rentangTahunMasuk(): array
    {
        return [self::TAHUN_TERLAMA, (int) now()->year];
    }
}
