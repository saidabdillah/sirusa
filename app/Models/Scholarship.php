<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Scholarship extends Model
{
    use HasFactory;

    protected $table = 'beasiswa';

    protected $fillable = [
        'nama',
        'kampus',
        'kampus_id',
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
        'pengumuman_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'batas_waktu' => 'date',
            'tanggal_pengumuman' => 'date',
            'tanggal_pengumuman_selesai' => 'date',
            'pengumuman_notified_at' => 'datetime',
        ];
    }

    public function pendaftar(): HasMany
    {
        return $this->hasMany(Applicant::class, 'beasiswa_id');
    }

    public function kampus(): BelongsTo
    {
        return $this->belongsTo(Kampus::class, 'kampus_id');
    }

    public function fakultas(): HasMany
    {
        return $this->hasMany(ScholarshipFakultas::class, 'beasiswa_id');
    }

    public function isExpired(): bool
    {
        return $this->batas_waktu?->isPast() ?? true;
    }

    public function sisaKuota(): int
    {
        $diterima = $this->pendaftar()->where('status', 'diterima')->count();

        return max((int) $this->kuota - $diterima, 0);
    }

    public function isPengumumanAktif(): bool
    {
        if (! $this->tanggal_pengumuman || ! $this->tanggal_pengumuman_selesai) {
            return false;
        }

        $today = now()->startOfDay();

        return $today->between(
            $this->tanggal_pengumuman->copy()->startOfDay(),
            $this->tanggal_pengumuman_selesai->copy()->endOfDay(),
        );
    }

    public function hasPengumuman(): bool
    {
        return $this->isPengumumanAktif()
            && $this->pendaftar()->where('status', 'diterima')->exists();
    }

    public function penerima()
    {
        return $this->pendaftar()
            ->where('status', 'diterima')
            ->with('user.profile');
    }

    public function allowsProdi(UserProfile $profile): bool
    {
        $prodi = $profile->prodi;

        if (! $prodi) {
            return false;
        }

        $allowedNames = $this->prodiSnapshotNames();

        if ($allowedNames->isNotEmpty()) {
            return $allowedNames->contains($prodi->nama);
        }

        return $this->kampus_id !== null
            && $prodi->fakultas?->kampus_id === $this->kampus_id;
    }

    public function eligibilityIssueFor(?UserProfile $profile): ?string
    {
        if ($this->isExpired()) {
            return 'Pendaftaran beasiswa sudah ditutup.';
        }

        if ($this->status !== 'aktif') {
            return 'Beasiswa ini sedang tidak aktif.';
        }

        if (! $profile || ! $profile->prodi_id) {
            return 'Profil Anda belum memiliki Program Studi.';
        }

        if (! $this->allowsProdi($profile)) {
            return 'Program Studi Anda tidak termasuk dalam beasiswa ini.';
        }

        if ((float) $profile->ipk < (float) $this->ipk_minimal) {
            return "IPK minimal untuk beasiswa ini adalah {$this->ipk_minimal}.";
        }

        if ((int) $profile->semester < (int) $this->semester_minimal) {
            return "Semester minimal untuk beasiswa ini adalah {$this->semester_minimal}.";
        }

        return null;
    }

    private function prodiSnapshotNames(): Collection
    {
        return $this->fakultas()->with('prodi')->get()
            ->flatMap(fn (ScholarshipFakultas $fakultas) => $fakultas->prodi->pluck('nama'))
            ->filter()
            ->values();
    }
}
