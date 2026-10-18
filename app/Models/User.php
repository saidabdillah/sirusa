<?php

namespace App\Models;

use App\Support\MenuAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles;

    /**
     * The only role names the app recognises, mapped to their display label.
     *
     * @var array<string, string>
     */
    public const ROLE_LABELS = [
        'super_admin' => 'Super Admin',
        'kesra' => 'Kesra',
        'kampus' => 'Kampus',
        'catpil' => 'Catpil',
        'user' => 'User',
    ];

    /**
     * Field profil yang bernilai BOOLEAN, jadi tidak boleh diperiksa dengan
     * `empty()`.
     *
     * `empty(false)` bernilai `true`, sehingga jawaban "Tidak" pada
     * `kepala_keluarga` akan selamanya dianggap "belum diisi" dan mahasiswa itu
     * tidak pernah dianggap melengkapi profilnya. Untuk kolom di daftar ini,
     * "kosong" berarti `null` (belum dijawab), bukan `false`.
     *
     * @var list<string>
     */
    private const BOOLEAN_PROFILE_FIELDS = ['kepala_keluarga'];

    protected $fillable = [
        'username',
        'email',
        'nik',
        'password',
        'status',
        'kampus_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    /**
     * Pendaftaran yang masih menutup jalan pendaftaran baru. Satu mahasiswa hanya
     * boleh punya satu beasiswa, jadi `ditolak` dan `dibatalkan` tidak dihitung —
     * keduanya justru membuka jalan mencoba lagi.
     */
    public function blockingApplicant(): ?Applicant
    {
        return $this->applicants()
            ->whereIn('status', Applicant::STATUS_BLOCKING)
            ->latest('id')
            ->first();
    }

    public function canRegisterForScholarship(): bool
    {
        return $this->blockingApplicant() === null;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function kampus(): BelongsTo
    {
        return $this->belongsTo(Kampus::class);
    }

    public function menus(): Collection
    {
        return Menu::query()
            ->whereRelation('roles', fn ($q) => $q->whereIn('name', $this->getRoleNames()))
            ->where('aktif', true)
            ->with(['children' => fn ($q) => $q->where('aktif', true)->orderBy('urutan')])
            ->orderBy('urutan')
            ->get();
    }

    public function sidebarMenus(): Collection
    {
        return Menu::query()
            ->whereRelation('roles', fn ($q) => $q->whereIn('name', $this->getRoleNames()))
            ->where('aktif', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q
                ->where('aktif', true)
                ->whereRelation('roles', fn ($r) => $r->whereIn('name', $this->getRoleNames()))
                ->orderBy('urutan')])
            ->orderBy('urutan')
            ->get();
    }

    public function menuScopes(): Collection
    {
        return $this->menus()
            ->pluck('scope')
            ->filter()
            ->values();
    }

    public function hasMenuAccess(string $routeName): bool
    {
        return $this->menuScopes()->contains(fn (string $scope) => $this->menuScopeCovers($scope, $routeName));
    }

    /**
     * Boleh melakukan `$action` (read/create/edit/update/delete) pada modul `$scope`?
     *
     * Lapisan ini terpisah dari `hasMenuAccess()`: grant menu menentukan
     * apakah menu tampil dan halamannya bisa dibuka, sedangkan permission
     * Spatie (`<scope>.<aksi>`) menentukan aksi apa yang boleh dilakukan di
     * dalamnya. Modul non-CRUD tidak punya permission sama sekali, jadi
     * pemanggilnya harus memakai `hasMenuAccess()` untuk modul tersebut.
     *
     * `super_admin` selalu boleh: dialah satu-satunya akun yang tidak boleh
     * kehilangan akses, jadi aturannya dipatok di sini (bukan di UI) supaya
     * kombinasi permission yang salah tidak bisa mengunci sistem.
     */
    public function hasMenuAction(string $scope, string $action): bool
    {
        if (! MenuAction::isModule($scope)) {
            return false;
        }

        if ($this->hasRole('super_admin')) {
            return true;
        }

        // `getAllPermissions()` dipakai, BUKAN `hasPermissionTo()`: yang kedua
        // memanggil `Permission::findByName()` yang MELEMPAR
        // `PermissionDoesNotExist` kalau baris permission-nya belum di-seed.
        // Akibatnya setiap halaman admin jadi 500 alih-alih 403 di database
        // yang belum menjalankan `PermissionSeeder`. Di sini jawaban yang benar
        // untuk "permission tidak ada" adalah "tidak punya izin".
        return $this->getAllPermissions()
            ->contains('name', MenuAction::permissionName($scope, $action));
    }

    /**
     * `hasMenuAction()` untuk peran aktif; `false` bila tidak ada peran.
     */
    public function canAnyMenuAction(string $scope, array $actions): bool
    {
        foreach ($actions as $action) {
            if ($this->hasMenuAction($scope, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * May this user open the Pengguna module at all (index, create, edit)?
     *
     * Breadth follows the menu grant, like every other admin module; only the
     * rules that protect the super_admin account itself stay on `hasRole`.
     *
     * Sejak aksi granular ada, `read` adalah aksinya: memegang `read` berarti
     * boleh membuka modulnya, sedangkan tambah/ubah/hapus diperiksa terpisah
     * lewat `hasMenuAction()`. Dengan begitu UI dan backend tidak bisa berbeda
     * pendapat soal "boleh buka halaman ini".
     */
    public function canManageUsers(): bool
    {
        return $this->hasMenuAction('admin.pengguna', MenuAction::READ);
    }

    /**
     * Role names this user is allowed to hand out when creating or editing an account.
     *
     * @return list<string>
     */
    public function assignableRoles(): array
    {
        $roles = array_keys(self::ROLE_LABELS);

        return $this->hasRole('super_admin')
            ? $roles
            : array_values(array_diff($roles, ['super_admin']));
    }

    /**
     * Assignable roles as `value => label`, ready for a `<select>`.
     *
     * @return array<string, string>
     */
    public function assignableRoleOptions(): array
    {
        return array_intersect_key(self::ROLE_LABELS, array_flip($this->assignableRoles()));
    }

    public static function menuScopeCovers(string $scope, string $routeName): bool
    {
        return $routeName === $scope || str_starts_with($routeName, $scope.'.');
    }

    public static function usersGrantedMenu(string $scope): Collection
    {
        $menuIds = Menu::query()
            ->where('aktif', true)
            ->where('scope', $scope)
            ->pluck('id');

        $roleIds = DB::table('role_menu')
            ->whereIn('menu_id', $menuIds)
            ->pluck('role_id');

        return self::query()
            ->role($roleIds)
            ->where('status', 'aktif')
            ->orderBy('id')
            ->get();
    }

    /**
     * Hanya akun `user` (mahasiswa) yang punya baris profil isian + dokumen.
     * `super_admin`/`kesra`/`kampus`/`catpil` tidak punya profil, jadi halaman
     * profil untuk mereka hanya menampilkan informasi akun.
     */
    public function isMahasiswa(): bool
    {
        return $this->hasRole('user');
    }

    /**
     * Keputusan tahap Kesra untuk ditampilkan, diturunkan dari baris `pendaftar`.
     *
     * `verif_kesra` HANYA menandai "sudah diputuskan" (kunci tahap Kampus) dan
     * nilainya selalu `setuju` walau pendaftarannya DITOLAK. Karena itu label
     * tahap Kesra tidak boleh dibaca dari `verif_kesra` -- kalau dibaca dari
     * sana, penolakan tampil sebagai "Setuju" di halaman Status Verifikasi,
     * dasbor mahasiswa, dan export. Sumber kebenarannya baris `pendaftar.status`.
     *
     * Satu mahasiswa bisa punya beberapa pendaftaran: `diterima` menang, lalu
     * `ditolak`, dan selain itu (belum ada pendaftaran / masih `verifikasi` /
     * semua `dibatalkan`) berarti "Menunggu".
     *
     * Hanya keputusan yang SUDAH DIUMUMKAN (`decision_at` terisi) yang dihitung.
     * Sebelum pengumuman, tahap Kesra tetap "Menunggu" walau Kesra sudah
     * menekan setuju/tolak, supaya dasbor mahasiswa tidak membocorkan hasil
     * lebih awal daripada pendaftar lain.
     *
     * @return array{status: string, label: string, badge: string, decided: bool}
     */
    public function kesraDecision(): array
    {
        $applicants = $this->relationLoaded('applicants')
            ? $this->applicants
            : $this->applicants()->get();

        $status = match (true) {
            $applicants->contains('status', 'diterima') => 'setuju',
            $applicants->contains('status', 'ditolak') => 'tolak',
            default => 'menunggu',
        };

        return UserProfile::decisionForStatus($status);
    }

    /**
     * Versi `kesraDecision()` untuk mata mahasiswa: hanya keputusan yang sudah
     * DIUMUMKAN (`decision_at` terisi) yang dihitung.
     *
     * Halaman admin dan export tetap memakai `kesraDecision()` karena petugas
     * memang harus melihat keputusan yang belum diumumkan. Dasbor mahasiswa
     * memakai versi ini supaya hasil tidak bocor lebih awal daripada pendaftar
     * lain pada beasiswa yang sama.
     *
     * @return array{status: string, label: string, badge: string, decided: bool}
     */
    public function kesraDecisionVisible(): array
    {
        $applicants = $this->relationLoaded('applicants')
            ? $this->applicants
            : $this->applicants()->get();

        $announced = $applicants->whereNotNull('decision_at');

        $status = match (true) {
            $announced->contains('status', 'diterima') => 'setuju',
            $announced->contains('status', 'ditolak') => 'tolak',
            default => 'menunggu',
        };

        return UserProfile::decisionForStatus($status);
    }

    /**
     * Credential yang boleh dipakai untuk masuk, sesuai aturan SIRUSA.
     *
     * Mahasiswa punya NIK *dan* username, jadi bebas memakai salah satu. Akun
     * staf (super_admin/kesra/kampus/catpil) tidak punya baris profil, jadi
     * NIK-nya kosong -- menampilkan "login dengan NIK" untuk mereka hanya
     * membingungkan. Resolver backend sudah benar (NIK dari `profil_pengguna`
     * -> `users.username` -> `users.email`); ini cuma teks untuk ditampilkan.
     */
    public function loginCredentialLabel(): string
    {
        return $this->isMahasiswa() ? 'NIK atau username' : 'username';
    }

    public function isProfileComplete(): bool
    {
        $profile = $this->profile;

        if (! $profile) {
            return false;
        }

        foreach ($this->getRequiredProfileFields() as $field => $label) {
            if ($this->fieldProfilKosong($profile, $field)) {
                return false;
            }
        }

        return true;
    }

    public function getMissingProfileFields(): array
    {
        $profile = $this->profile;
        $missing = [];

        if (! $profile) {
            return array_values($this->getRequiredProfileFields());
        }

        foreach ($this->getRequiredProfileFields() as $field => $label) {
            if ($this->fieldProfilKosong($profile, $field)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * Apakah satu field profil masih dianggap kosong.
     *
     * `empty()` saja TIDAK cukup untuk kolom boolean: jawaban "Tidak" pada
     * `kepala_keluarga` bernilai `false`, dan `empty(false)` adalah `true` --
     * akibatnya mahasiswa yang menjawab "Tidak" akan selamanya dianggap
     * profilnya belum lengkap. Kolom boolean karena itu hanya dianggap kosong
     * kalau nilainya benar-benar `null` (belum dijawab).
     */
    private function fieldProfilKosong(UserProfile $profile, string $field): bool
    {
        if (in_array($field, self::BOOLEAN_PROFILE_FIELDS, true)) {
            return $profile->$field === null;
        }

        return empty($profile->$field);
    }

    private function getRequiredProfileFields(): array
    {
        $fields = [
            'nama_lengkap' => 'Nama Lengkap',
            'nik' => 'NIK',
            'no_kk' => 'No. KK',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'agama' => 'Agama',
            'telepon' => 'No. HP',
            'alamat' => 'Alamat',
            'kecamatan' => 'Kecamatan',
            'desa_kelurahan' => 'Desa/Kelurahan',
            'prodi_id' => 'Program Studi',
            'jenjang' => 'Jenjang Pendidikan',
            'tahun_masuk' => 'Tahun Masuk',
            'kepala_keluarga' => 'Kepala Keluarga',
            'ipk' => 'IPK (Indeks Prestasi Kumulatif)',
            'semester' => 'Semester',
            'ukt' => 'UKT',
            'desil' => 'Desil',
            'ikut_kk' => 'Ikut KK',
            'nama_ayah' => 'Nama Ayah',
            'nik_ayah' => 'NIK Ayah',
            'pekerjaan_ayah' => 'Pekerjaan Ayah',
            'nama_ibu' => 'Nama Ibu',
            'nik_ibu' => 'NIK Ibu',
            'pekerjaan_ibu' => 'Pekerjaan Ibu',
            'foto_profil' => 'Pas Foto',
            'dokumen_ktp' => 'KTP',
            'dokumen_kk' => 'Kartu Keluarga',
            'dokumen_desil' => 'Dokumen Desil',
            // SKTM tidak lagi disyaratkan; Transkrip digantikan PDDIKTI.
            'dokumen_pddikti' => 'Dokumen PDDIKTI',
            'dokumen_surat_aktif' => 'Surat Aktif Kuliah',
            'dokumen_surat_pernyataan' => 'Surat Pernyataan',
            'dokumen_bukti_ukt' => 'Bukti UKT',
            'ktp_ayah' => 'KTP Ayah',
            'ktp_ibu' => 'KTP Ibu',
        ];

        if ($this->profile?->kk_ikut_famili_lain) {
            $fields += [
                'nama_wali' => 'Nama Wali',
                'nik_wali' => 'NIK Wali',
                'hubungan_wali' => 'Hubungan Wali',
                'pekerjaan_wali' => 'Pekerjaan Wali',
                'ktp_wali' => 'KTP Wali',
                'kk_wali' => 'KK Wali',
            ];
        }

        return $fields;
    }
}
