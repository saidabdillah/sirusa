<?php

namespace App\Support;

use App\Models\Applicant;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal untuk hitungan antrean verifikasi dan pendaftaran.
 *
 * Semua angka memakai `UserProfile::canVerifStage()` yang sama dengan pemfilteran
 * antrean di `VerifikasiController`. Menyalin aturan itu ke SQL dijamin membuat
 * dasbor berbeda dengan daftar yang benar-benar dibuka admin, jadi pemfilteran
 * tetap di PHP meski menambah query.
 */
class VerifikasiAntrean
{
    public function __construct(private readonly string $stage) {}

    /**
     * Prefix route antrean tahap ini.
     */
    public function routeName(string $action = 'index'): string
    {
        $prefix = UserProfile::verifRoutePrefixes()[$this->stage] ?? 'kesra';

        return "admin.{$prefix}.{$action}";
    }

    public function label(): string
    {
        return match ($this->stage) {
            'capil' => 'Verifikasi Capil',
            'kampus' => 'Verifikasi Kampus',
            default => 'Verifikasi Kesra',
        };
    }

    public function actorLabel(): string
    {
        return match ($this->stage) {
            'capil' => 'Capil',
            'kampus' => 'Kampus',
            default => 'Kesra',
        };
    }

    /**
     * Hanya admin tahap `kampus` yang bekerja per kampus. Tahap lain
     * melihat seluruh wilayah.
     */
    public function scopedKampusId(): ?int
    {
        $user = auth()->user();

        if ($this->stage !== 'kampus' || ! $user?->hasRole('kampus')) {
            return null;
        }

        return $user->kampus_id;
    }

    /**
     * Antrean profil yang masih boleh ditangani tahap ini, sudah diurutkan.
     *
     * @return Collection<int, User>
     */
    public function antrean(?string $filter = null): Collection
    {
        $kampusId = $this->scopedKampusId();

        return User::role('user')
            ->where('status', 'aktif')
            ->with(['profile' => fn ($query) => $query->with('prodi.fakultas.kampus')])
            ->when($kampusId, fn (Builder $query) => $query->whereHas('profile.prodi.fakultas', fn ($q) => $q->where('kampus_id', $kampusId)))
            ->get()
            ->filter(fn (User $user) => $user->profile && $user->profile->canVerifStage($this->stage))
            ->filter(fn (User $user) => $filter === null
                || $user->profile->verifStageDecision($this->stage)['status'] === $filter)
            ->sortBy([
                fn (User $user) => $user->profile->verifQueueRank($this->stage),
                fn (User $user) => $user->profile->nama_lengkap,
            ])
            ->values();
    }

    /**
     * Jumlah antrean per status, tanpa memuat daftar orangnya.
     *
     * @return array<string, int>
     */
    public function ringkasan(): array
    {
        $counts = [
            'menunggu' => 0,
            'revisi' => 0,
            'setuju' => 0,
            'tolak' => 0,
        ];

        foreach ($this->antrean() as $user) {
            $status = $user->profile->verifStageDecision($this->stage)['status'];
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    public function menunggu(): int
    {
        return $this->ringkasan()['menunggu'];
    }

    /**
     * Pendaftaran yang menunggu keputusan Kesra. Hanya bermakna di tahap `kesra`,
     * karena di tahap lain belum ada yang diputuskan.
     *
     * @return Collection<int, Applicant>
     */
    /**
     * @return Collection<int, Applicant>
     */
    public function pendaftaran(): Collection
    {
        if ($this->stage !== 'kesra') {
            return collect();
        }

        return Applicant::with(['beasiswa', 'user.profile'])
            ->where('status', 'verifikasi')
            ->whereHas('user', fn (Builder $query) => $query
                ->where('status', 'aktif')
                ->whereHas('profile', fn ($q) => $q->where('verif_kesra', 'setuju')))
            ->latest()
            ->get();
    }

    public function pendaftaranMenunggu(): int
    {
        return $this->pendaftaran()->count();
    }

    /**
     * Corong dua tahap: profil terverifikasi, lalu pendaftaran, lalu hasilnya.
     * Pendaftaran yang sudah dibatalkan mahasiswa dihitung sebagai selesai dengan
     * hasil "dibatalkan" supaya mahasiswanya tidak terlihat menggantung.
     *
     * @return array<string, int>
     */
    public function funnel(): array
    {
        $summary = $this->ringkasan();

        if ($this->stage !== 'kesra') {
            return [
                'terverifikasi' => $summary['setuju'],
                'pendaftar' => 0,
                'diterima' => 0,
                'ditolak' => 0,
                'dibatalkan' => 0,
            ];
        }

        $statusCounts = Applicant::query()
            ->whereIn('status', ['diterima', 'ditolak', 'dibatalkan'])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'terverifikasi' => $summary['setuju'],
            'pendaftar' => Applicant::query()
                ->whereHas('user', fn (Builder $query) => $query->where('status', 'aktif'))
                ->count(),
            'diterima' => (int) ($statusCounts['diterima'] ?? 0),
            'ditolak' => (int) ($statusCounts['ditolak'] ?? 0),
            'dibatalkan' => (int) ($statusCounts['dibatalkan'] ?? 0),
        ];
    }
}
