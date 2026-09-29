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
        'verifikasi' => 'Menunggu',
        'diterima' => 'Disetujui',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];

    /**
     * Warna badge per status, sebagai pasangan langsung dari `STATUS_LABELS`.
     * Digit status database (`verifikasi`, `diterima`, …) tidak pernah tampil
     * mentah di UI -- semua label dan warna lewat kedua peta ini.
     *
     * @var array<string, string>
     */
    public const STATUS_BADGES = [
        'verifikasi' => 'warning',
        'diterima' => 'success',
        'ditolak' => 'danger',
        'dibatalkan' => 'secondary',
    ];

    /**
     * Ikon per status untuk tampilan berkartu. Dipisah dari warna supaya
     * `@switch` per status tidak perlu ditulis ulang di tiap view.
     *
     * @var array<string, string>
     */
    public const STATUS_ICONS = [
        'verifikasi' => 'fa-clock',
        'diterima' => 'fa-check-circle',
        'ditolak' => 'fa-times-circle',
        'dibatalkan' => 'fa-ban',
    ];

    /**
     * Status yang menutup jalan pendaftaran baru. `ditolak` dan `dibatalkan`
     * justru membuka kembali, supaya mahasiswa boleh mencoba lagi.
     */
    public const STATUS_BLOCKING = ['verifikasi', 'diterima'];

    /**
     * Status pendaftaran yang belum diputuskan, jadi masih menjadi pekerjaan Kesra.
     *
     * Dipakai sebagai default daftar antrean Kesra dan sebagai status yang dicari
     * dashboard. Disebut konstanta, bukan menulis `'verifikasi'` di banyak tempat,
     * karena "menunggu" untuk tahap Kesra berarti sesuatu yang berbeda dari
     * "menunggu" pada tahap profil: di sana tidak ada keputusan sama sekali, di sini
     * produknya satu baris `pendaftar` yang belum diputuskan.
     */
    public const PENDING_STATUS = 'verifikasi';

    /**
     * Nilai `filter` di URL antrean Kesra yang berarti "semua status".
     *
     * Bukan string kosong, karena `ConvertEmptyStringsToNull` di middleware `web`
     * mengubah `?filter=` menjadi `null` -- sama dengan tidak mengirim `filter` sama
     * sekali. Padahal keduanya harus berbeda: tidak mengirim berarti antrean yang
     * masih menunggu, sedangkan nilai ini berarti seluruh arsip termasuk yang sudah
     * dibatalkan mahasiswa.
     */
    public const FILTER_ALL = 'semua';

    protected $fillable = [
        'user_id',
        'beasiswa_id',
        'fakultas',
        'prodi',
        'ipk',
        'semester',
        'status',
        'catatan',
        'diputuskan_at',
        'diputuskan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'diputuskan_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beasiswa(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class, 'beasiswa_id');
    }

    public function diputuskanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'warning';
    }

    public function statusIcon(): string
    {
        return self::STATUS_ICONS[$this->status] ?? 'fa-clock';
    }

    /**
     * Nama admin yang memutus pendaftaran ini, atau `null` untuk baris lama
     * yang diputuskan sebelum kolom `diputuskan_oleh` ada.
     */
    public function decidedByName(): ?string
    {
        return $this->diputuskan_oleh
            ? $this->diputuskanOleh?->name
            : null;
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
