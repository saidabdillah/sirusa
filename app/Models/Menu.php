<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

class Menu extends Model
{
    use HasFactory;

    /**
     * Section bawaan: selalu tersedia walau belum dipakai menu mana pun,
     * supaya form Tambah/Ubah tidak pernah kehabisan pilihan awal.
     *
     * @var list<string>
     */
    private const DEFAULT_SECTIONS = [
        'Menu Utama',
        'Administrasi',
        'Verifikasi',
        'Administrator',
        'Layanan Mahasiswa',
        'Pengaturan',
        'Manajemen',
    ];

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
     * URL yang dipakai sidebar untuk menu daun.
     *
     * `route` bukan field form -- superadmin hanya membuat menu tampilan,
     * arah link-nya diisi developer lewat koding (`MenuSeeder`/`tinker`). Jadi
     * saat render bisa terjadi tiga kondisi:
     *
     * 1. `route` terisi dan terdaftar -> `route()` seperti biasa;
     * 2. `route` terisi tapi belum terdaftar (koding belum lengkap) -> URL
     *    diturunkan dari nama route (`admin.x.y` -> `/admin/x/y`) yang akan
     *    berujung 404 -- menu tetap bisa diklik, bukan `<span>` mati;
     * 3. `route` masih NULL (belum dikoding) -> `href="#"`; link tidak
     *    melakukan navigasi (inert), bukan ke halaman 404.
     *
     * Sengaja mengembalikan string (bukan null): sidebar selalu merender `<a>`.
     */
    public function linkUrl(): string
    {
        if (filled($this->route) && Route::has($this->route)) {
            return route($this->route);
        }

        $slug = trim(str_replace('.', '/', (string) $this->route), '/');

        return $slug === '' ? '#' : url('/'.$slug);
    }

    /**
     * Label grup di sidebar, dalam urutan tampil.
     *
     * Dipakai tiga tempat sekaligus: `layouts/partials/sidebar.blade.php`
     * (urutan + teks `menu-header`), `StoreMenuRequest`/`UpdateMenuRequest`
     * (`required|string` -- `Rule::in` sudah DIHAPUS karena admin boleh membuat
     * section baru langsung dari form), dan `MenuSeeder`.
     *
     * Bukan foreign key, dan bukan lagi allow-list: hasilnya = `DEFAULT_SECTIONS`
     * (urutannya tetap) digabung section yang benar-benar dipakai baris
     * `menus`, sehingga section yang dibuat admin otomatis ikut tampil di
     * sidebar tanpa menyentuh kode. Bagian DB diurutkan kemunculan pertama
     * (menu diurutkan `urutan` lalu `label`) supaya urutannya deterministik.
     *
     * TANPA cache `static`: suite Pest berjalan dalam satu proses PHP, jadi
     * cache statis akan membawa section dari database test sebelumnya
     * (RefreshDatabase). Tabelnya kecil, query distinct per panggilan murah.
     *
     * Sidebar tetap HANYA mengiterasi daftar ini, jadi union dengan isi
     * database inilah yang menjamin menu tidak pernah "hilang tanpa error"
     * hanya karena section-nya tidak terdaftar.
     *
     * @return list<string>
     */
    public static function sections(): array
    {
        $dariDatabase = static::query()
            ->orderBy('urutan')
            ->orderBy('label')
            ->pluck('section')
            ->filter()
            ->unique()
            ->values();

        return array_values(array_unique(array_merge(
            self::DEFAULT_SECTIONS,
            $dariDatabase->all(),
        )));
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu');
    }

    /**
     * Apakah `$value` angka yang menunjuk baris `menus` yang benar-benar ada?
     *
     * Dipakai `StoreMenuRequest`/`UpdateMenuRequest` untuk membedakan dua bentuk
     * `parent_id`: id menu yang sudah ada (dipakai apa adanya) versus nama menu
     * induk BARU yang diketik lewat Select2 `tags`. Nilai yang bukan angka atau
     * angka yang tidak menunjuk menu mana pun dianggap nama baru, bukan id yang
     * rusak -- `prepareForValidation()` sudah mem-trim menjadi string, jadi id
     * satu digit ("5") harus tetap dikenali walaupun `ctype_digit()` tidak
     * cocok dengan integer mentah.
     */
    public static function isExistingMenuId(mixed $value): bool
    {
        if (is_int($value)) {
            return static::query()->whereKey($value)->exists();
        }

        if (! is_string($value) || ! ctype_digit($value)) {
            return false;
        }

        return static::query()->whereKey((int) $value)->exists();
    }
}
