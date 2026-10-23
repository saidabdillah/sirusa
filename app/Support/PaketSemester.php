<?php

namespace App\Support;

use App\Models\Applicant;
use App\Models\DokumenAkademik;
use App\Models\VerifikasiSemester;
use Illuminate\Support\Collection;

/**
 * Paket verifikasi per semester untuk PENERIMA beasiswa.
 *
 * Fase seleksi (Catpil + Kampus paralel, lalu Kesra memutuskan) selesai sekali
 * dan tidak diulang. Setelah mahasiswa diterima, tiap semester ia menyerahkan
 * paket akademik: KRS semester N, KHS semester N, dan nilai KHS semester N.
 * Kampus memeriksa paket itu, lalu Kesra menutupnya. Catpil tidak pernah
 * dipanggil lagi.
 *
 * Sumber kebenaran kelengkapan dokumen tetap `dokumen_akademik` (yang diunggah
 * di halaman profil), jadi paket TIDAK menyimpan salinan berkas. Yang disimpan
 * hanya keputusannya (`verifikasi_semester`).
 */
class PaketSemester
{
    /**
     * Daftar paket semester milik satu pendaftaran yang sudah diterima.
     *
     * Semester yang dihitung adalah 1 sampai semester AKTIF mahasiswa saat ini
     * (dari profil), karena itulah paket yang seharusnya sudah ia punya.
     * Semester berjalan tetap ikut tampil walau KHS-nya belum terbit, supaya
     * mahasiswa tahu apa yang masih ditunggu.
     *
     * @return array<int, array{semester: int, lengkap: bool, kurang: array<int, string>, keputusan: ?VerifikasiSemester, status: string}>
     */
    public static function untuk(Applicant $applicant): array
    {
        $applicant->loadMissing(['user.profile', 'verifikasiSemester']);

        $semesterAktif = (int) ($applicant->user?->profile?->semester ?? $applicant->semester ?? 0);

        if ($semesterAktif < 1) {
            return [];
        }

        $dokumen = DokumenAkademik::query()
            ->where('user_id', $applicant->user_id)
            ->get()
            ->groupBy('jenis');

        $keputusan = $applicant->verifikasiSemester->keyBy('semester');

        $paket = [];

        for ($semester = 1; $semester <= $semesterAktif; $semester++) {
            $kurang = self::kekurangan($dokumen, $semester);
            $baris = $keputusan->get($semester);

            $paket[] = [
                'semester' => $semester,
                'lengkap' => $kurang === [],
                'kurang' => $kurang,
                'keputusan' => $baris,
                'status' => self::statusRingkas($kurang === [], $baris),
                'label' => self::labelRingkas($kurang === [], $baris),
                'badge' => self::badgeRingkas($kurang === [], $baris),
            ];
        }

        return $paket;
    }

    /**
     * Berkas/nilai yang belum ada untuk satu semester.
     *
     * KHS wajib punya NILAI (permintaan pengguna), bukan hanya berkas: nilai
     * itulah yang dipakai memeriksa apakah IPK masih memenuhi syarat.
     *
     * @param  Collection<string, Collection<int, DokumenAkademik>>  $dokumen
     * @return array<int, string>
     */
    private static function kekurangan($dokumen, int $semester): array
    {
        $kurang = [];

        $krs = $dokumen->get(DokumenAkademik::JENIS_KRS, collect())->firstWhere('semester', $semester);
        $khs = $dokumen->get(DokumenAkademik::JENIS_KHS, collect())->firstWhere('semester', $semester);

        if (! $krs?->file) {
            $kurang[] = 'Berkas KRS';
        }

        if (! $khs?->file) {
            $kurang[] = 'Berkas KHS';
        }

        if ($khs === null || $khs->nilai === null || $khs->nilai === '') {
            $kurang[] = 'Nilai KHS';
        }

        return $kurang;
    }

    /**
     * Status ringkas satu paket untuk ditampilkan.
     *
     * Keputusan `dicabut` menang atas apa pun: beasiswa sudah tidak berlaku,
     * jadi kelengkapan dokumen tidak lagi berarti.
     */
    private static function statusRingkas(bool $lengkap, ?VerifikasiSemester $baris): string
    {
        if ($baris?->status_kesra === VerifikasiSemester::KESRA_DICABUT) {
            return 'dicabut';
        }

        if (! $lengkap) {
            return 'belum_lengkap';
        }

        if ($baris?->selesai()) {
            return 'selesai';
        }

        if ($baris?->status_kampus === VerifikasiSemester::KAMPUS_SETUJU) {
            return 'menunggu_kesra';
        }

        return $baris?->status_kampus ?? VerifikasiSemester::KAMPUS_MENUNGGU;
    }

    /**
     * Label yang dilihat mahasiswa. Satu tempat untuk seluruh paket, supaya
     * halaman pendaftaran dan dasbor tidak menulis ulang istilah yang sama.
     */
    private static function labelRingkas(bool $lengkap, ?VerifikasiSemester $baris): string
    {
        return match (self::statusRingkas($lengkap, $baris)) {
            'dicabut' => 'Dicabut',
            'belum_lengkap' => 'Belum lengkap',
            'selesai' => 'Selesai',
            'menunggu_kesra' => 'Menunggu Kesra',
            VerifikasiSemester::KAMPUS_REVISI => 'Perlu perbaikan',
            default => 'Menunggu Kampus',
        };
    }

    private static function badgeRingkas(bool $lengkap, ?VerifikasiSemester $baris): string
    {
        return match (self::statusRingkas($lengkap, $baris)) {
            'dicabut' => 'dark',
            'belum_lengkap' => 'secondary',
            'selesai' => 'success',
            'menunggu_kesra' => 'info',
            VerifikasiSemester::KAMPUS_REVISI => 'danger',
            default => 'warning',
        };
    }

    /**
     * Paket yang perlu ditindaklanjuti MAHASISWA (belum lengkap atau diminta
     * perbaikan). Dipakai pengingat di dasbor.
     *
     * @return array<int, array{semester: int, status: string, kurang: array<int, string>, catatan: ?string}>
     */
    public static function perluTindakan(Applicant $applicant): array
    {
        return collect(self::untuk($applicant))
            ->filter(fn (array $paket) => in_array($paket['status'], [
                'belum_lengkap',
                VerifikasiSemester::KAMPUS_REVISI,
            ], true))
            ->map(fn (array $paket) => [
                'semester' => $paket['semester'],
                'status' => $paket['status'],
                'kurang' => $paket['kurang'],
                'catatan' => $paket['keputusan']?->catatan_kampus,
            ])
            ->values()
            ->all();
    }
}
