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
    ];

    protected function casts(): array
    {
        return [
            'batas_waktu' => 'date',
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
}
