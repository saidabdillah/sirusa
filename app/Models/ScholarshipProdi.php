<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipProdi extends Model
{
    use HasFactory;

    protected $table = 'beasiswa_prodi';

    protected $fillable = [
        'fakultas_id',
        'nama',
    ];

    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(ScholarshipFakultas::class, 'fakultas_id');
    }
}
