<?php

use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('beasiswa', 'tanggal');

beforeEach(function () {
    seedAkses();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);

    $this->prodi = $this->kampus
        ->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);

    $this->admin = User::factory()->superAdmin()->create(['email' => 'admin@test.com']);
});

/**
 * Dua input tanggal harus punya konfigurasi picker yang persis sama supaya
 * create dan edit tidak berbeda perilaku.
 *
 * @return array{0: string, 1: string}
 */
function htmlTanggal(string $html): array
{
    $html = preg_replace('/\s+/', ' ', $html);

    preg_match('/<input type="text" class="form-control flatpickr[^"]*" id="tanggal_mulai" name="tanggal_mulai" value="([^"]*)"/', $html, $mulai);
    preg_match('/<input type="text" class="form-control flatpickr[^"]*" id="tanggal_selesai" name="tanggal_selesai" value="([^"]*)"/', $html, $selesai);

    return [$mulai[1] ?? '', $selesai[1] ?? ''];
}

/*
|--------------------------------------------------------------------------
| Tanggal mulai boleh di masa lalu
|--------------------------------------------------------------------------
*/

test('beasiswa dengan tanggal mulai yang sudah lewat tetap bisa dibuat', function () {
    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), [
            'nama' => 'Beasiswa Periode Lampau',
            'deskripsi' => 'Pendaftaran sudah ditutup.',
            'persyaratan' => 'Tidak ada.',
            'kampus_id' => $this->kampus->id,
            'prodi_ids' => [$this->prodi->id],
            'tingkat_gelar' => 'S1',
            'kuota' => 10,
            'ipk_minimal' => 2.5,
            'semester_minimal' => 1,
            'tanggal_mulai' => now()->subMonth()->format('Y-m-d'),
            'tanggal_selesai' => now()->subWeek()->format('Y-m-d'),
            'status' => 'aktif',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('beasiswa', ['nama' => 'Beasiswa Periode Lampau']);
});

test('tanggal selesai tetap harus tidak lebih awal dari tanggal mulai', function () {
    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), [
            'nama' => 'Beasiswa Terbalik',
            'deskripsi' => 'Tanggalnya terbalik.',
            'persyaratan' => 'Tidak ada.',
            'kampus_id' => $this->kampus->id,
            'prodi_ids' => [$this->prodi->id],
            'tingkat_gelar' => 'S1',
            'kuota' => 10,
            'ipk_minimal' => 2.5,
            'semester_minimal' => 1,
            'tanggal_mulai' => now()->addMonth()->format('Y-m-d'),
            'tanggal_selesai' => now()->subDay()->format('Y-m-d'),
            'status' => 'aktif',
        ])
        ->assertSessionHasErrors('tanggal_selesai');

    $this->assertDatabaseCount('beasiswa', 0);
});

test('perubahan tanggal masa lalu lewat form ubah tetap diterima', function () {
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tingkat_gelar' => 'S1',
        'status' => 'aktif',
        'tanggal_mulai' => now()->subYear()->startOfYear()->format('Y-m-d'),
        'tanggal_selesai' => now()->subYear()->startOfYear()->addMonth()->format('Y-m-d'),
    ]);

    actingAs($this->admin)
        ->put(route('admin.beasiswa.perbarui', $beasiswa), [
            'nama' => $beasiswa->nama,
            'deskripsi' => $beasiswa->deskripsi,
            'persyaratan' => $beasiswa->persyaratan,
            'kampus_id' => $this->kampus->id,
            'prodi_ids' => [$this->prodi->id],
            'tingkat_gelar' => 'S1',
            'kuota' => $beasiswa->kuota,
            'ipk_minimal' => 2.5,
            'semester_minimal' => 3,
            'tanggal_mulai' => now()->subYear()->startOfYear()->format('Y-m-d'),
            'tanggal_selesai' => now()->subYear()->startOfYear()->addDays(20)->format('Y-m-d'),
            'status' => 'aktif',
        ])
        ->assertSessionHasNoErrors();

    expect($beasiswa->refresh()->tanggal_selesai->format('Y-m-d'))
        ->toBe(now()->subYear()->startOfYear()->addDays(20)->format('Y-m-d'));
});

/*
|--------------------------------------------------------------------------
| Konsistensi date picker antara create dan edit
|--------------------------------------------------------------------------
*/

test('form buat memakai input teks flatpickr yang dikonfigurasi', function () {
    $html = actingAs($this->admin)->get(route('admin.beasiswa.buat'))->assertOk()->getContent();

    // Input harus `text`, bukan `date`, karena flatpickr mengganti tampilan
    // dengan `altInput` berformat dd/mm/yyyy sementara nilainya tetap Y-m-d.
    expect($html)->toContain('class="form-control flatpickr bg-white', false)
        ->and($html)->toContain("initDatePicker('.flatpickr')", false);
});

test('form ubah memakai input teks flatpickr yang dikonfigurasi sama', function () {
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_mulai' => '2026-01-15',
        'tanggal_selesai' => '2026-03-20',
    ]);

    $html = actingAs($this->admin)->get(route('admin.beasiswa.ubah', $beasiswa))->assertOk()->getContent();

    expect($html)->toContain('class="form-control flatpickr bg-white', false)
        ->and($html)->toContain("initDatePicker('.flatpickr')", false);
});

test('nilai tanggal tersimpan terisi kembali di form ubah', function () {
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_mulai' => '2026-01-15',
        'tanggal_selesai' => '2026-03-20',
    ]);

    [$mulai, $selesai] = htmlTanggal(actingAs($this->admin)->get(route('admin.beasiswa.ubah', $beasiswa))->getContent());

    // Nilai yang dikirim balik adalah Y-m-d, sama dengan yang disimpan.
    expect($mulai)->toBe('2026-01-15')
        ->and($selesai)->toBe('2026-03-20');
});

test('form ubah tidak memaksa tanggal ke masa depan', function () {
    $beasiswa = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'tanggal_mulai' => now()->subYear()->format('Y-m-d'),
        'tanggal_selesai' => now()->subMonth()->format('Y-m-d'),
    ]);

    [$mulai, $selesai] = htmlTanggal(actingAs($this->admin)->get(route('admin.beasiswa.ubah', $beasiswa))->getContent());

    expect($mulai)->toBe(now()->subYear()->format('Y-m-d'))
        ->and($selesai)->toBe(now()->subMonth()->format('Y-m-d'));
});

test('form buat tidak memaksa tanggal, jadi tidak ada nilai bawaan yang mengunci', function () {
    [$mulai, $selesai] = htmlTanggal(actingAs($this->admin)->get(route('admin.beasiswa.buat'))->getContent());

    expect($mulai)->toBe('')
        ->and($selesai)->toBe('');
});

/*
|--------------------------------------------------------------------------
| Konfigurasi picker di helper yang dipakai kedua form
|--------------------------------------------------------------------------
*/

test('helper date picker memakai format yang sama untuk create dan edit', function () {
    $js = file_get_contents(base_path('public/assets/js/custom.js'));

    expect($js)->toContain('window.initDatePicker')
        // Nilai yang dikirim server tetap Y-m-d...
        ->toContain('dateFormat: "Y-m-d"')
        // ...yang ditampilkan sebagai dd/mm/yyyy.
        ->toContain('altInput: true')
        ->toContain('altFormat: "d/m/Y"')
        // Input dikunci supaya pengguna hanya bisa memilih lewat kalender,
        // bukan mengetik bebas yang bisa berformat salah.
        ->toContain('allowInput: false')
        // Picker native dipakai di layar kecil supaya tetap bisa dibuka di
        // Android/iOS, sementara desktop memakai kalender flatpickr.
        ->toContain('disableMobile: true');
});

/*
|--------------------------------------------------------------------------
| Error tanggal harus terlihat di input yang benar-benar diklik pengguna
|--------------------------------------------------------------------------
|
| Flatpickr v4 menyembunyikan input aslinya lewat `setAttribute("type","hidden")`
| lalu menyisipkan `.flatpickr-alt` sebagai saudara langsungnya. `altInputClass`
| hanya disalin SEKALI saat inisialisasi (`altInputClass = el.className + " " +
| config.altInputClass`), jadi:
|
|   - error yang sudah ada saat halaman dirender ikut tersalin -> tampil benar;
|   - error 422 dari AJAX datang SETELAH inisialisasi -> `is-invalid` di input
|     asli tidak akan pernah terlihat. Yang tampil cuma pesan errornya, tanpa
|     border merah.
|
| Yang kedua inilah yang dilaporkan user, dan hanya bisa diperbaiki dengan
| mencerminkan state `is-invalid` ke alt-input.
|
*/

test('error tanggal dari server menandai input dan menaruh pesan di bawahnya', function () {
    // Error 422 di path AJAX tidak pernah menyentuh render Blade, jadi jalur
    // non-AJAX diuji terpisah: `@error` harus tetap menghasilkan `is-invalid` +
    // `invalid-feedback` per field. `followingRedirects()` dipakai supaya render
    // terjadi di respons yang sama, dengan flash error yang baru saja di-set.
    $html = $this->followingRedirects()
        ->actingAs($this->admin)
        ->from(route('admin.beasiswa.buat'))
        ->post(route('admin.beasiswa.simpan'), [
            'nama' => 'Beasiswa Terbalik',
            'deskripsi' => 'Tanggalnya terbalik.',
            'persyaratan' => 'Tidak ada.',
            'kampus_id' => $this->kampus->id,
            'prodi_ids' => [$this->prodi->id],
            'tingkat_gelar' => 'S1',
            'kuota' => 10,
            'ipk_minimal' => 2.5,
            'semester_minimal' => 1,
            'tanggal_mulai' => '2026-09-30',
            'tanggal_selesai' => '2026-09-20',
            'status' => 'aktif',
        ])
        ->assertOk()
        ->getContent();

    // Field yang salah reddenya, field yang benar tidak.
    expect(tagInput($html, 'tanggal_selesai'))->toContain('is-invalid')
        ->and(tagInput($html, 'tanggal_mulai'))->not->toContain('is-invalid');

    // Pesannya muncul tepat di bawah field yang salah, dan pesan itu milik
    // `tanggal_selesai` -- bukan pesan field lain.
    expect(blokField($html, 'tanggal_selesai'))
        ->toContain('is-invalid')
        ->toContain('invalid-feedback d-block')
        ->toContain('Tanggal selesai harus sama atau setelah tanggal mulai');

    // Field yang benar tidak boleh ikut membawa error.
    expect(blokField($html, 'tanggal_mulai'))->not->toContain('is-invalid');
});

test('error tanggal masing-masing ditolak backend secara terpisah', function () {
    $payload = fn (array $tanggal) => [
        'nama' => 'Beasiswa Uji Tanggal',
        'deskripsi' => 'Uji validasi tanggal.',
        'persyaratan' => 'Tidak ada.',
        'kampus_id' => $this->kampus->id,
        'prodi_ids' => [$this->prodi->id],
        'tingkat_gelar' => 'S1',
        'kuota' => 10,
        'ipk_minimal' => 2.5,
        'semester_minimal' => 1,
        'status' => 'aktif',
        ...$tanggal,
    ];

    // Tanggal terbalik: hanya `tanggal_selesai` yang salah, supaya user melihat
    // field yang valid tidak ikut merah.
    $respons = actingAs($this->admin)->post(route('admin.beasiswa.simpan'), $payload([
        'tanggal_mulai' => '2026-09-30',
        'tanggal_selesai' => '2026-09-20',
    ]));

    $respons->assertSessionHasErrors('tanggal_selesai')
        ->assertSessionDoesntHaveErrors('tanggal_mulai');

    // Pesannya harus milik `tanggal_selesai` dan menyebut urutannya, supaya
    // jelas field mana yang perlu diperbaiki.
    expect(session('errors')->first('tanggal_selesai'))
        ->toBe('Tanggal selesai harus sama atau setelah tanggal mulai');

    // Tanggal tidak boleh: field itu sendiri yang ditolak.
    $respons = actingAs($this->admin)->post(route('admin.beasiswa.simpan'), $payload([
        'tanggal_mulai' => 'bukan tanggal',
        'tanggal_selesai' => '2026-09-20',
    ]));

    $respons->assertSessionHasErrors('tanggal_mulai');

    expect(session('errors')->first('tanggal_mulai'))
        ->toBe('Tanggal mulai tidak valid');

    // Tanggal berurutan: tidak ada error sama sekali, jadi tidak ada `is-invalid`.
    actingAs($this->admin)->post(route('admin.beasiswa.simpan'), $payload([
        'tanggal_mulai' => '2026-09-20',
        'tanggal_selesai' => '2026-09-30',
    ]))->assertSessionHasNoErrors();

    $html = actingAs($this->admin)->get(route('admin.beasiswa.buat'))->assertOk()->getContent();

    expect(tagInput($html, 'tanggal_mulai'))->not->toContain('is-invalid')
        ->and(tagInput($html, 'tanggal_selesai'))->not->toContain('is-invalid');
});

/**
 * Ambil tag `<input>` yang memuat `name="{$name}"` lalu kembalikan seluruh
 * isi tag-nya.
 *
 * Regex harus menyasar tag utuh, bukan `name="..."` diikuti karakter apa pun:
 * di view Sirusa atribut `class` sering diletakkan SEBELUM `id`/`name`, jadi
 * pola `name="x"[^>]*is-invalid` akan meleset walau field-nya memang salah.
 */
function tagInput(string $html, string $name): string
{
    $html = preg_replace('/\s+/', ' ', $html);

    preg_match('/<input\b[^>]*\bname="'.preg_quote($name, '/').'"[^>]*>/', $html, $m);

    return $m[0] ?? '';
}

/**
 * Ambil seluruh blok `form-group` milik satu field, dari `<label for=...>`
 * sampai `<label>` berikutnya.
 *
 * `Str::between()` tidak bisa dipakai di sini: `id="tanggal_selesai"`
 * muncul dua kali di tag yang sama (atribut `id` dan bagian dari `name`), dan
 * Blade menyisipkan `is-invalid` ke `class` yang terletak SEBELUM keduanya --
 * jadi potongan yang dibatasi `id` memotong justru bagian yang sedang diuji.
 */
function blokField(string $html, string $for): string
{
    $html = preg_replace('/\s+/', ' ', $html);

    $mulai = strpos($html, '<label for="'.$for.'">');

    if ($mulai === false) {
        return '';
    }

    $selesai = strpos($html, '<label for="', $mulai + 1);

    return $selesai === false
        ? substr($html, $mulai)
        : substr($html, $mulai, $selesai - $mulai);
}

test('error ajax dicerminkan ke alt input flatpickr', function () {
    $jsRapi = preg_replace('/\s+/', ' ', file_get_contents(base_path('public/assets/js/custom.js')));

    // `paintFieldError()` menandai input asli; tanpa cermin, border merah tidak
    // pernah muncul karena yang dilihat user adalah `.flatpickr-alt`.
    expect($jsRapi)
        ->toContain('function cerminIsInvalid($el)')
        ->toContain('$el.siblings(".flatpickr-alt").toggleClass("is-invalid", $el.hasClass("is-invalid"))')
        // Dipanggil tepat setelah input asli ditandai, di dalam `paintFieldError`.
        ->toContain('$el.addClass("is-invalid").attr("aria-invalid", "true"); '
            .'$group.addClass("is-invalid"); cerminIsInvalid($el);')
        // Dan dibersihkan lagi saat form disubmit ulang -- kalau tidak,
        // `is-invalid` yang tertinggal di alt-input akan membuat field yang
        // sudah diperbaiki tetap merah.
        ->toContain('$form.find(".is-invalid").removeClass("is-invalid").removeAttr("aria-invalid"); '
            .'$form.find(".flatpickr-alt").removeClass("is-invalid");');
});

test('field tanggal di form profil punya perlindungan error yang sama', function () {
    // `profil/index.blade.php` memanggil `flatpickr()` langsung, bukan lewat
    // `initDatePicker`, tapi field-nya punya masalah yang persis sama: `is-invalid`
    // di view tidak akan terlihat karena input aslinya disembunyikan flatpickr.
    $sumber = file_get_contents(resource_path('views/profil/index.blade.php'));

    $tag = tagInput($sumber, 'tanggal_lahir');

    expect($tag)
        ->not->toBe('', 'Input tanggal_lahir tidak ditemukan di profil/index.blade.php')
        ->toContain('flatpickr')
        ->toContain("@error('tanggal_lahir') is-invalid @enderror");

    expect($sumber)->toContain("@error('tanggal_lahir')<div class=\"invalid-feedback\">");
});

test('semua field flatpickr punya is-invalid dan invalid feedback sendiri', function () {
    // Kalau ada field tanpa blok `@error`, pesannya tidak akan pernah muncul
    // sama sekali -- bukan cuma border yang hilang. Dan `is-invalid` harus
    // ada di tag input itu sendiri, bukan di wrapper, karena yang dilukis
    // `paintFieldError()` adalah input yang diketik user.
    $berkas = [
        resource_path('views/admin/beasiswa/buat.blade.php') => ['tanggal_mulai', 'tanggal_selesai'],
        resource_path('views/admin/beasiswa/ubah.blade.php') => ['tanggal_mulai', 'tanggal_selesai'],
        resource_path('views/profil/index.blade.php') => ['tanggal_lahir'],
    ];

    foreach ($berkas as $path => $fields) {
        $isi = file_get_contents($path);

        foreach ($fields as $field) {
            $tag = tagInput($isi, $field);

            expect($tag)
                ->not->toBe('', "Input '{$field}' tidak ditemukan di {$path}")
                ->toContain('flatpickr')
                ->toContain("@error('{$field}') is-invalid @enderror");
        }
    }

    // Tiap field punya blok `@error`-nya sendiri, kalau tidak user tidak tahu
    // tanggal mana yang salah.
    $buat = file_get_contents(resource_path('views/admin/beasiswa/buat.blade.php'));

    expect(substr_count($buat, "@error('tanggal_mulai')<div class=\"invalid-feedback d-block\">"))->toBe(1)
        ->and(substr_count($buat, "@error('tanggal_selesai')<div class=\"invalid-feedback d-block\">"))->toBe(1);
});
