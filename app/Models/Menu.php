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

    public static function sections(): array
    {
        return ['Menu Utama', 'Menu Admin', 'Menu Pengguna', 'Pengaturan', 'Manajemen'];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu');
    }
}
