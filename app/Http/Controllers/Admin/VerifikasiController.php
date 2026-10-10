<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CatpilVerifikasiExport;
use App\Exports\KampusVerifikasiExport;
use App\Exports\KesraVerifikasiExport;
use App\Exports\VerifikasiExport;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifikasiProfilRequest;
use App\Models\Applicant;
use App\Models\Fakultas;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ExcelDownload;
use App\Support\VerifikasiAntrean;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiController extends Controller
{
    use RespondsToAjax;

    private const FILTERS = ['menunggu', 'revisi', 'setuju', 'tolak'];

    /**
     * Kelas unduhan Excel per tahap. Isi dan cakupannya mengikuti antrean di
     * layar, jadi tiap verifikator hanya mengunduh datanya sendiri.
     *
     * @var array<string, class-string<VerifikasiExport>>
     */
    private const EXPORTS = [
        'catpil' => CatpilVerifikasiExport::class,
        'kampus' => KampusVerifikasiExport::class,
        'kesra' => KesraVerifikasiExport::class,
    ];

    public function index(string $stage): View
    {
        $stage = $this->validStage($stage);
        $filter = $this->validFilter($stage);

        // Kesra tidak punya daftar profil sendiri. Satu keputusan Kesra ada di baris
        // `pendaftar`, jadi daftar kerja Kesra adalah daftar pendaftaran yang perlu
        // diputuskan -- bukan daftar orang yang profilnya belum disentuh. Halamannya
        // terpisah karena bentuk datanya memang berbeda (kolom, aksi, dan filter
        // status), bukan karena salah satu dari keduanya perlu dialihkan.
        if ($stage === 'kesra') {
            return view('admin.verifikasi.kesra', [
                'stage' => $stage,
                'pendaftaran' => $this->antrean($stage)->pendaftaran($filter),
                'filter' => $filter,
            ]);
        }

        $antrean = $this->antrean($stage);

        return view('admin.verifikasi.index', [
            'stage' => $stage,
            'users' => $antrean->antrean($filter),
            'filter' => $filter,
        ]);
    }

    public function show(User $user, string $stage): View
    {
        $stage = $this->validStage($stage);
        $profile = $user->profile;

        // `inVerifQueue()`, bukan `canVerifStage()`: baris Kampus yang sudah
        // dikunci Kesra tetap bisa dibuka -- halamannya tampil read-only, bukan
        // 403. Gerbang tulis tetap di `canVerifStage()` (lihat `verifikasi()`).
        if (! $profile || ! $profile->inVerifQueue($stage)) {
            abort(403);
        }

        if ($stage === 'kampus' && $this->diLuarKampus($profile)) {
            abort(403);
        }

        return view('admin.verifikasi.lihat', compact('stage', 'user', 'profile'));
    }

    public function verifikasi(User $user, string $stage, VerifikasiProfilRequest $request): RedirectResponse|JsonResponse
    {
        $stage = $this->validStage($stage);
        $profile = $user->profile;

        if (! $profile || ! $profile->canVerifStage($stage)) {
            abort(403);
        }

        // Tahap Kesra tidak punya verdict profil sendiri lagi. Satu keputusan
        // Kesra ada di baris `pendaftar` dan ditulis lewat
        // `KeputusanPendaftaranController::update()`, yang sekaligus menutup
        // tahap ini. Endpoint di sini sengaja ditutup supaya tidak ada jalur
        // tulis kedua yang bisa memberi dua keputusan berbeda untuk satu
        // pendaftaran.
        if ($stage === 'kesra') {
            abort(403, 'Keputusan Kesra diambil dari decision Beasiswa pada halaman yang sama.');
        }

        if ($stage === 'kampus' && $this->diLuarKampus($profile)) {
            abort(403);
        }

        $data = $request->validated();

        $stages = UserProfile::verifStages();
        $profile->{$stages[$stage][0]} = $data['status'];
        $profile->{$stages[$stage][1]} = $data['catatan'] ?? null;

        // Tahap-tahap lain sengaja tidak disentuh: Catpil dan Kampus berjalan
        // paralel, jadi keputusan revisi/tolak di satu tahap tidak boleh
        // menghapus pekerjaan tahap lain yang sedang berjalan.
        $profile->save();

        // Penolakan Catpil/Kampus menutup gerbang tahap Kesra selamanya
        // (butuh `setuju` di keduanya), jadi pendaftaran yang masih menunggu
        // putusan tidak akan pernah diputuskan: kalau dibiarkan, baris
        // `verifikasi` itu memblokir pendaftaran beasiswa lain tanpa henti.
        // Pendaftaran tersebut ditutup jadi `ditolak` -- bukan keputusan
        // Kesra, hanya pembersihan karena profilnya gagal verifikasi.
        // `verif_kesra` tidak disentuh: sumber label tahap Kesra tetap baris
        // `pendaftar.status` (`User::kesraDecision()`).
        if ($data['status'] === 'tolak' && in_array($stage, ['catpil', 'kampus'], true)) {
            $user->applicants()
                ->where('status', Applicant::PENDING_STATUS)
                ->update(['status' => 'ditolak']);
        }

        $successAction = match ($data['status']) {
            'setuju' => 'setujui',
            'revisi' => 'minta perbaikan',
            'tolak' => 'tolak',
            default => 'tarik kembali',
        };

        return $this->ajaxOk(
            $request,
            "Verifikasi profil {$profile->nama_lengkap} berhasil di{$successAction}.",
            route($this->antrean($stage)->routeName())
        );
    }

    /**
     * Unduhan Excel antrean tahap ini.
     *
     * Otorisasi tetap datang dari `akses.menu`: nama route mengikuti prefix
     * tahap (`admin.catpil.*`, `admin.kampusverif.*`, `admin.kesra.*`) sehingga
     * grant menu yang sudah ada otomatis berlaku tanpa pengecualian baru.
     * Cakupan data diambil dari `VerifikasiAntrean`, jadi admin kampus tetap
     * hanya melihat kampusnya sendiri.
     */
    public function export(string $stage): StreamedResponse
    {
        $stage = $this->validStage($stage);

        $export = new (self::EXPORTS[$stage])(
            $this->antrean($stage),
            $this->validFilter($stage),
        );

        return ExcelDownload::response($export->toSpreadsheet(), $export->fileName());
    }

    /**
     * Halaman "Status Verifikasi": rekap status Catpil, Kampus, dan Kesra semua
     * profil, terbuka untuk Kesra, super_admin, Catpil, dan Kampus (menu leaf
     * mandiri, bukan anak dropdown Kesra, di section "Administrasi"). Bukan
     * antrean dan tidak punya kolom aksi: tidak ada tahap yang dikerjakan di
     * sini, hanya melihat status.
     *
     * Filter lokasi `kampus_id` / `fakultas_id` / `jurusan_id` (di mana
     * "Jurusan" adalah Prodi -- tidak ada tabel jurusan terpisah). Filter
     * status per tahap (`verif_catpil` / `verif_kampus`) DIHAPUS atas
     * permintaan pengguna: halaman ini rekap penuh, bukan daftar yang
     * disaring per tahap. Ketiga filter lokasi tidak pernah disabled
     * dan opsinya dinamis: Fakultas hanya memuat milik kampus yang terpilih,
     * Jurusan hanya milik fakultas yang terpilih, dan tanpa induk terpilih
     * daftarnya tampil penuh. Nilai yang tidak konsisten dengan induk yang
     * TURUT dipilih jatuh ke null (semua) agar kombinasi yang salah tidak
     * mengosongkan daftar -- tapi memilih fakultas/jurusan tanpa induknya
     * tetap valid, dan hanya `kampus_id` yang benar-benar menyaring daftar
     * pengguna sebagai induk tidak langsung Fakultas.
     */
    public function status(): View
    {
        $kampusId = $this->validChoice('kampus_id');
        $fakultasId = $this->validChoice('fakultas_id');
        $jurusanId = $this->validChoice('jurusan_id');

        // Soft coherence: level bawah hanya dicek terhadap induknya JIKA induk
        // itu ikut dipilih. Memilih fakultas tanpa kampus (atau jurusan tanpa
        // fakultas) tetap sah karena itu "memilih salah satu" filter; nilai yang
        // bertentangan dengan induk yang terpilih diabaikan, bukan mengosongkan
        // daftar.
        if ($kampusId !== null && $fakultasId !== null && ! Fakultas::query()
            ->where('id', $fakultasId)
            ->where('kampus_id', $kampusId)
            ->exists()) {
            $fakultasId = null;
        }

        if ($fakultasId !== null && $jurusanId !== null && ! Prodi::query()
            ->where('id', $jurusanId)
            ->where('fakultas_id', $fakultasId)
            ->exists()) {
            $jurusanId = null;
        }

        $users = User::role('user')
            ->where('status', 'aktif')
            ->whereHas('profile')
            ->with(['profile' => fn ($query) => $query->with('prodi.fakultas.kampus'), 'applicants'])
            ->when($kampusId, fn ($query) => $query->whereHas('profile.prodi.fakultas', fn ($fakultas) => $fakultas->where('kampus_id', $kampusId)))
            ->when($fakultasId, fn ($query) => $query->whereHas('profile.prodi', fn ($prodi) => $prodi->where('fakultas_id', $fakultasId)))
            ->when($jurusanId, fn ($query) => $query->whereHas('profile.prodi', fn ($prodi) => $prodi->where('id', $jurusanId)))
            ->get()
            ->sortBy(fn (User $user) => $user->profile->nama_lengkap)
            ->values();

        // Dropdown lokasi dinamis (bertingkat): Fakultas hanya memuat fakultas milik
        // kampus yang terpilih; Jurusan (Prodi) hanya memuat jurusan milik fakultas
        // yang terpilih, atau milik fakultas mana pun di kampus terpilih selama
        // fakultasnya belum dipilih. Tanpa induk terpilih, daftarnya tampil penuh
        // supaya admin bebas memilih filter apa pun.
        $kampusOptions = Kampus::query()->orderBy('nama_kampus')->get(['id', 'nama_kampus']);

        $fakultasOptions = Fakultas::query()
            ->when($kampusId, fn ($query) => $query->where('kampus_id', $kampusId))
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $jurusanOptions = Prodi::query()
            ->when($fakultasId, fn ($query) => $query->where('fakultas_id', $fakultasId))
            ->when($fakultasId === null && $kampusId !== null,
                fn ($query) => $query->whereHas('fakultas', fn ($fakultas) => $fakultas->where('kampus_id', $kampusId)))
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return view('admin.verifikasi.status', compact(
            'users',
            'kampusId', 'fakultasId', 'jurusanId',
            'kampusOptions', 'fakultasOptions', 'jurusanOptions',
        ));
    }

    private function validStage(string $stage): string
    {
        if (! in_array($stage, UserProfile::verifStageOrder(), true)) {
            abort(404);
        }

        return $stage;
    }

    private function validChoice(string $param): ?int
    {
        $value = filter_var(request($param), FILTER_VALIDATE_INT);

        return $value === false || $value < 1 ? null : $value;
    }

    /**
     * Filter status pada daftar verifikasi. Null berarti tidak difilter.
     *
     * Untuk Catpil dan Kampus, statusnya milik profil (`menunggu`/`revisi`/`setuju`/
     * `tolak`). Kesra memakai status yang sama dengan `Applicant` (`verifikasi`/
     * `diterima`/`ditolak`/`dibatalkan`), jadi daftar yang diizinkan berbeda per
     * tahap -- filter `setuju` tidak akan cocok dengan apa pun di Kesra.
     *
     * Semua tahap mulai dari "tanpa filter" (= semua status). Dulu Kesra bias ke
     * `verifikasi` (antrean putusan) sebagai default, tapi itu tidak lagi
     * (permintaan pengguna): halaman antrean Kesra kini menampilkan seluruh
     * pendaftaran, filter status tinggal mempersempitnya. "Semua status" memakai
     * nilai `Applicant::FILTER_ALL` karena `?filter=` sudah diubah jadi `null`
     * oleh `ConvertEmptyStringsToNull` sebelum sampai sini.
     */
    private function validFilter(string $stage): ?string
    {
        $filter = request('filter');

        if ($filter === null) {
            return null;
        }

        if ($filter === Applicant::FILTER_ALL) {
            return null;
        }

        $allowed = $stage === 'kesra'
            ? array_keys(Applicant::STATUS_LABELS)
            : self::FILTERS;

        return in_array($filter, $allowed, true) ? $filter : null;
    }

    private function antrean(string $stage): VerifikasiAntrean
    {
        return new VerifikasiAntrean($stage);
    }

    /**
     * Admin kampus hanya boleh menangani mahasiswa di kampusnya sendiri.
     */
    private function diLuarKampus(UserProfile $profile): bool
    {
        $kampusId = $this->antrean('kampus')->scopedKampusId();

        return $kampusId !== null && $profile->prodi?->fakultas?->kampus_id !== $kampusId;
    }
}
