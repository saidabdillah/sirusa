<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * NIK yang boleh langsung aktif saat mendaftar.
 *
 * Dipakai oleh `RegisterController` (akun baru langsung `aktif`) dan
 * `AuthController` (akun `non-aktif` yang NIK-nya baru didaftarkan belakangan
 * ikut diaktifkan saat ia berhasil masuk).
 */
class NikDiizinkan extends Model
{
    protected $table = 'nik_diizinkan';

    protected $fillable = ['nik'];

    /**
     * Apakah NIK ini terdaftar sebagai NIK yang boleh aktif otomatis.
     */
    public static function mengizinkan(?string $nik): bool
    {
        if ($nik === null || $nik === '') {
            return false;
        }

        return static::query()->where('nik', $nik)->exists();
    }
}
