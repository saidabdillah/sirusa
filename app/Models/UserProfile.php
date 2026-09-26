<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'profil_pengguna';

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nik',
        'nim',
        'no_kk',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'telepon',
        'alamat',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'desa_kelurahan',
        'foto_profil',
        'ikut_kk',
        'kk_ikut_wali',
        'nama_ayah',
        'pekerjaan_ayah',
        'nik_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'nik_ibu',
        'nama_wali',
        'pekerjaan_wali',
        'hubungan_wali',
        'nik_wali',
        'prodi_id',
        'ipk',
        'semester',
        'ukt',
        'desil',
        'dokumen_ktp',
        'dokumen_kk',
        'dokumen_desil',
        'dokumen_sktm',
        'dokumen_prestasi',
        'dokumen_transkrip',
        'dokumen_surat_aktif',
        'dokumen_surat_pernyataan',
        'dokumen_bukti_ukt',
        'ktp_ayah',
        'ktp_ibu',
        'ktp_wali',
        'kk_wali',
        'verif_capil',
        'verif_kampus',
        'verif_kesra',
        'catatan_capil',
        'catatan_kampus',
        'catatan_kesra',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'kk_ikut_wali' => 'boolean',
            'dokumen_prestasi' => 'array',
        ];
    }

    public static function verifStages(): array
    {
        return [
            'capil' => ['verif_capil', 'catatan_capil'],
            'kampus' => ['verif_kampus', 'catatan_kampus'],
            'kesra' => ['verif_kesra', 'catatan_kesra'],
        ];
    }

    /**
     * Prefix route tiap tahap verifikasi. Tidak seragam — Verifikasi Kampus
     * memakai `admin.kampusverif`, bukan `admin.kampus` — jadi pemetaannya
     * harus satu sumber kebenaran, bukan di-hardcode di tiap controller.
     */
    public static function verifRoutePrefixes(): array
    {
        return [
            'capil' => 'capil',
            'kampus' => 'kampusverif',
            'kesra' => 'kesra',
        ];
    }

    public static function verifStageOrder(): array
    {
        return array_keys(self::verifStages());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function getAlamatLengkapAttribute(): string
    {
        $parts = array_filter([
            $this->alamat,
            $this->desa_kelurahan,
            $this->kecamatan ? 'Kec. '.$this->kecamatan : null,
            $this->kabupaten_kota,
            $this->provinsi,
        ]);

        return implode(', ', $parts);
    }

    public function isVerified(): bool
    {
        return $this->verif_capil === 'setuju'
            && $this->verif_kampus === 'setuju'
            && $this->verif_kesra === 'setuju';
    }

    public function verifStatus(): string
    {
        if ($this->isVerified()) {
            return 'terverifikasi';
        }

        foreach (self::verifStages() as $stage) {
            if ($this->{$stage[0]} === 'revisi') {
                return 'revisi';
            }
        }

        foreach (self::verifStages() as $stage) {
            if ($this->{$stage[0]} === 'tolak') {
                return 'tolak';
            }
        }

        return 'proses';
    }

    public function verifStageLabels(): array
    {
        return [
            'capil' => 'Verifikasi Capil',
            'kampus' => 'Verifikasi Kampus',
            'kesra' => 'Verifikasi Kesra',
        ];
    }

    /**
     * Keputusan pada satu tahap, beserta label dan warna badge siap tampil.
     *
     * @return array{status: string, label: string, badge: string, decided: bool}
     */
    public function verifStageDecision(string $stage): array
    {
        $statusField = self::verifStages()[$stage][0] ?? null;
        $status = $statusField ? $this->{$statusField} : 'menunggu';

        return [
            'status' => $status,
            'label' => match ($status) {
                'setuju' => 'Disetujui',
                'revisi' => 'Perlu Perbaikan',
                'tolak' => 'Ditolak',
                default => 'Menunggu',
            },
            'badge' => match ($status) {
                'setuju' => 'success',
                'revisi' => 'warning',
                'tolak' => 'danger',
                default => 'secondary',
            },
            'decided' => $status !== 'menunggu',
        ];
    }

    /**
     * Urutan antrean: yang paling butuh tindakan mahasiswa dulu, lalu yang
     * sudah diputuskan dipinda ke bawah agar mudah dibedakan dari yang baru.
     */
    public function verifQueueRank(string $stage): int
    {
        return match ($this->{self::verifStages()[$stage][0]}) {
            'revisi' => 0,
            'menunggu' => 1,
            'tolak' => 2,
            default => 3,
        };
    }

    /**
     * Apakah tahap ini masih relevan untuk ditangani oleh admin tahap tersebut.
     *
     * Syaratnya hanya urutan — semua tahap sebelumnya harus disetujui lebih dulu.
     * Keputusan tahap ini sendiri tidak dikunci, sehingga admin masih dapat
     * mengubah keputusan yang sudah dibuat. Namun, begitu tahap berikutnya sudah
     * diputuskan, mengubah kembali tahap ini akan membatalkan pekerjaan tahap
     * tersebut, jadi barisnya disembunyikan.
     */
    public function canVerifStage(string $stage): bool
    {
        $stages = self::verifStages();
        $order = self::verifStageOrder();
        $index = array_search($stage, $order, true);

        if ($index === false) {
            return false;
        }

        foreach (array_slice($order, 0, $index) as $previous) {
            if ($this->{$stages[$previous][0]} !== 'setuju') {
                return false;
            }
        }

        if ($this->{$stages[$stage][0]} === 'menunggu') {
            return true;
        }

        foreach (array_slice($order, $index + 1) as $downstream) {
            if ($this->{$stages[$downstream][0]} !== 'menunggu') {
                return false;
            }
        }

        return true;
    }

    public function resetVerification(): void
    {
        $stages = self::verifStages();

        foreach ($stages as $stage) {
            $this->{$stage[0]} = 'menunggu';
            $this->{$stage[1]} = null;
        }
    }

    /**
     * Reset semua tahap setelah tahap tertentu, karena urutan verifikasinya
     * jadi tidak berlaku lagi (mis. Capil ditarik kembali saat Kampus sudah
     * disetujui).
     */
    public function resetDownstreamStages(string $stage): void
    {
        $stages = self::verifStages();
        $order = self::verifStageOrder();
        $index = array_search($stage, $order, true);

        if ($index === false) {
            return;
        }

        foreach (array_slice($order, $index + 1) as $downstream) {
            $this->{$stages[$downstream][0]} = 'menunggu';
            $this->{$stages[$downstream][1]} = null;
        }
    }

    public function syncVerifStatusFromTables(): void
    {
        $verifKampus = VerifikasiKampus::where('mahasiswa_id', $this->id)
            ->latest('verified_at')
            ->first();

        $verifCapil = VerifikasiCapil::where('mahasiswa_id', $this->id)
            ->latest('verified_at')
            ->first();

        $verifKesra = VerifikasiKesra::where('mahasiswa_id', $this->id)
            ->latest('verified_at')
            ->first();

        if ($verifKampus) {
            $this->verif_kampus = $verifKampus->status;
            $this->catatan_kampus = $verifKampus->catatan;
        }

        if ($verifCapil) {
            $this->verif_capil = $verifCapil->status;
            $this->catatan_capil = $verifCapil->catatan;
        }

        if ($verifKesra) {
            $this->verif_kesra = $verifKesra->status;
            $this->catatan_kesra = $verifKesra->catatan;
        }

        $this->save();
    }

    public function verifyKampus(string $status, ?string $catatan = null, ?int $verifierId = null): VerifikasiKampus
    {
        $verif = VerifikasiKampus::updateOrCreate(
            ['mahasiswa_id' => $this->id, 'verifier_id' => $verifierId],
            [
                'pengajuan_id' => null,
                'status' => $status,
                'catatan' => $catatan,
                'verified_at' => now(),
            ]
        );

        $this->verif_kampus = $verif->status;
        $this->catatan_kampus = $verif->catatan;
        $this->save();

        return $verif;
    }

    public function verifyCapil(string $status, ?string $catatan = null, ?int $verifierId = null): VerifikasiCapil
    {
        $verif = VerifikasiCapil::updateOrCreate(
            ['mahasiswa_id' => $this->id, 'verifier_id' => $verifierId],
            [
                'pengajuan_id' => null,
                'status' => $status,
                'catatan' => $catatan,
                'verified_at' => now(),
            ]
        );

        $this->verif_capil = $verif->status;
        $this->catatan_capil = $verif->catatan;
        $this->save();

        return $verif;
    }

    public function verifyKesra(string $status, ?string $catatan = null, ?int $verifierId = null): VerifikasiKesra
    {
        $verif = VerifikasiKesra::updateOrCreate(
            ['mahasiswa_id' => $this->id, 'verifier_id' => $verifierId],
            [
                'pengajuan_id' => null,
                'status' => $status,
                'catatan' => $catatan,
                'verified_at' => now(),
            ]
        );

        $this->verif_kesra = $verif->status;
        $this->catatan_kesra = $verif->catatan;
        $this->save();

        return $verif;
    }
}
