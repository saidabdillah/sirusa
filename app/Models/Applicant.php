<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Applicant extends Model
{
    use HasFactory;

    protected $table = 'pendaftar';

    protected $fillable = [
        'user_id',
        'beasiswa_id',
        'fakultas',
        'prodi',
        'ipk',
        'semester',
        'dokumen_ktp',
        'dokumen_kk',
        'dokumen_surat_aktif',
        'dokumen_transkrip',
        'dokumen_surat_permohonan',
        'dokumen_pas_foto',
        'dokumen_prestasi',
        'dokumen_surat_pernyataan',
        'dokumen_sktm',
        'dokumen_bukti_ukt',
        'status',
        'hasil_pengumuman',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'dokumen_prestasi' => 'array',
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

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'verifikasi' => 'Verifikasi',
            'diterima' => 'Diterima',
            'revisi' => 'Revisi',
            'ditolak' => 'Ditolak',
            default => '-',
        };
    }

    public function isDiumumkan(): bool
    {
        return $this->status === 'diterima' 
            && $this->hasil_pengumuman === 'diterima' 
            && $this->beasiswa?->isPengumumanAktif();
    }

    public function isPengumumanBerlangsung(): bool
    {
        return $this->beasiswa?->isPengumumanAktif() ?? false;
    }
}
