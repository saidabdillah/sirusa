<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifikasiCapil extends Model
{
    use HasFactory;

    protected $table = 'verifikasi_capil';

    protected $fillable = [
        'pengajuan_id',
        'mahasiswa_id',
        'verifier_id',
        'status',
        'catatan',
        'verified_at',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'pengajuan_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'mahasiswa_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }
}
