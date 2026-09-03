<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kampus extends Model
{
    use HasFactory;

    protected $table = 'kampus';

    protected $fillable = [
        'nama_kampus',
    ];

    public function fakultas(): HasMany
    {
        return $this->hasMany(Fakultas::class);
    }
}
