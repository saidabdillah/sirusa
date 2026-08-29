<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'status',
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

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
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
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'agama' => 'Agama',
            'telepon' => 'Telepon',
            'alamat' => 'Alamat',
            'status_orang_tua' => 'Status Orang Tua',
        ];

        $profile = $this->profile;
        $status = $profile?->status_orang_tua;

        if ($status === 'Lengkap') {
            $fields += [
                'nama_ayah' => 'Nama Ayah',
                'status_ayah' => 'Status Ayah',
                'pekerjaan_ayah' => 'Pekerjaan Ayah',
                'penghasilan_ayah' => 'Penghasilan Ayah',
                'nama_ibu' => 'Nama Ibu',
                'status_ibu' => 'Status Ibu',
                'pekerjaan_ibu' => 'Pekerjaan Ibu',
                'penghasilan_ibu' => 'Penghasilan Ibu',
            ];
        } elseif ($status === 'Yatim') {
            $fields += [
                'nama_ibu' => 'Nama Ibu',
                'status_ibu' => 'Status Ibu',
                'pekerjaan_ibu' => 'Pekerjaan Ibu',
                'penghasilan_ibu' => 'Penghasilan Ibu',
            ];
        } elseif ($status === 'Piatu') {
            $fields += [
                'nama_ayah' => 'Nama Ayah',
                'status_ayah' => 'Status Ayah',
                'pekerjaan_ayah' => 'Pekerjaan Ayah',
                'penghasilan_ayah' => 'Penghasilan Ayah',
            ];
        } elseif ($status === 'Yatim Piatu' || $status === 'Wali') {
            $fields += [
                'nama_wali' => 'Nama Wali',
                'hubungan_wali' => 'Hubungan Wali',
                'pekerjaan_wali' => 'Pekerjaan Wali',
                'penghasilan_wali' => 'Penghasilan Wali',
            ];
        }

        return $fields;
    }
}
