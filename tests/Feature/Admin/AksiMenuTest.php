<?php

use App\Models\Menu;
use App\Models\User;
use App\Support\MenuAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('admin', 'permission');

beforeEach(function () {
    seedAkses();

    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'aksi-sa@test.com']);
    $this->kesra = User::factory()->admin()->create(['email' => 'aksi-kesra@test.com']);
    $this->target = User::factory()->create(['email' => 'aksi-target@test.com']);
});

/**
 * Role dengan grant menu `admin.pengguna` (menunya tampil) tapi hanya aksi
 * `read`. Inilah bentuk yang diminta: boleh membuka halaman, tidak boleh
 * menambah/mengubah/menghapus.
 */
function roleHanyaLihat(): User
{
    $role = Role::create(['name' => 'pembaca']);
    $role->syncPermissions([Permission::findOrCreate('admin.pengguna.read', 'web')]);

    $menuId = Menu::query()->where('scope', 'admin.pengguna')->value('id');

    DB::table('role_menu')->insertOrIgnore([
        'role_id' => $role->id,
        'menu_id' => $menuId,
    ]);

    return User::factory()->create(['email' => 'pembaca@test.com'])->assignRole($role);
}

// ─── Aksi granular: read saja ─────────────────────────────────────────────

test('role dengan aksi read saja bisa membuka daftar dan detail pengguna', function () {
    $pembaca = roleHanyaLihat();

    actingAs($pembaca)->get(route('admin.pengguna.index'))->assertOk();
    actingAs($pembaca)->get(route('admin.pengguna.lihat', $this->target))->assertOk();
});

test('role dengan aksi read saja tidak bisa membuka form tambah', function () {
    $pembaca = roleHanyaLihat();

    actingAs($pembaca)->get(route('admin.pengguna.buat'))->assertForbidden();
});

test('role dengan aksi read saja tidak bisa menyimpan pengguna baru', function () {
    $pembaca = roleHanyaLihat();

    actingAs($pembaca)->post(route('admin.pengguna.simpan'), [
        'peran' => 'user',
        'status' => 'aktif',
        'nik' => '6302000000000777',
    ])->assertForbidden();

    $this->assertDatabaseMissing('profil_pengguna', ['nik' => '6302000000000777']);
});

test('role dengan aksi read saja tidak bisa membuka form ubah', function () {
    $pembaca = roleHanyaLihat();

    actingAs($pembaca)->get(route('admin.pengguna.ubah', $this->target))->assertForbidden();
});

test('role dengan aksi read saja tidak bisa menyimpan perubahan', function () {
    $pembaca = roleHanyaLihat();

    actingAs($pembaca)->put(route('admin.pengguna.perbarui', $this->target), [
        'peran' => 'kesra',
        'status' => 'aktif',
    ])->assertForbidden();
});

test('role dengan aksi read saja tidak bisa mengubah status atau mereset kata sandi', function () {
    $pembaca = roleHanyaLihat();

    actingAs($pembaca)->patch(route('admin.pengguna.toggle-status', $this->target))->assertForbidden();
    actingAs($pembaca)->post(route('admin.pengguna.reset-password', $this->target))->assertForbidden();
});

test('role dengan aksi read saja tidak bisa menghapus pengguna', function () {
    $pembaca = roleHanyaLihat();

    actingAs($pembaca)->delete(route('admin.pengguna.hapus', $this->target))->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $this->target->id]);
});

test('daftar pengguna untuk role read saja tidak menampilkan tombol tambah', function () {
    $pembaca = roleHanyaLihat();

    actingAs($pembaca)->get(route('admin.pengguna.index'))
        ->assertOk()
        ->assertDontSee('Tambah Pengguna');
});

test('daftar pengguna untuk role read saja tidak menampilkan tombol aksi apa pun', function () {
    $pembaca = roleHanyaLihat();

    // Tombol "Aksi" adalah pintu ke Ubah/Nonaktifkan/Reset/Hapus. Role yang
    // hanya boleh melihat tidak boleh melihat pintunya sama sekali -- dropdown
    // kosong tetap terbaca sebagai "boleh mengubah".
    //
    // Penandanya harus `fa-cog"></i> Aksi` (tombol dropdown di kolom Aksi),
    // BUKAN `fa-cog` polos: ikon yang sama dipakai menu "Pengaturan" di navbar.
    actingAs($pembaca)->get(route('admin.pengguna.index'))
        ->assertOk()
        ->assertDontSee('fa-cog"></i> Aksi', false)
        ->assertDontSee('Nonaktifkan')
        ->assertDontSee('Reset Password')
        ->assertDontSee('Hapus User')
        // "Lihat" tetap ada: itu inti dari aksi read.
        ->assertSee('Lihat');
});

test('detail pengguna untuk role read saja tidak menampilkan tombol ubah', function () {
    $pembaca = roleHanyaLihat();

    actingAs($pembaca)->get(route('admin.pengguna.lihat', $this->target))
        ->assertOk()
        ->assertDontSee('fa-user-tag', false);
});

test('daftar pengguna untuk role dengan edit tetap menampilkan tombol aksi berisi ubah', function () {
    $role = Role::create(['name' => 'pengubah']);
    $role->syncPermissions([
        Permission::findOrCreate('admin.pengguna.read', 'web'),
        Permission::findOrCreate('admin.pengguna.edit', 'web'),
    ]);

    $menuId = Menu::query()->where('scope', 'admin.pengguna')->value('id');
    DB::table('role_menu')->insertOrIgnore(['role_id' => $role->id, 'menu_id' => $menuId]);

    $pengubah = User::factory()->create(['email' => 'pengubah@test.com'])->assignRole($role);

    // Aksi yang tidak dipegang tetap tidak muncul walau tombolnya ada.
    actingAs($pengubah)->get(route('admin.pengguna.index'))
        ->assertOk()
        ->assertSee('Aksi')
        ->assertSee('Ubah')
        ->assertDontSee('Reset Password')
        ->assertDontSee('Hapus User');
});

// ─── Aksi granular: kombinasi ─────────────────────────────────────────────

test('role dengan aksi read dan update bisa mengubah status tanpa bisa menghapus', function () {
    $role = Role::create(['name' => 'operator']);
    $role->syncPermissions([
        Permission::findOrCreate('admin.pengguna.read', 'web'),
        Permission::findOrCreate('admin.pengguna.update', 'web'),
    ]);

    $menuId = Menu::query()->where('scope', 'admin.pengguna')->value('id');
    DB::table('role_menu')->insertOrIgnore(['role_id' => $role->id, 'menu_id' => $menuId]);

    $operator = User::factory()->create(['email' => 'operator@test.com'])->assignRole($role);

    actingAs($operator)->patch(route('admin.pengguna.toggle-status', $this->target))
        ->assertRedirect(route('admin.pengguna.index'));

    expect($this->target->fresh()->status)->toBe('non-aktif');

    actingAs($operator)->delete(route('admin.pengguna.hapus', $this->target))->assertForbidden();
});

test('aksi edit terpisah dari aksi update', function () {
    $role = Role::create(['name' => 'hanya-ubah']);
    $role->syncPermissions([
        Permission::findOrCreate('admin.pengguna.read', 'web'),
        Permission::findOrCreate('admin.pengguna.edit', 'web'),
    ]);

    $menuId = Menu::query()->where('scope', 'admin.pengguna')->value('id');
    DB::table('role_menu')->insertOrIgnore(['role_id' => $role->id, 'menu_id' => $menuId]);

    $user = User::factory()->create(['email' => 'hanyaubah@test.com'])->assignRole($role);

    // Boleh membuka form ubah...
    actingAs($user)->get(route('admin.pengguna.ubah', $this->target))->assertOk();

    // ...tapi tidak boleh menyimpannya.
    actingAs($user)->put(route('admin.pengguna.perbarui', $this->target), [
        'peran' => 'catpil',
        'status' => 'aktif',
    ])->assertForbidden();
});

// ─── Super Admin selalu penuh ─────────────────────────────────────────────

test('super admin memegang semua aksi walau permission-nya dicabut', function () {
    // Cabut semua permission super_admin dari database: `hasMenuAction()`
    // tetap harus menjawab boleh, supaya halaman Akses Menu tidak bisa
    // mengunci sistem.
    Role::where('name', 'super_admin')->firstOrFail()->syncPermissions([]);

    $fresh = $this->superAdmin->fresh();

    foreach (MenuAction::ACTIONS as $action) {
        expect($fresh->hasMenuAction('admin.pengguna', $action))->toBeTrue();
    }

    actingAs($fresh)->get(route('admin.pengguna.index'))->assertOk();
    actingAs($fresh)->get(route('admin.pengguna.buat'))->assertOk();
});

test('seeder memberi kesra modul pengguna kecuali hapus', function () {
    $kesra = $this->kesra->fresh();

    expect($kesra->hasMenuAction('admin.pengguna', MenuAction::READ))->toBeTrue()
        ->and($kesra->hasMenuAction('admin.pengguna', MenuAction::CREATE))->toBeTrue()
        ->and($kesra->hasMenuAction('admin.pengguna', MenuAction::EDIT))->toBeTrue()
        ->and($kesra->hasMenuAction('admin.pengguna', MenuAction::UPDATE))->toBeTrue()
        ->and($kesra->hasMenuAction('admin.pengguna', MenuAction::DELETE))->toBeFalse();
});

test('seeder tidak memberi aksi modul ke role catpil', function () {
    $catpil = User::factory()->catpil()->create(['email' => 'catpil-aksi@test.com']);

    foreach (MenuAction::ACTIONS as $action) {
        expect($catpil->hasMenuAction('admin.pengguna', $action))->toBeFalse();
    }
});

// ─── Halaman Akses Menu ───────────────────────────────────────────────────

test('halaman akses menu menampilkan centang aksi untuk modul crud', function () {
    $pengguna = Menu::query()->where('scope', 'admin.pengguna')->firstOrFail();

    actingAs($this->superAdmin)
        ->get(route('admin.menu.index', ['role' => Role::where('name', 'kesra')->value('id')]))
        ->assertOk()
        ->assertSee('Aksi yang diizinkan')
        ->assertSee('aksi['.$pengguna->id.'][]', false)
        ->assertSee('id="aksi-'.$pengguna->id.'-read"', false);
});

test('halaman akses menu tidak menampilkan centang aksi untuk menu non crud', function () {
    // Menu daftar pendaftar lama (scope admin.pendaftar) sudah dihapus;
    // contoh menu non-CRUD kini diambil dari leaf rekap "Pendaftar".
    $pendaftar = Menu::query()->where('scope', 'admin.kesra.status')->firstOrFail();

    $html = actingAs($this->superAdmin)
        ->get(route('admin.menu.index', ['role' => Role::where('name', 'kesra')->value('id')]))
        ->getContent();

    expect($html)->not->toContain('aksi['.$pendaftar->id.'][]');
});

test('menyimpan aksi hanya mengubah role terpilih', function () {
    $pengguna = Menu::query()->where('scope', 'admin.pengguna')->firstOrFail();
    $kelolaAkses = Menu::query()->where('scope', 'admin.menu')->firstOrFail();
    $catpilId = Role::where('name', 'catpil')->value('id');
    $wajibIds = Menu::query()->where('wajib', true)->pluck('id')->all();

    // Beri catpil hak read saja pada modul Pengguna.
    actingAs($this->superAdmin)->put(route('admin.menu.grants'), [
        'role_id' => $catpilId,
        'menus' => [$pengguna->id, $kelolaAkses->id],
        'aksi' => [$pengguna->id => ['read']],
    ])->assertRedirect(route('admin.menu.index', ['role' => $catpilId]));

    $catpil = Role::where('name', 'catpil')->firstOrFail();
    $nama = $catpil->permissions->pluck('name')->all();

    expect($nama)->toContain('admin.pengguna.read')
        ->and($nama)->not->toContain('admin.pengguna.create')
        ->and($nama)->not->toContain('admin.pengguna.delete');

    // Role kesra tidak ikut berubah.
    $kesra = Role::where('name', 'kesra')->firstOrFail();
    expect($kesra->permissions->pluck('name')->all())->toContain('admin.pengguna.update');

    // Menu wajib tetap ter-grant untuk role yang disimpan.
    expect(DB::table('role_menu')->where('role_id', $catpilId)->pluck('menu_id')->all())
        ->toContain($pengguna->id, $kelolaAkses->id);
});

test('menu modul tanpa aksi dicatat sebagai lihat saja', function () {
    $pengguna = Menu::query()->where('scope', 'admin.pengguna')->firstOrFail();
    $catpilId = Role::where('name', 'catpil')->value('id');

    actingAs($this->superAdmin)->put(route('admin.menu.grants'), [
        'role_id' => $catpilId,
        'menus' => [$pengguna->id],
        'aksi' => [],
    ])->assertRedirect();

    $catpil = Role::where('name', 'catpil')->firstOrFail();

    expect($catpil->permissions->pluck('name')->all())->toBe(['admin.pengguna.read']);
});

test('menyimpan aksi menolak nama aksi yang tidak dikenal', function () {
    $pengguna = Menu::query()->where('scope', 'admin.pengguna')->firstOrFail();
    $catpilId = Role::where('name', 'catpil')->value('id');

    actingAs($this->superAdmin)->put(route('admin.menu.grants'), [
        'role_id' => $catpilId,
        'menus' => [$pengguna->id],
        'aksi' => [$pengguna->id => ['hapus-semua']],
    ])->assertSessionHasErrors('aksi.'.$pengguna->id.'.0');
});

test('mencabut menu modul ikut mencabut aksinya', function () {
    $pengguna = Menu::query()->where('scope', 'admin.pengguna')->firstOrFail();
    $catpilId = Role::where('name', 'catpil')->value('id');

    actingAs($this->superAdmin)->put(route('admin.menu.grants'), [
        'role_id' => $catpilId,
        'menus' => [$pengguna->id],
        'aksi' => [$pengguna->id => ['read', 'create']],
    ])->assertRedirect();

    expect(Role::where('name', 'catpil')->firstOrFail()->permissions->pluck('name')->all())
        ->toContain('admin.pengguna.create');

    // Simpan lagi tanpa menu itu: permission-nya harus ikut hilang.
    actingAs($this->superAdmin)->put(route('admin.menu.grants'), [
        'role_id' => $catpilId,
        'menus' => [],
        'aksi' => [],
    ])->assertRedirect();

    expect(Role::where('name', 'catpil')->firstOrFail()->permissions->pluck('name')->all())
        ->toBeEmpty();
});

test('super admin tidak kehilangan aksi walau disimpan tanpa centang', function () {
    $superAdminId = Role::where('name', 'super_admin')->value('id');

    actingAs($this->superAdmin)->put(route('admin.menu.grants'), [
        'role_id' => $superAdminId,
        'menus' => [],
        'aksi' => [],
    ])->assertRedirect();

    expect(Role::where('name', 'super_admin')->firstOrFail()->permissions->pluck('name')->all())
        ->toContain('admin.pengguna.delete', 'admin.role.delete', 'admin.menukelola.delete');
});

// ─── Route non-CRUD tidak terpengaruh ─────────────────────────────────────

test('route yang tidak punya aksi granular tetap dijaga grant menu saja', function () {
    // Kesra punya grant menu Pendaftar; `admin.pendaftar.index` tidak ada di
    // `MenuAction::ROUTES`, jadi middleware aksi harus melewatkannya.
    actingAs($this->kesra)->get(route('admin.pendaftar.index'))->assertOk();
});
