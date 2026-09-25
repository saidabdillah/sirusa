<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnggotaKeluarga extends Model
{
    use HasFactory;

    protected $table = 'anggota_keluarga';

    protected $fillable = [
        'kartu_keluarga_id',
        'nik',
        'nama',
        'hubungan',
        'status',
        'pekerjaan',
        'penghasilan',
        'pendidikan',
        'no_hp',
        'alamat',
        'is_wali',
    ];

    public function kartu_keluarga(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class, 'kartu_keluarga_id');
    }

    public function getHubunganLabelAttribute()
    {
        return match ($this->hubungan) {
            'AYAH' => 'Ayah',
            'IBU' => 'Ibu',
            'WALI' => 'Wali',
            'LAIN' => 'Lainnya',
            default => $this->hubungan,
        };
    }
}
