<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Scholarship extends Model
{
    use HasFactory;

    protected $table = 'beasiswa';

    protected $fillable = [
        'nama',
        'kampus',
        'kuota',
        'tingkat_gelar',
        'tanggal_mulai',
        'tanggal_selesai',
        'ipk_minimal',
        'semester_minimal',
        'deskripsi',
        'persyaratan',
        'status',
        'result_announced_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'result_announced_at' => 'datetime',
            // D1-D4 ikut diterima sejalan dengan `profil_pengguna.jenjang`;
            // tanpa cast ini Eloquent menganggapnya string biasa dan
            // perbandingannya dengan jenjang profil tidak konsisten.
            'tingkat_gelar' => 'string',
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
     * Beasiswa yang boleh didaftar seorang mahasiswa, disaring mengikuti
     * cakupan prodi: seorang mahasiswa boleh mendaftar bila prodinya IKUT
     * TERCENTANG pada beasiswa itu.
     *
     * Beasiswa TANPA cakupan berarti terbuka untuk semua prodi (permintaan
     * pengguna: admin yang tidak mencentang apa pun tidak mengunci beasiswanya
     * dari semua orang).
     *
     * `$profile` null atau tanpa prodi berarti prodi mahasiswanya belum
     * diketahui, jadi hasil kosong, bukan semua.
     */
    public function scopeUntukMahasiswa(Builder $query, ?UserProfile $profile): Builder
    {
        $prodiId = $profile?->prodi_id;

        if (! $prodiId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(fn (Builder $inner) => $inner
            ->whereDoesntHave('cakupan')
            ->orWhereHas('cakupan', fn (Builder $c) => $c->where('prodi_id', $prodiId)));
    }

    /**
     * Cakupan prodi: satu baris per prodi yang boleh mendaftar.
     */
    public function cakupan(): HasMany
    {
        return $this->hasMany(BeasiswaCakupan::class, 'beasiswa_id');
    }

    /**
     * Kampus yang tercakup, diturunkan dari prodi terpilih.
     *
     * Dihitung di query, bukan disimpan, supaya tidak ada daftar kedua yang
     * bisa berbeda dengan cakupan prodinya.
     *
     * @return Collection<int, Kampus>
     */
    public function kampusTercakup(): Collection
    {
        return Kampus::query()
            ->whereIn('kampus.id', Prodi::query()
                ->join('fakultas', 'fakultas.id', '=', 'prodi.fakultas_id')
                ->whereIn('prodi.id', $this->cakupan()->pluck('prodi_id'))
                ->pluck('fakultas.kampus_id'))
            ->orderBy('nama_kampus')
            ->get();
    }

    public function isExpired(): bool
    {
        return $this->tanggal_selesai?->isPast() ?? true;
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

    /**
     * Jumlah pendaftar yang belum diputuskan Kesra.
     *
     * Selama angkanya bukan nol, hasil seleksi belum boleh diumumkan: kalau
     * diumumkan sekarang, pendaftar yang masih menunggu akan melihat
     * "sedang diproses" sementara yang lain sudah tahu hasilnya -- persis
     * keadaan yang ingin dihilangkan.
     */
    public function jumlahBelumDiputuskan(): int
    {
        return $this->pendaftar()
            ->where('status', Applicant::PENDING_STATUS)
            ->count();
    }

    /**
     * Boleh diumumkan bila ada minimal satu keputusan dan tidak ada lagi
     * pendaftar yang menggantung. Pendaftaran `dibatalkan` mahasiswa tidak
     * menghalangi karena itu bukan keputusan yang perlu diumumkan.
     */
    public function bolehDiumumkan(): bool
    {
        if ($this->result_announced_at !== null) {
            return false;
        }

        return $this->jumlahBelumDiputuskan() === 0
            && $this->pendaftar()->whereIn('status', ['diterima', 'ditolak'])->exists();
    }

    /**
     * Apakah prodi mahasiswa ini termasuk cakupan beasiswa.
     *
     * Beasiswa TANPA cakupan terbuka untuk semua prodi (permintaan pengguna).
     * Semantiknya harus sama persis dengan `scopeUntukMahasiswa()`, supaya
     * daftar beasiswa tidak menawarkan sesuatu yang detailnya menolak.
     */
    public function allowsProdi(UserProfile $profile): bool
    {
        if (! $profile->prodi_id) {
            return false;
        }

        $this->loadMissing('cakupan');

        if ($this->cakupan->isEmpty()) {
            return true;
        }

        return $this->cakupan->contains('prodi_id', $profile->prodi_id);
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
            // Angka dari kolom decimal(3,2) berbeda antar driver: MySQL
            // mengembalikan `3.50`, sqlite `3.5`. Format eksplisit supaya
            // pesannya sama di mana pun aplikasi dijalankan.
            return 'IPK minimal untuk beasiswa ini adalah '.number_format((float) $this->ipk_minimal, 2, '.', '').'.';
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
     * TANPA cakupan berarti terbuka untuk semua prodi (permintaan pengguna),
     * bukan untuk satu kampus saja: tidak ada lagi kampus yang "memiliki"
     * beasiswa ini.
     */
    public function cakupanLabel(): string
    {
        $jumlahProdi = $this->atributTerhitung('cakupan_count') ?? $this->cakupan()->count();

        if ($jumlahProdi === 0) {
            return 'Terbuka untuk semua program studi';
        }

        $jumlahKampus = $this->kampusTercakup()->count();

        return $jumlahProdi.' Program Studi • '.$jumlahKampus.' Kampus';
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
}
