<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScholarshipFakultas extends Model
{
    use HasFactory;

    protected $table = 'beasiswa_fakultas';

    protected $fillable = [
        'beasiswa_id',
        'nama',
    ];

    public function beasiswa(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class, 'beasiswa_id');
    }

    public function prodi(): HasMany
    {
        return $this->hasMany(ScholarshipProdi::class, 'fakultas_id');
    }
}
