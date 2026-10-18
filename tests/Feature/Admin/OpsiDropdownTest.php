<?php

use App\Models\Kampus;
use App\Models\Menu;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('views', 'form');

beforeEach(function () {
    seedAkses();

    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'opsi-sa@test.com']);
    $this->user = User::factory()->standardUser()->create(['email' => 'opsi-user@test.com']);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Opsi']);
});

/**
 * Ambil isi satu `<select>` dari HTML berdasarkan id-nya.
 *
 * Dipakai untuk memeriksa apakah dropdown punya opsi kosong di posisi pertama.
 */
function isiSelect(string $html, string $id): string
{
    $pola = '#<select[^>]*\bid="'.preg_quote($id, '#').'"[^>]*>(.*?)</select>#is';

    preg_match($pola, $html, $cocok);

    return $cocok[1] ?? '';
}

// ─── Dropdown form: wajib punya opsi kosong di posisi pertama ─────────────

test('setiap dropdown di form ubah pengguna punya opsi kosong di posisi pertama', function () {
    $html = actingAs($this->superAdmin)->get(route('admin.pengguna.ubah', $this->user))->assertOk()->getContent();

    foreach (['peran', 'kampus_id', 'status'] as $id) {
        $isi = isiSelect($html, $id);

        expect($isi)->not->toBe('', "Dropdown #{$id} tidak ditemukan di form ubah pengguna");

        // Opsi pertama harus bernilai kosong, supaya tidak ada pilihan yang
        // diam-diam terpilih sebelum pengguna menyentuhnya.
        expect($isi)->toMatch('/^\s*<option value=""[^>]*>/', "Dropdown #{$id} tidak diawali opsi kosong");
    }
});

test('setiap dropdown di form tambah pengguna punya opsi kosong di posisi pertama', function () {
    $html = actingAs($this->superAdmin)->get(route('admin.pengguna.buat'))->assertOk()->getContent();

    foreach (['peran', 'kampus_id', 'status'] as $id) {
        expect(isiSelect($html, $id))->toMatch('/^\s*<option value=""[^>]*>/', "Dropdown #{$id} tidak diawali opsi kosong");
    }
});

test('setiap dropdown di form ubah beasiswa punya opsi kosong di posisi pertama', function () {
    $beasiswa = Scholarship::factory()->create([
        'nama' => 'Beasiswa Opsi',
        'kampus_id' => $this->kampus->id,
    ]);

    $html = actingAs($this->superAdmin)->get(route('admin.beasiswa.ubah', $beasiswa))->assertOk()->getContent();

    foreach (['kampus_id', 'tingkat_gelar', 'semester_minimal'] as $id) {
        $isi = isiSelect($html, $id);

        expect($isi)->not->toBe('', "Dropdown #{$id} tidak ditemukan di form ubah beasiswa");
        expect($isi)->toMatch('/^\s*<option value=""[^>]*>/', "Dropdown #{$id} tidak diawali opsi kosong");
    }
});

test('setiap dropdown di form tambah beasiswa punya opsi kosong di posisi pertama', function () {
    $html = actingAs($this->superAdmin)->get(route('admin.beasiswa.buat'))->assertOk()->getContent();

    foreach (['kampus_id', 'tingkat_gelar', 'semester_minimal'] as $id) {
        expect(isiSelect($html, $id))->toMatch('/^\s*<option value=""[^>]*>/', "Dropdown #{$id} tidak diawali opsi kosong");
    }
});

test('setiap dropdown di form profil punya opsi kosong di posisi pertama', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    // Hanya dropdown pilihan pengguna. `desa_kelurahan` dan `fakultas` dimuat
    // lewat JS sehingga opsinya belum ada saat render, tapi opsi kosongnya
    // tetap dirender server (placeholder-nya berbeda-beda).
    foreach (['desil', 'agama', 'kecamatan', 'desa_kelurahan', 'fakultas', 'prodi'] as $id) {
        $isi = isiSelect($html, $id);

        expect($isi)->not->toBe('', "Dropdown #{$id} tidak ditemukan di form profil");
        expect($isi)->toMatch('/^\s*<option value=""[^>]*>/', "Dropdown #{$id} tidak diawali opsi kosong");
    }

    foreach (['pekerjaan_ayah', 'pekerjaan_ibu', 'hubungan_wali', 'pekerjaan_wali'] as $id) {
        expect(isiSelect($html, $id))->toMatch('/^\s*<option value=""[^>]*>/', "Dropdown #{$id} tidak diawali opsi kosong");
    }
});

// ─── Radio & checkbox: tidak ada yang terpilih sendiri ────────────────────

test('tidak ada radio jenis kelamin yang terpilih saat profil masih kosong', function () {
    $html = actingAs($this->user)->get(route('profile'))->assertOk()->getContent();

    foreach (['jenis_kelamin_laki_laki', 'jenis_kelamin_perempuan'] as $id) {
        preg_match('#<input[^>]*\bid="'.$id.'"[^>]*>#i', $html, $cocok);

        expect($cocok[0] ?? '')->not->toBe('', "Radio #{$id} tidak ditemukan");
        expect($cocok[0])->not->toContain('checked', "Radio #{$id} terpilih padahal profil masih kosong");
    }
});

test('checkbox akses menu tidak tercentang untuk role yang belum punya akses', function () {
    // Role baru tanpa grant apa pun: seluruh checkbox harus kosong.
    Role::create(['name' => 'kosong']);
    $roleId = Role::where('name', 'kosong')->value('id');

    $html = actingAs($this->superAdmin)
        ->get(route('admin.menu.index', ['role' => $roleId]))
        ->assertOk()
        ->getContent();

    // Hanya menu `wajib` (Dasbor) yang boleh tercentang.
    $wajibIds = Menu::query()->where('wajib', true)->pluck('id')->all();

    preg_match_all('#<input[^>]*name="menus\[\]"[^>]*>#i', $html, $semua);

    foreach ($semua[0] as $tag) {
        preg_match('#value="(\d+)"#', $tag, $nilai);
        $menuId = (int) ($nilai[1] ?? 0);

        if (in_array($menuId, $wajibIds, true)) {
            expect($tag)->toContain('checked');

            continue;
        }

        expect($tag)->not->toContain('checked', "Menu #{$menuId} tercentang padahal role belum diberi akses");
    }
});

test('checkbox aksi tidak tercentang untuk role yang belum punya aksi', function () {
    Role::create(['name' => 'tanpa-aksi']);
    $roleId = Role::where('name', 'tanpa-aksi')->value('id');

    $html = actingAs($this->superAdmin)
        ->get(route('admin.menu.index', ['role' => $roleId]))
        ->assertOk()
        ->getContent();

    preg_match_all('#<input[^>]*data-aksi-menu="\d+"[^>]*>#i', $html, $semua);

    expect($semua[0])->not->toBeEmpty();

    foreach ($semua[0] as $tag) {
        expect($tag)->not->toContain('checked', 'Checkbox aksi tercentang padahal role belum diberi aksi');
    }
});
