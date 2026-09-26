<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'tanggal_mulai',
        'tanggal_selesai',
        'ipk_minimal',
        'semester_minimal',
        'deskripsi',
        'persyaratan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function pendaftar(): HasMany
    {
        return $this->hasMany(Applicant::class, 'beasiswa_id');
    }

    /**
     * Beasiswa yang masih menerima pendaftar. dipakai daftar mahasiswa dan
     * dasbor sekaligus, supaya "tersedia" di dua tempat berarti hal yang sama.
     */
    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('status', 'aktif')->whereDate('tanggal_selesai', '>=', now());
    }

    /**
     * Beasiswa milik satu kampus. Kalau daftar prodi pada beasiswa diisi, itu
     * yang jadi acuan, bukan kampus -- supaya mahasiswa tetap bisa melihat
     * beasiswa yang memang sengaja dibuka untuk prodinya.
     *
     * `$kampusId` null berarti kampus mahasiswanya belum diketahui, jadi tidak
     * ada beasiswa yang bisa dipastikan miliknya: hasil kosong, bukan semua.
     */
    public function scopeUntukKampus(Builder $query, ?int $kampusId): Builder
    {
        if (! $kampusId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $inner) use ($kampusId) {
            $inner->where('kampus_id', $kampusId)
                ->orWhereHas('fakultas', fn ($q) => $q
                    ->whereHas('prodi', fn ($p) => $p->whereHas('fakultas', fn ($f) => $f->where('kampus_id', $kampusId))));
        });
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
        return $this->tanggal_selesai?->isPast() ?? true;
    }

    public function sisaKuota(): int
    {
        $diterima = $this->pendaftar()->where('status', 'diterima')->count();

        return max((int) $this->kuota - $diterima, 0);
    }

    public function penerima()
    {
        return $this->pendaftar()
            ->where('status', 'diterima')
            ->with('user.profile.prodi.fakultas.kampus');
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

        if ($this->sisaKuota() <= 0) {
            return 'Kuota beasiswa ini sudah penuh.';
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
