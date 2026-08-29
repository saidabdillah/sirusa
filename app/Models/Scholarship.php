<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scholarship extends Model
{
    use HasFactory;

    protected $table = 'beasiswa';

    protected $fillable = [
        'nama',
        'kampus',
        'kuota',
        'tingkat_gelar',
        'cakupan',
        'batas_waktu',
        'ipk_minimal',
        'semester_minimal',
        'deskripsi',
        'persyaratan',
        'status',
        'tanggal_pengumuman',
        'tanggal_pengumuman_selesai',
    ];

    protected function casts(): array
    {
        return [
            'batas_waktu' => 'date',
            'tanggal_pengumuman' => 'date',
            'tanggal_pengumuman_selesai' => 'date',
        ];
    }

    public function pendaftar(): HasMany
    {
        return $this->hasMany(Applicant::class, 'beasiswa_id');
    }

    public function fakultas(): HasMany
    {
        return $this->hasMany(ScholarshipFakultas::class, 'beasiswa_id');
    }

    public function isExpired(): bool
    {
        return $this->batas_waktu?->isPast() ?? true;
    }

    public function isDiumumkan(): bool
    {
        return filled($this->tanggal_pengumuman);
    }

    public function isPengumumanAktif(): bool
    {
        if (! $this->tanggal_pengumuman || ! $this->tanggal_pengumuman_selesai) {
            return false;
        }

        $now = now('Asia/Jakarta');
        $start = $this->tanggal_pengumuman;
        $end = $this->tanggal_pengumuman_selesai->endOfDay();

        return $now->gte($start) && $now->lte($end);
    }
}
