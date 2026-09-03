<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fakultas extends Model
{
    use HasFactory;

    protected $table = 'fakultas';

    protected $fillable = [
        'kampus_id',
        'nama',
    ];

    public function kampus(): BelongsTo
    {
        return $this->belongsTo(Kampus::class);
    }

    public function prodi(): HasMany
    {
        return $this->hasMany(Prodi::class);
    }
}
