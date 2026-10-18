<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu dokumen akademik milik mahasiswa: KRS atau KHS untuk satu semester,
 * beserta nilainya.
 *
 * KRS dan KHS adalah dua dokumen berbeda, jadi keduanya baris terpisah
 * (`jenis`), bukan dua kolom di satu baris. Menggabungkannya akan membuat nilai
 * KRS dan nilai KHS saling menimpa.
 *
 * Satu baris per (mahasiswa, jenis, semester) -- lihat unique index di
 * migrasinya. Mengunggah ulang semester yang sama memperbarui baris itu;
 * semester lain selalu menambah baris baru, sehingga dokumen semester
 * sebelumnya tidak pernah tertimpa.
 */
class DokumenAkademik extends Model
{
    protected $table = 'dokumen_akademik';

    public const JENIS_KRS = 'krs';

    public const JENIS_KHS = 'khs';

    /**
     * Label tampilan tiap jenis dokumen.
     *
     * @var array<string, string>
     */
    public const JENIS_LABELS = [
        self::JENIS_KRS => 'KRS',
        self::JENIS_KHS => 'KHS',
    ];

    protected $fillable = [
        'user_id',
        'jenis',
        'semester',
        'file',
        'nilai',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            // Sama seperti `profil_pengguna.ipk`: skala akademik yang sudah
            // dipakai aplikasi adalah 0-4. Tanpa cast, nilai dibaca sebagai
            // string dan perbandingannya di tampilan jadi tidak konsisten.
            'nilai' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jenisLabel(): string
    {
        return self::JENIS_LABELS[$this->jenis] ?? strtoupper((string) $this->jenis);
    }

    /**
     * Semua dokumen akademik satu mahasiswa, diurutkan per jenis lalu semester
     * supaya tampilannya stabil.
     *
     * @param  Builder<DokumenAkademik>  $query
     * @return Builder<DokumenAkademik>
     */
    public function scopeMilik(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
