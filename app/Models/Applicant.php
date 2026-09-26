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

    public function canBeCancelled(): bool
    {
        return $this->isActive();
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
