<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris cakupan beasiswa = satu prodi dari Master Data.
 *
 * Menyimpan id prodi (bukan nama salinan), sehingga perubahan nama fakultas
 * atau kampus di Master Data otomatis ikut tanpa perlu menyunting beasiswa.
 */
class BeasiswaCakupan extends Model
{
    protected $table = 'beasiswa_cakupan';

    protected $fillable = [
        'beasiswa_id',
        'prodi_id',
    ];

    public function beasiswa(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class, 'beasiswa_id');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }
}
