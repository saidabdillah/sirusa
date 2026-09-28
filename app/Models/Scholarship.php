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

    /**
     * Hitungan cakupan untuk daftar: `fakultas_count` dan `prodi_total`.
     *
     * `withCount` nested (`withCount('fakultas.prodi')`) TIDAK bisa dipakai --
     * `withAggregate()` di framework ini memperlakukan satu string sebagai satu
     * nama relasi dan memanggil `$model->{'fakultas.prodi'}()`, yang tidak ada.
     * Jumlah prodi karena itu dihitung dengan subquery terkorelasi yang
     * menggabungkan `beasiswa_fakultas` ke `beasiswa_prodi`; dua-duanya
     * menjadi kolom SELECT di query yang sama, jadi daftar 9 kartu tetap satu
     * query, bukan satu query per kartu.
     */
    public function scopeWithHitunganCakupan(Builder $query): Builder
    {
        return $query
            ->withCount('fakultas')
            ->selectSub(
                ScholarshipFakultas::query()
                    ->join('beasiswa_prodi', 'beasiswa_prodi.fakultas_id', '=', 'beasiswa_fakultas.id')
                    ->selectRaw('count(*)')
                    ->whereColumn('beasiswa_fakultas.beasiswa_id', 'beasiswa.id'),
                'prodi_total'
            );
    }

    /**
     * Sisa kuota = kuota dikurangi yang sudah DITERIMA.
     *
     * Kalau pemanggil sudah menghitung pendaftar yang diterima lewat
     * `withCount(['pendaftar as penerima_diterima' => ...])`, angka itu dipakai
     * apa adanya supaya halaman daftar dan detail tidak menembak satu query
     * COUNT per pemanggilan `sisaKuota()`. Tanpa atribut itu, hasilnya sama
     * seperti sebelumnya: satu query.
     *
     * Perhatikan nama atributnya `penerima_diterima`, bukan
     * `penerima_diterima_count`: `withAggregate()` memakai alias apa adanya
     * sebagai nama kolom, tidak menambahkan sufiks `_count`.
     */
    public function sisaKuota(): int
    {
        $diterima = $this->atributTerhitung('penerima_diterima')
            ?? $this->pendaftar()->where('status', 'diterima')->count();

        return max((int) $this->kuota - (int) $diterima, 0);
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

    /**
     * Ringkasan cakupan untuk daftar dan halaman pengajuan.
     *
     * Tanpa daftar fakultas/prodi, `allowsProdi()` jatuh ke aturan "semua prodi
     * di kampus ini" -- jadi itu yang ditulis, bukan "semua kampus".
     * `kampus_id` null TIDAK berarti terbuka untuk semua kampus: `scopeUntukKampus()`
     * tidak akan mencocokkannya dengan siapa pun, sehingga beasiswa tersebut
     * tidak tampil untuk mahasiswa mana pun.
     */
    public function cakupanLabel(): string
    {
        $fakultas = $this->atributTerhitung('fakultas_count') ?? $this->fakultas()->count();
        $prodi = $this->atributTerhitung('prodi_total') ?? $this->jumlahProdiSnapshot();

        if ($fakultas === 0) {
            return $this->kampus
                ? 'Semua Program Studi di '.$this->kampus
                : 'Semua Program Studi';
        }

        return $fakultas.' Fakultas • '.$prodi.' Program Studi';
    }

    /**
     * Jumlah baris `beasiswa_prodi` milik beasiswa ini.
     *
     * Dipakai sebagai fallback kalau `scopeWithHitunganCakupan()` tidak
     * dipakai, dan oleh `jumlahProdiSnapshot()` yang dipanggil sekali saja --
     * bukan per baris di dalam loop.
     */
    public function jumlahProdiSnapshot(): int
    {
        $this->loadMissing('fakultas.prodi');

        return $this->fakultas->sum(fn (ScholarshipFakultas $f) => $f->prodi->count());
    }

    /**
     * Isi kolom `persyaratan` dipecah jadi butir daftar.
     *
     * Kolomnya TEXT bebas dari textarea admin, jadi isinya bisa satu kalimat
     * panjang atau sudah berbaris. Baris baru dipakai sebagai pemisah butir.
     * Penanda daftar di depan baris (`- `, `* `, `1. `) dibuang supaya tidak
     * tampil dobel bersama ikon checklist; penanda di TENGAH teks tidak
     * disentuh, jadi "IPK minimal 3.00" tetap utuh.
     *
     * @return Collection<int, string>
     */
    public function butirPersyaratan(): Collection
    {
        if (blank($this->persyaratan)) {
            return collect();
        }

        return collect(preg_split('/\R/u', trim($this->persyaratan)))
            ->map(fn (string $baris) => $this->bersihkanPenandaButir($baris))
            ->filter();
    }

    /**
     * Buang penanda daftar yang menempel di depan teks, berulang kali.
     *
     * Satu kali saja tidak cukup untuk baris seperti "1. -mahasiswa aktif",
     * yang muncul kalau admin menempelkan daftar bertingkat ke textarea.
     * Penanda angka harus diikuti spasi supaya "3.5 IPK" -- yang diikuti
     * spasi setelah "3." dan tidak akan ikut terpotong -- tetap aman.
     */
    private function bersihkanPenandaButir(string $baris): string
    {
        $baris = trim($baris);

        do {
            $sebelum = $baris;
            $baris = trim((string) preg_replace('/^(?:[-*•]+\s*|\d+[.)]\s+)/u', '', $baris));
        } while ($baris !== $sebelum);

        return $baris;
    }

    /**
     * Nilai dari `withCount()` kalau ada, `null` kalau tidak.
     *
     * Bedakan "tidak dihitung" dari "hitungannya 0" itu penting: cakupan yang
     * benar-benar kosong harus tampil "Semua Program Studi di ...", bukan
     * ikut jatuh ke fallback yang menembak query.
     */
    private function atributTerhitung(string $nama): ?int
    {
        return array_key_exists($nama, $this->attributes)
            ? (int) $this->attributes[$nama]
            : null;
    }

    private function prodiSnapshotNames(): Collection
    {
        $this->loadMissing('fakultas.prodi');

        return $this->fakultas
            ->flatMap(fn (ScholarshipFakultas $fakultas) => $fakultas->prodi->pluck('nama'))
            ->filter()
            ->values();
    }
}
