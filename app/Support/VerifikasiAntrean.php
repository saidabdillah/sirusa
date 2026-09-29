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
     * Kolom tabel untuk kartu "Menunggu Tindakan Anda" di dasbor tahap profil.
     *
     * Sengaja lebih sedikit dan berbeda dari halaman queue. Dua alasannya: kartu
     * dasbor hanya muat untuk tiga kolom, dan isinya harus yang jadi dasar
     * keputusan tahap tersebut. Capil memeriksa data kependudukan, jadi prodi dan
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
            'capil' => [
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
     * bisa punya satu daftar kolom -- padahal tahap Capil/Kampus dan tahap Kesra
     * memakai kumpulan status yang berbeda. Jawabannya: satu daftar kolom tetap
     * dengan lebih dari satu kunci yang mungkin, dan sel yang tidak berlaku untuk
     * tahap tersebut ditampilkan sebagai tanda hubung, bukan `0`. Angka nol berarti
     * "tidak ada", sedangkan tanda hubung berarti "tidak berlaku" -- keduanya beda
     * dan tidak boleh dicampur.
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
     * Profil yang sudah cukup terverifikasi untuk bisa didaftarkan beasiswa, yaitu
     * yang lolos Capil dan Kampus.
     *
     * Ini diterjemahkan ke SQL, bukan disaring lewat `canVerifStage()`, dan itu
     * aman khusus untuk tahap `kesra`: `kesra` adalah tahap terakhir, jadi
     * `canVerifStage()` untuknya tidak punya aturan tambahan apa pun --
     * hanya dua flag sebelumnya yang harus `setuju`. Kalau suatu hari ada tahap
     * setelah Kesra, aturan ini harus ikut diperbarui di sampingnya.
     */
    public function profilTerverifikasi(): int
    {
        return $this->stage === 'kesra'
            ? User::role('user')
                ->where('status', 'aktif')
                ->whereHas('profile', fn (Builder $query) => $query
                    ->where('verif_capil', 'setuju')
                    ->where('verif_kampus', 'setuju'))
                ->count()
            : $this->ringkasan()['setuju'];
    }

    /**
     * Pendaftaran yang masih menjadi pekerjaan tahap `kesra`, default-nya yang
     * statusnya `verifikasi`.
     *
     * Antrean ini tidak lagi mensyaratkan `verif_kesra = 'setuju'`. Dulu itu
     * syarat, padahal tahap profile Kesra ditutup oleh keputusan yang sama seperti
     * pendaftaran ini -- menyaring berdasarkan `verif_kesra` hanya akan
     * menyembunyikan pendaftar yang justru belum pernah diputuskan. Satu
     * keputusan, satu tempat: antrean ini.
     *
     * Filter status punya default `verifikasi` dengan sengaja, dan tidak boleh
     * dipindah ke `pendaftarQuery()`: ringkasan butuh semua status untuk menghitung
     * kartu Disetujui/Ditolak/Dibatalkan. Tanpa pemisahan itu, tabel "menunggu
     * putusan" akan ikut menampilkan pendaftaran yang sudah diputuskan.
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
     * sudah lolos verifikasi Capil dan Kampus.
     *
     * Dipakai oleh daftar antrean, export, maupun hitungan ringkasan supaya
     * statistik dasbor, tabel antrean, dan unduhan tidak pernah menghitung cakupan
     * yang berbeda.
     *
     * Kedua flag profil itu wajib, bukan hanya `verif_kampus`. Alasannya
     * `UserProfile::canVerifStage('kesra')` yang dipegang `show()` juga menyyaratkan
     * keduanya, jadi tanpa syarat Capil di sini daftar bisa menampilkan baris yang
     * justru membalas 403 saat diklik -- antrean yang isinya tidak bisa dikerjakan.
     */
    private function pendaftarQuery(?string $status = null): Builder
    {
        return Applicant::query()
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->whereHas('user', fn (Builder $query) => $query
                ->where('status', 'aktif')
                ->whereHas('profile', fn (Builder $profile) => $profile
                    ->where('verif_capil', 'setuju')
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
            // `terverifikasi` dihitung dari profil yang lolos Capil+Kampus, bukan
            // dari `verif_kesra` seperti semula. `verif_kesra` sekarang hanya
            // menyatakan bahwa suatu pendaftaran sudah diputuskan, jadi
            // memakainya di sini akan membuat jumlah "profil terverifikasi" ikut
            // bergerak setiap kali Kesra memutus pendaftaran.
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
