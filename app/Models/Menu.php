<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'label',
        'icon',
        'route',
        'scope',
        'section',
        'urutan',
        'aktif',
        'wajib',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'wajib' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('urutan');
    }

    /**
     * Label grup di sidebar, dalam urutan tampil.
     *
     * Dipakai tiga tempat sekaligus: `layouts/partials/sidebar.blade.php`
     * (urutan + teks `menu-header`), `StoreMenuRequest`/`UpdateMenuRequest`
     * (`Rule::in`), dan `MenuSeeder`. Karena bukan foreign key, mengganti
     * string di sini TIDAK memperbaiki baris `menus.section` yang sudah ada --
     * `MenuSeeder` yang memindahkannya (idempoten lewat
     * `updateOrCreate(['section', 'label', 'parent_id'])` + `buangMenuLama()`).
     *
     * PENTING: sidebar hanya mengiterasi daftar ini. Menu dengan `section`
     * yang tidak terdaftar di sini TIDAK AKAN muncul sama sekali, bukan hanya
     * salah tempat -- jadi setiap section baru wajib ditambah ke daftar ini,
     * bukan cuma dipakai di seeder.
     *
     * Urutan mengikuti alur kerja: operasional harian (Administrasi) ->
     * antrean verifikasi -> alat sistem (Administrator) -> menu mahasiswa.
     * `Administrasi` sengaja masih memuat "Master Data": data master
     * kampus adalah back-office, sama seperti Beasiswa dan Pendaftar.
     * `Pengaturan` dan `Manajemen` belum dipakai `MenuSeeder`; keduanya tetap
     * disimpan karena bisa dipilih saat menambah menu baru.
     *
     * @return list<string>
     */
    public static function sections(): array
    {
        return [
            'Menu Utama',
            'Administrasi',
            'Verifikasi',
            'Administrator',
            'Layanan Mahasiswa',
            'Pengaturan',
            'Manajemen',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu');
    }
}
