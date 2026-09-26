<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    /**
     * The only role names the app recognises, mapped to their display label.
     *
     * @var array<string, string>
     */
    public const ROLE_LABELS = [
        'super_admin' => 'Super Admin',
        'kesra' => 'Kesra',
        'kampus' => 'Kampus',
        'capil' => 'Capil',
        'user' => 'User',
    ];

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
     * May this user open the Pengguna module at all (index, create, edit)?
     *
     * Breadth follows the menu grant, like every other admin module; only the
     * rules that protect the super_admin account itself stay on `hasRole`.
     */
    public function canManageUsers(): bool
    {
        return $this->hasMenuAccess('admin.pengguna.index');
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

    public function isProfileComplete(): bool
    {
        $profile = $this->profile;

        if (! $profile) {
            return false;
        }

        foreach ($this->getRequiredProfileFields() as $field => $label) {
            if (empty($profile->$field)) {
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
            if (empty($profile->$field)) {
                $missing[] = $label;
            }
        }

        return $missing;
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
            'telepon' => 'Telepon',
            'alamat' => 'Alamat',
            'kecamatan' => 'Kecamatan',
            'desa_kelurahan' => 'Desa/Kelurahan',
            'prodi_id' => 'Program Studi',
            'ipk' => 'IPK',
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
            'dokumen_sktm' => 'SKTM',
            'dokumen_transkrip' => 'Transkrip',
            'dokumen_surat_aktif' => 'Surat Aktif Kuliah',
            'dokumen_surat_pernyataan' => 'Surat Pernyataan',
            'dokumen_bukti_ukt' => 'Bukti UKT',
            'ktp_ayah' => 'KTP Ayah',
            'ktp_ibu' => 'KTP Ibu',
        ];

        if ($this->profile?->kk_ikut_wali) {
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
