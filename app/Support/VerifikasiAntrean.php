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
 * Semua angka memakai `UserProfile::inVerifQueue()` yang sama dengan pemfilteran
 * antrean di `VerifikasiController`. Menyalin aturan itu ke SQL dijamin membuat
 * dasbor berbeda dengan daftar yang benar-benar dibuka admin, jadi pemfilteran
 * tetap di PHP meski menambah query.
 *
 * Pengecualian hanya tahap `kesra`, karena itu satu-satunya tahap yang pekerjaannya
 * adalah memutuskan baris `pendaftar`, bukan memverifikasi profil. Di sana angka
 * dan antrean dihitung dari `pendaftar` -- lihat `pendaftarQuery()`.
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
            'catpil' => 'Verifikasi Catpil',
            'kampus' => 'Verifikasi Kampus',
            default => 'Verifikasi Kesra',
        };
    }

    public function actorLabel(): string
    {
        return match ($this->stage) {
            'catpil' => 'Catpil',
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
     * Kolom tabel untuk kartu "Menunggu Tindakan Anda" di dasbor tahap profil.
     *
     * Sengaja lebih sedikit dan berbeda dari halaman queue. Dua alasannya: kartu
     * dasbor hanya muat untuk tiga kolom, dan isinya harus yang jadi dasar
     * keputusan tahap tersebut. Catpil memeriksa data kependudukan, jadi prodi dan
     * kampus tidak relevan di sana -- menampilkannya membuat antrean itu terbaca
     * seperti daftar yang informasinya tidak pernah dipakai untuk memutuskan.
     *
     * Definisi lengkap ada di `admin/verifikasi/index.blade.php`; yang di sini
     * hanya potongan yang paling menentukan untuk tahap itu.
     *
     * @return array<int, array{label: string, value: callable(UserProfile): string}>
     */
    public function antreanKolom(): array
    {
        return [
            'catpil' => [
                ['label' => 'NIK', 'value' => fn (UserProfile $profile) => $profile->nik ?? '-'],
                ['label' => 'No. Kartu Keluarga', 'value' => fn (UserProfile $profile) => $profile->no_kk ?? '-'],
                ['label' => 'Desil', 'value' => fn (UserProfile $profile) => $profile->desil ? 'Desil '.$profile->desil : '-'],
            ],
            'kampus' => [
                ['label' => 'NIM', 'value' => fn (UserProfile $profile) => $profile->nim ?? '-'],
                ['label' => 'Program Studi', 'value' => fn (UserProfile $profile) => $profile->prodi?->nama ?? '-'],
                ['label' => 'IPK', 'value' => fn (UserProfile $profile) => $profile->ipk ?? '-'],
            ],
        ][$this->stage] ?? [];
    }

    /**
     * Antrean profil yang tampil di tahap ini, sudah diurutkan.
     *
     * Memakai `inVerifQueue()`, bukan `canVerifStage()`: baris Kampus yang
     * keputusannya dikunci Kesra tetap tampil di sini (read-only) supaya angka
     * dasbor, tabel antrean, dan export tidak pernah berbeda cakupan.
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
            ->filter(fn (User $user) => $user->profile && $user->profile->inVerifQueue($this->stage))
            ->filter(fn (User $user) => $filter === null
                || $user->profile->verifStageDecision($this->stage)['status'] === $filter)
            // `sortBy` menerima satu kunci, jadi pengurutannya dirantai dari yang
            // paling lemah. `sortBy` stabil, sehingga nama tetap menentukan
            // urutan di dalam rank yang sama.
            ->sortBy(fn (User $user) => $user->profile->nama_lengkap)
            ->sortBy(fn (User $user) => $user->profile->verifQueueRank($this->stage))
            ->values();
    }

    /**
     * Kolom status lintas tahap, untuk tabel "Sebaran Antrean per Tahap".
     *
     * Satu baris tabel merangkai beberapa tahap sekaligus, jadiheader-nya hanya
     * bisa punya satu daftar kolom -- padahal tahap Catpil/Kampus dan tahap Kesra
     * memakai kumpulan status yang berbeda. Jawabannya: satu daftar kolom tetap
     * dengan lebih dari satu kunci yang mungkin, dan sel yang tidak berlaku untuk
     * tahap tersebut ditampilkan sebagai `0` (permintaan pengguna; dulu tanda
     * hubung).
     *
     * @return array<int, array{label: string, keys: list<string>, icon: string, bg: string}>
     */
    public static function ringkasanKolom(): array
    {
        return [
            ['label' => 'Menunggu', 'keys' => ['menunggu', 'verifikasi'], 'icon' => 'fa-inbox', 'bg' => 'warning'],
            ['label' => 'Perlu Perbaikan', 'keys' => ['revisi'], 'icon' => 'fa-pen', 'bg' => 'info'],
            ['label' => 'Disetujui', 'keys' => ['setuju', 'diterima'], 'icon' => 'fa-check-circle', 'bg' => 'success'],
            ['label' => 'Ditolak', 'keys' => ['tolak', 'ditolak'], 'icon' => 'fa-times-circle', 'bg' => 'danger'],
            ['label' => 'Dibatalkan', 'keys' => ['dibatalkan'], 'icon' => 'fa-ban', 'bg' => 'secondary'],
        ];
    }

    /**
     * Kunci status yang dilaporkan tahap ini.
     *
     * @return list<string>
     */
    public function ringkasanKeys(): array
    {
        return $this->stage === 'kesra'
            ? ['verifikasi', 'diterima', 'ditolak', 'dibatalkan']
            : ['menunggu', 'revisi', 'setuju', 'tolak'];
    }

    /**
     * Label per status tahap ini. Label Kesra diambil dari
     * `Applicant::STATUS_LABELS` supaya tidak ada string yang ditulis ulang di
     * view; hanya `verifikasi` yang diganti karena "Menunggu" ambigu antara profil
     * dan pendaftaran.
     *
     * @return array<string, string>
     */
    public function ringkasanLabels(): array
    {
        return $this->stage === 'kesra'
            ? ['verifikasi' => 'Menunggu Putusan'] + Applicant::STATUS_LABELS
            : [
                'menunggu' => 'Menunggu Tindakan',
                'revisi' => 'Perlu Perbaikan',
                'setuju' => 'Disetujui',
                'tolak' => 'Ditolak',
            ];
    }

    /**
     * Stat card dasbor tahap ini: hanya kolom yang memang berlaku, lengkap dengan
     * label, ikon, dan warnanya. Diturunkan dari `ringkasanKolom()` supaya grid
     * ikhtisar dan stat card tidak mungkin punya daftar status yang berbeda.
     *
     * @return array<int, array{key: string, label: string, icon: string, bg: string}>
     */
    public function ringkasanColumns(): array
    {
        $keys = $this->ringkasanKeys();
        $labels = $this->ringkasanLabels();

        $columns = [];

        foreach (self::ringkasanKolom() as $column) {
            $key = current(array_intersect($column['keys'], $keys));

            if ($key === false) {
                continue;
            }

            $columns[] = [
                'key' => $key,
                'label' => $labels[$key] ?? $column['label'],
                'icon' => $column['icon'],
                'bg' => $column['bg'],
            ];
        }

        return $columns;
    }

    /**
     * Jumlah antrean per status, tanpa memuat daftar orangnya.
     *
     * Kunci hasil mengikuti `ringkasanKeys()` untuk tahap ini, jadi pemanggil
     * selalu tahu status mana yang ada dan mana yang tidak.
     *
     * @return array<string, int>
     */
    public function ringkasan(): array
    {
        $counts = array_fill_keys($this->ringkasanKeys(), 0);

        if ($this->stage === 'kesra') {
            $byStatus = $this->pendaftarQuery()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            foreach ($counts as $key => $ignored) {
                $counts[$key] = (int) ($byStatus[$key] ?? 0);
            }

            return $counts;
        }

        foreach ($this->antrean() as $user) {
            $status = $user->profile->verifStageDecision($this->stage)['status'];

            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /**
     * Jumlah pendaftar yang masih menunggu putusan Kesra.
     */
    public function pendaftaranMenunggu(): int
    {
        return $this->ringkasan()['verifikasi'] ?? 0;
    }

    /**
     * Profil yang sudah cukup terverifikasi untuk bisa diputuskan Kesra, yaitu
     * yang lolos verifikasi Kampus. Catpil tidak lagi menghalangi putusan Kesra
     * (permintaan pengguna: Kesra tidak menunggu Catpil).
     *
     * Ini diterjemahkan ke SQL, bukan disaring lewat `canVerifStage()`, dan itu
     * aman khusus untuk tahap `kesra`: `kesra` adalah tahap terakhir, jadi
     * `canVerifStage()` untuknya tidak punya aturan tambahan apa pun --
     * hanya flag kampus yang harus `setuju`. Kalau suatu hari ada tahap
     * setelah Kesra, aturan ini harus ikut diperbarui di sampingnya.
     */
    public function profilTerverifikasi(): int
    {
        return $this->stage === 'kesra'
            ? User::role('user')
                ->where('status', 'aktif')
                ->whereHas('profile', fn (Builder $query) => $query
                    ->where('verif_kampus', 'setuju'))
                ->count()
            : $this->ringkasan()['setuju'];
    }

    /**
     * Pendaftaran tahap `kesra`. Default param-nya status `verifikasi` (antrean
     * putusan) dan dipakai kartu "Menunggu Tindakan Anda" di dasbor; halaman
     * antrean Kesra lewat `VerifikasiController` mengirim `null` (= semua status)
     * supaya daftar default-nya menampilkan seluruh pendaftaran (permintaan
     * pengguna).
     *
     * Antrean ini tidak lagi mensyaratkan `verif_kesra = 'setuju'`. Dulu itu
     * syarat, padahal tahap profile Kesra ditutup oleh keputusan yang sama seperti
     * pendaftaran ini -- menyaring berdasarkan `verif_kesra` hanya akan
     * menyembunyikan pendaftar yang justru belum pernah diputuskan. Satu
     * keputusan, satu tempat: antrean ini.
     *
     * Filter status tidak dipindah ke `pendaftarQuery()`: ringkasan butuh semua
     * status untuk menghitung kartu Disetujui/Ditolak/Dibatalkan.
     *
     * @return Collection<int, Applicant>
     */
    public function pendaftaran(?string $status = Applicant::PENDING_STATUS): Collection
    {
        if ($this->stage !== 'kesra') {
            return collect();
        }

        return $this->pendaftarQuery($status)
            ->with(['beasiswa', 'user.profile.prodi.fakultas.kampus'])
            ->latest()
            ->get();
    }

    /**
     * Orang-orang di balik antrean pendaftaran, tanpa duplikat.
     *
     * Satu orang bisa punya beberapa pendaftaran, tapi Export Kesra menulis satu
     * baris per orang dan menggabungkan pendaftarannya di dalam sel. Kalau
     * pendaftarannya diambil dari `pendaftaran()` dan dikoleksi per user, satu
     * orang yang punya tiga pendaftaran akan muncul sebagai tiga baris dengan isi
     * yang sama.
     *
     * Relasi `applicants.beasiswa` ikut di-eager-load di sini karena pemanggilnya
     * cuma export Kesra, yang butuh seluruh pendaftaran milik tiap baris untuk
     * menyusun selnya. Halaman daftar sendiri memakai `pendaftaran()` dan tidak
     * pernah butuh relasi itu, jadi tidak ada query sia-sia di sana.
     *
     * @return Collection<int, User>
     */
    public function pendaftarUsers(?string $status = Applicant::PENDING_STATUS): Collection
    {
        // `pluck()` dipakai, bukan `pendaftaran()->pluck('user')`, supaya query
        // kedua di bawah tidak mengambil ulang graf profil yang sudah dimuat oleh
        // `pendaftaran()`. Urutannya tetap sama karena keduanya `latest()`.
        $urut = $this->stage === 'kesra'
            ? $this->pendaftarQuery($status)->latest()->pluck('user_id')->unique()->values()
            : collect();

        // `whereKey()` tidak menjaga urutan inputnya, jadi urutan antrean
        // ditrestore di sini: baris unduhan harus dibaca urut sama seperti tabel
        // yang sedang dibuka admin.
        return User::query()
            ->whereKey($urut)
            ->with(['profile.prodi.fakultas.kampus', 'applicants.beasiswa'])
            ->get()
            ->sortBy(fn (User $user) => $urut->search($user->id))
            ->values();
    }

    /**
     * Audiens kerja tahap `kesra`: baris `pendaftar` milik mahasiswa aktif yang
     * sudah lolos verifikasi Kampus. Catpil tidak lagi menghalangi putusan Kesra
     * (permintaan pengguna).
     *
     * Dipakai oleh daftar antrean, export, maupun hitungan ringkasan supaya
     * statistik dasbor, tabel antrean, dan unduhan tidak pernah menghitung cakupan
     * yang berbeda.
     *
     * Syaratnya persis `UserProfile::canVerifStage('kesra')` yang dipegang `show()`
     * -- hanya `verif_kampus = 'setuju'` -- jadi setiap baris yang tampil selalu
     * bisa dibuka tanpa membalas 403.
     */
    private function pendaftarQuery(?string $status = null): Builder
    {
        return Applicant::query()
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->whereHas('user', fn (Builder $query) => $query
                ->where('status', 'aktif')
                ->whereHas('profile', fn (Builder $profile) => $profile
                    ->where('verif_kampus', 'setuju')));
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
        if ($this->stage !== 'kesra') {
            return [
                'terverifikasi' => $this->profilTerverifikasi(),
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
            // `terverifikasi` dihitung dari profil yang lolos Kampus, bukan
            // dari `verif_kesra` seperti semula dan bukan dari Catpil+Kampus
            // (permintaan pengguna: Kesra tidak menunggu Catpil). `verif_kesra`
            // sekarang hanya menyatakan bahwa suatu pendaftaran sudah diputuskan,
            // jadi memakainya di sini akan membuat jumlah "profil terverifikasi"
            // ikut bergerak setiap kali Kesra memutus pendaftaran.
            'terverifikasi' => $this->profilTerverifikasi(),
            'pendaftar' => Applicant::query()
                ->whereHas('user', fn (Builder $query) => $query->where('status', 'aktif'))
                ->count(),
            'diterima' => (int) ($statusCounts['diterima'] ?? 0),
            'ditolak' => (int) ($statusCounts['ditolak'] ?? 0),
            'dibatalkan' => (int) ($statusCounts['dibatalkan'] ?? 0),
        ];
    }
}
