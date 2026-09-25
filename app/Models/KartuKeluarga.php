<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KartuKeluarga extends Model
{
    use HasFactory;

    protected $table = 'kartu_keluarga';

    protected $fillable = [
        'no_kk',
        'kepala_keluarga',
        'alamat',
        'desil',
        'sumber_desil',
        'tanggal_update',
    ];

    public function anggota(): HasMany
    {
        return $this->hasMany(AnggotaKeluarga::class, 'kartu_keluarga_id');
    }

    public function getDesilAttribute($value)
    {
        return $value ?? 0;
    }
}
