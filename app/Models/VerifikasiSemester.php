<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu paket semester milik seorang PENERIMA beasiswa.
 *
 * Fase seleksi (Catpil/Kampus/Kesra) sudah selesai sebelum baris ini ada, dan
 * tidak pernah diulang di sini. Yang diperiksa tiap semester hanya paket
 * akademiknya: KRS semester N, KHS semester N, dan nilai KHS semester N.
 *
 * Urutannya SENGAJA berurutan (Kampus dulu, baru Kesra), bukan paralel seperti
 * fase seleksi: keduanya memeriksa dokumen yang sama, jadi kalau berjalan
 * bersamaan bisa lahir dua keputusan yang bertentangan untuk satu semester.
 */
class VerifikasiSemester extends Model
{
    protected $table = 'verifikasi_semester';

    public const KAMPUS_BELUM_LENGKAP = 'belum_lengkap';

    public const KAMPUS_MENUNGGU = 'menunggu';

    public const KAMPUS_SETUJU = 'setuju';

    public const KAMPUS_REVISI = 'revisi';

    public const KESRA_MENUNGGU = 'menunggu';

    public const KESRA_SETUJU = 'setuju';

    public const KESRA_TAHAN = 'tahan';

    public const KESRA_DICABUT = 'dicabut';

    /**
     * Label status Kampus. `belum_lengkap` bukan keputusan petugas, melainkan
     * keadaan dokumen: KRS/KHS/nilai semester itu belum lengkap diunggah.
     *
     * @var array<string, string>
     */
    public const LABEL_KAMPUS = [
        self::KAMPUS_BELUM_LENGKAP => 'Belum lengkap',
        self::KAMPUS_MENUNGGU => 'Menunggu Kampus',
        self::KAMPUS_SETUJU => 'Disetujui Kampus',
        self::KAMPUS_REVISI => 'Perlu perbaikan',
    ];

    /** @var array<string, string> */
    public const BADGE_KAMPUS = [
        self::KAMPUS_BELUM_LENGKAP => 'secondary',
        self::KAMPUS_MENUNGGU => 'warning',
        self::KAMPUS_SETUJU => 'success',
        self::KAMPUS_REVISI => 'danger',
    ];

    /** @var array<string, string> */
    public const LABEL_KESRA = [
        self::KESRA_MENUNGGU => 'Menunggu Kesra',
        self::KESRA_SETUJU => 'Selesai',
        self::KESRA_TAHAN => 'Ditahan',
        self::KESRA_DICABUT => 'Dicabut',
    ];

    /** @var array<string, string> */
    public const BADGE_KESRA = [
        self::KESRA_MENUNGGU => 'warning',
        self::KESRA_SETUJU => 'success',
        self::KESRA_TAHAN => 'danger',
        self::KESRA_DICABUT => 'dark',
    ];

    protected $fillable = [
        'pendaftar_id',
        'semester',
        'status_kampus',
        'catatan_kampus',
        'diputuskan_kampus_at',
        'diputuskan_kampus_oleh',
        'status_kesra',
        'catatan_kesra',
        'diputuskan_kesra_at',
        'diputuskan_kesra_oleh',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'diputuskan_kampus_at' => 'datetime',
            'diputuskan_kesra_at' => 'datetime',
        ];
    }

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'pendaftar_id');
    }

    public function pemutusKampus(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_kampus_oleh');
    }

    public function pemutusKesra(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_kesra_oleh');
    }

    public function labelKampus(): string
    {
        return self::LABEL_KAMPUS[$this->status_kampus] ?? ucfirst((string) $this->status_kampus);
    }

    public function badgeKampus(): string
    {
        return self::BADGE_KAMPUS[$this->status_kampus] ?? 'secondary';
    }

    public function labelKesra(): string
    {
        return self::LABEL_KESRA[$this->status_kesra] ?? ucfirst((string) $this->status_kesra);
    }

    public function badgeKesra(): string
    {
        return self::BADGE_KESRA[$this->status_kesra] ?? 'secondary';
    }

    /**
     * Apakah paket ini sudah selesai seluruhnya (Kampus dan Kesra setuju).
     */
    public function selesai(): bool
    {
        return $this->status_kampus === self::KAMPUS_SETUJU
            && $this->status_kesra === self::KESRA_SETUJU;
    }

    /**
     * Antrean kerja Kampus: paket yang dokumennya sudah lengkap dan belum
     * diputuskan, plus yang sudah diputuskan (supaya keputusan bisa diubah).
     *
     * @param  Builder<VerifikasiSemester>  $query
     * @return Builder<VerifikasiSemester>
     */
    public function scopeAntreanKampus(Builder $query): Builder
    {
        return $query->whereIn('status_kampus', [self::KAMPUS_MENUNGGU, self::KAMPUS_SETUJU, self::KAMPUS_REVISI])
            ->where('status_kesra', '!=', self::KESRA_DICABUT);
    }

    /**
     * Antrean kerja Kesra: hanya paket yang SUDAH disetujui Kampus. Ini yang
     * membuat urutannya berurutan, bukan paralel.
     *
     * @param  Builder<VerifikasiSemester>  $query
     * @return Builder<VerifikasiSemester>
     */
    public function scopeAntreanKesra(Builder $query): Builder
    {
        return $query->where('status_kampus', self::KAMPUS_SETUJU)
            ->where('status_kesra', '!=', self::KESRA_DICABUT);
    }

    /**
     * Pastikan setiap paket semester yang DOKUMENNYA SUDAH LENGKAP punya baris
     * keputusan berstatus "menunggu". Idempoten: baris yang sudah ada tidak
     * diubah, jadi keputusan petugas tidak pernah tertimpa.
     *
     * Hanya berjalan untuk pendaftaran `diterima` -- mahasiswa yang belum
     * diterima tidak masuk siklus semester sama sekali.
     */
    public static function sinkron(User $user): void
    {
        $semesterAktif = (int) ($user->profile?->semester ?? 0);

        if ($semesterAktif < 1) {
            return;
        }

        $dokumen = DokumenAkademik::query()
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('jenis');

        $penerima = $user->applicants()->where('status', 'diterima')->get();

        foreach ($penerima as $applicant) {
            for ($semester = 1; $semester <= $semesterAktif; $semester++) {
                if (! self::paketLengkap($dokumen, $semester)) {
                    continue;
                }

                self::firstOrCreate(
                    ['pendaftar_id' => $applicant->id, 'semester' => $semester],
                    ['status_kampus' => self::KAMPUS_MENUNGGU, 'status_kesra' => self::KESRA_MENUNGGU],
                );
            }
        }
    }

    /**
     * Paket dianggap lengkap bila KRS, KHS, dan nilai KHS semester itu ada.
     * Nilai KHS ikut disyaratkan karena itulah dasar pemeriksaan IPK.
     */
    private static function paketLengkap($dokumen, int $semester): bool
    {
        $krs = $dokumen->get(DokumenAkademik::JENIS_KRS, collect())->firstWhere('semester', $semester);
        $khs = $dokumen->get(DokumenAkademik::JENIS_KHS, collect())->firstWhere('semester', $semester);

        return (bool) $krs?->file
            && (bool) $khs?->file
            && $khs->nilai !== null
            && $khs->nilai !== '';
    }
}
