<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Applicant extends Model
{
    use HasFactory;

    protected $table = 'pendaftar';

    public const STATUS_LABELS = [
        'verifikasi' => 'Verifikasi',
        'diterima' => 'Diterima',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];

    /**
     * Status yang menutup jalan pendaftaran baru. `ditolak` dan `dibatalkan`
     * justru membuka kembali, supaya mahasiswa boleh mencoba lagi.
     */
    public const STATUS_BLOCKING = ['verifikasi', 'diterima'];

    protected $fillable = [
        'user_id',
        'beasiswa_id',
        'fakultas',
        'prodi',
        'ipk',
        'semester',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beasiswa(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class, 'beasiswa_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'diterima' => 'success',
            'ditolak' => 'danger',
            'dibatalkan' => 'secondary',
            default => 'warning',
        };
    }

    /**
     * Sudah diputuskan Kesra? `dibatalkan` bukan keputusan, itu pilihan mahasiswa.
     */
    public function isDecided(): bool
    {
        return in_array($this->status, ['diterima', 'ditolak'], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'verifikasi';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'dibatalkan';
    }

    public function blocksNewApplication(): bool
    {
        return in_array($this->status, self::STATUS_BLOCKING, true);
    }

    /**
     * Alasan pendaftaran tidak bisa dibatalkan, atau `null` kalau masih boleh.
     *
     * Dua syarat, dan itu seluruhnya. Sumber kebenarannya status yang sudah
     * ada -- tidak ada status atau kolom baru untuk batas pembatalan:
     *
     *  1. Pendaftaran masih `verifikasi`. `diterima`/`ditolak` adalah
     *     keputusan Kesra, dan `dibatalkan` berarti sudah pernah dibatalkan.
     *  2. Masa pendaftaran beasiswa BELUM ditutup, yaitu
     *     `now() <= beasiswa.tanggal_selesai`.
     *
     * Batas waktu memakai `beasiswa.tanggal_selesai` karena itu batas yang
     * sama dengan yang sudah dipakai seluruh aplikasi untuk menentukan
     * "beasiswa ini masih menerima pendaftar" -- dipakai oleh `scopeTersedia()`
     * untuk daftar beasiswa dan oleh `eligibilityIssueFor()` untuk menolak
     * pendaftaran baru. Jadi "batas pembatalan" dan "beasiswa masih buka"
     * tidak mungkin berbeda, dan tidak ada angka baru yang harus dis-tuned.
     *
     * `pendaftar.created_at` sengaja TIDAK dipakai: `PendaftaranController::store()`
     * menghidupkan kembali baris yang dibatalkan alih-alih membuat baris baru
     * (index unik `[user_id, beasiswa_id]`), jadi `created_at` baris itu
     * berasal dari pendaftaran pertama dan tidak pernah di-reset. `updated_at`
     * juga tidak cocok: keputusan Kesra ikut memutusnya, jadi batasnya bisa
     * bergeser tanpa disengaja.
     *
     * Fail-safe: beasiswa yang hilang atau `tanggal_selesai`-nya null dianggap
     * sudah lewat batas, supaya data rusak tidak membuka jalan membatalkan
     * pendaftaran yang sebentar lagi diputuskan.
     */
    public function cancellationBlockedReason(): ?string
    {
        if ($this->isCancelled()) {
            return 'Pendaftaran ini sudah dibatalkan.';
        }

        if ($this->isDecided()) {
            return 'Pendaftaran ini sudah diputuskan sehingga tidak bisa dibatalkan.';
        }

        if (! $this->isActive()) {
            return 'Pendaftaran ini tidak sedang diproses sehingga tidak bisa dibatalkan.';
        }

        $deadline = $this->beasiswa?->tanggal_selesai;

        if ($deadline === null || $deadline->isPast()) {
            return 'Masa pendaftaran beasiswa ini sudah ditutup sehingga tidak bisa dibatalkan lagi.';
        }

        return null;
    }

    public function canBeCancelled(): bool
    {
        return $this->cancellationBlockedReason() === null;
    }

    /**
     * Data yang tercatat saat mendaftar sudah tidak sama dengan profil sekarang,
     * jadi keputusan Kesra sebaiknya melihat kedua angka, bukan cuma snapshot.
     *
     * @return array<int, string>
     */
    public function snapshotDrifts(?UserProfile $profile): array
    {
        if (! $profile) {
            return [];
        }

        $drifts = [];

        if ($this->ipk !== null && $profile->ipk !== null && (float) $this->ipk !== (float) $profile->ipk) {
            $drifts[] = "IPK saat mendaftar {$this->ipk}, sekarang {$profile->ipk}";
        }

        if ($this->semester !== null && $profile->semester !== null && (int) $this->semester !== (int) $profile->semester) {
            $drifts[] = "Semester saat mendaftar {$this->semester}, sekarang {$profile->semester}";
        }

        $currentProdi = $profile->prodi?->nama;

        if ($this->prodi && $currentProdi && $this->prodi !== $currentProdi) {
            $drifts[] = "Program Studi saat mendaftar {$this->prodi}, sekarang {$currentProdi}";
        }

        return $drifts;
    }
}
