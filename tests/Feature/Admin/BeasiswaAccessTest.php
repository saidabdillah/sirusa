<?php

use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class)->group('admin', 'beasiswa');

beforeEach(function () {
    seedAkses();

    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'sa@test.com']);
    $this->admin = User::factory()->admin()->create(['email' => 'admin@test.com']);
});

// ─── Scholarship: index & detail visible to both ────────────────

test('super admin can view scholarship index and detail', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->superAdmin)->get(route('admin.beasiswa.index'))->assertOk();
    actingAs($this->superAdmin)->get(route('admin.beasiswa.lihat', $scholarship))->assertOk();
});

test('admin can view scholarship index and detail', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->admin)->get(route('admin.beasiswa.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.beasiswa.lihat', $scholarship))->assertOk();
});

test('detail beasiswa admin menampilkan fakultas dan program studi yang dipilih', function () {
    $scholarship = Scholarship::factory()->create();

    // Cakupan yang dipilih saat membuat/mengubah beasiswa harus terbaca di
    // halaman detailnya, bukan hanya tersimpan di database. Kini cakupan
    // disimpan sebagai id prodi, jadi kampus/fakultasnya diturunkan dari prodi.
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $prodiTeknik = $kampus->fakultas()->create(['nama' => 'Fakultas Teknik'])
        ->prodi()->create(['nama' => 'Teknik Informatika']);
    $prodiEkonomi = $kampus->fakultas()->create(['nama' => 'Fakultas Ekonomi'])
        ->prodi()->create(['nama' => 'Akuntansi']);

    $scholarship->cakupan()->createMany([
        ['prodi_id' => $prodiTeknik->id],
        ['prodi_id' => $prodiEkonomi->id],
    ]);

    actingAs($this->admin)->get(route('admin.beasiswa.lihat', $scholarship))
        ->assertOk()
        ->assertSee('Program Studi yang Bisa Mendaftar')
        ->assertSee('Fakultas Teknik')
        ->assertSee('Teknik Informatika')
        ->assertSee('Fakultas Ekonomi')
        ->assertSee('Akuntansi')
        // Daftar prodi diindentasi lewat `pl-4` saja supaya sejajar dengan
        // catatan fakultas; `ml-4` dobel dengan padding default `ul`.
        ->assertSee('<ul class="mb-0 pl-4">', false)
        ->assertDontSee('<ul class="ml-4 mb-0">', false);
});

// ─── Scholarship: menu-granted roles manage ─────────────────────

test('admin can access scholarship create form and create one', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $fakultas = $kampus->fakultas()->create(['nama' => 'Teknik']);
    $prodi = $fakultas->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin)->get(route('admin.beasiswa.buat'))
        ->assertOk()
        ->assertDontSee(' required>', false)
        // Beasiswa tidak lagi memilih SATU kampus: cakupan ditentukan daftar
        // prodi, dan kampus/fakultas jadi penyaring centangnya.
        ->assertDontSee('name="kampus_id"', false)
        ->assertSee('Fakultas &amp; Program Studi <span class="text-danger">*</span></h4>', false)
        ->assertSee('id="prodi-tree"', false)
        ->assertSee('>Persyaratan <span class="text-danger">*</span></label>', false)
        // Group checkbox `prodi_ids` (required min:1) ditandai lewat heading card-nya.
        ->assertSee('Fakultas &amp; Program Studi <span class="text-danger">*</span></h4>', false)
        ->assertSee('data-kampus-id="'.$kampus->id.'"', false)
        ->assertSee('kampus-check', false)
        ->assertSee('fakultas-check', false)
        ->assertSee('placeholder="contoh 3.00"', false)
        // Semester minimal kini dropdown (permintaan pengguna), jadi
        // placeholdernya diganti opsi kosong berlabel.
        ->assertSee('-- Pilih Semester --', false)
        ->assertSee('placeholder="contoh 10"', false);

    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Prestasi',
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'IPK >= 3.0',
        'prodi_ids' => [$prodi->id],
    ])->assertRedirect(route('admin.beasiswa.index'));

    $this->assertDatabaseHas('beasiswa', ['nama' => 'Beasiswa Prestasi']);
    $this->assertEquals('Universitas Indonesia', Scholarship::where('nama', 'Beasiswa Prestasi')->first()->kampus);
});

test('admin can access scholarship edit form without html5 required attribute', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->admin)->get(route('admin.beasiswa.ubah', $scholarship))
        ->assertOk()
        ->assertDontSee(' required>', false)
        ->assertSee('Fakultas &amp; Program Studi <span class="text-danger">*</span></h4>', false)
        ->assertSee('id="prodi-tree"', false);
});

test('create scholarship form links to add kampus when none exists', function () {
    actingAs($this->admin)->get(route('admin.beasiswa.buat'))
        ->assertOk()
        ->assertSee(route('admin.kampus.buat'), false)
        ->assertDontSee('alert-warning', false);
});

test('scholarship index action buttons have spacing', function () {
    Scholarship::factory()->create();

    actingAs($this->admin)->get(route('admin.beasiswa.index'))
        ->assertOk()
        ->assertSee('btn btn-info btn-sm mr-1 mb-1', false)
        ->assertSee('btn btn-primary btn-sm mr-1 mb-1', false)
        ->assertSee('d-inline-block align-middle mr-1 mb-1 btn-delete-form', false)
        ->assertDontSee('d-inline mr-1 mb-1 btn-delete-form', false)
        ->assertDontSee('d-flex gap-1', false);
});

test('satu program studi yang sah sudah cukup untuk menyimpan beasiswa', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $fakultas = $kampus->fakultas()->create(['nama' => 'Teknik']);
    $prodiPertama = $fakultas->prodi()->create(['nama' => 'Informatika']);
    $prodiKedua = $fakultas->prodi()->create(['nama' => 'Sipil']);

    $this->actingAs($this->admin);

    // Tidak ada aturan "semua prodi wajib dipilih": yang diminta minimal satu,
    // dan yang tersimpan harus persis prodi yang dicentang admin.
    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Satu Prodi',
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'prodi_ids' => [$prodiPertama->id],
    ])->assertSessionDoesntHaveErrors();

    $beasiswa = Scholarship::where('nama', 'Beasiswa Satu Prodi')->firstOrFail();
    // Yang tersimpan sekarang ID PRODI ASLI, bukan salinan nama.
    $this->assertSame([$prodiPertama->id], $beasiswa->cakupan()->pluck('prodi_id')->all());

    // Dua prodi dari kampus yang sama juga sah.
    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Dua Prodi',
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'prodi_ids' => [$prodiPertama->id, $prodiKedua->id],
    ])->assertSessionDoesntHaveErrors();
});

test('satu beasiswa boleh mencakup prodi dari BANYAK kampus', function () {
    // Permintaan pengguna: satu beasiswa boleh punya banyak kampus, banyak
    // fakultas, dan banyak prodi. Aturan lama (semua prodi wajib sekampus)
    // sudah tidak berlaku.
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodiSatu = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    $kampusLain = Kampus::create(['nama_kampus' => 'Universitas Ranking Dua']);
    $prodiLain = $kampusLain->fakultas()->create(['nama' => 'Ekonomi'])->prodi()->create(['nama' => 'Akuntansi']);

    $this->actingAs($this->admin);

    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Dua Kampus',
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'prodi_ids' => [$prodiSatu->id, $prodiLain->id],
    ])->assertSessionDoesntHaveErrors();

    $beasiswa = Scholarship::where('nama', 'Beasiswa Dua Kampus')->firstOrFail();

    expect($beasiswa->cakupan()->pluck('prodi_id')->all())
        ->toEqualCanonicalizing([$prodiSatu->id, $prodiLain->id]);

    // Ringkasan kolom `kampus` menurunkan nama kampus dari cakupan prodinya.
    expect($beasiswa->kampus)->toContain('Universitas Indonesia')
        ->and($beasiswa->kampus)->toContain('Universitas Ranking Dua');
});

test('kedua form beasiswa memakai pohon centang kampus-fakultas-prodi', function () {
    // Kampus tidak lagi disembunyikan/dinonaktifkan per pilihan: SELURUH kampus
    // tampil dan bisa dicentang bersamaan, karena satu beasiswa boleh mencakup
    // banyak kampus. Centang kampus dan fakultas adalah turunan dari centang
    // prodinya; yang dikirim ke server tetap hanya `prodi_ids[]`.
    foreach (['buat', 'ubah'] as $view) {
        $source = file_get_contents(resource_path("views/admin/beasiswa/{$view}.blade.php"));

        expect($source)
            ->toContain('.kampus-check')
            ->toContain('.fakultas-check')
            ->toContain('btn-centang-semua')
            ->toContain('btn-lepas-semua')
            ->and($source)->not->toContain(".prop('disabled', !isSelected)");
    }
});

test('tanggal mulai boleh sudah lewat, tapi urutan tanggal tetap dijaga', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);
    $this->actingAs($this->admin);

    // Tanggal mulai yang sudah lewat tetap diterima: beasiswa bisa dibuat atau
    // diperbaiki di tengah masa pendaftarannya, bahkan setelahnya.
    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Lampau',
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->subMonth()->format('Y-m-d'),
        'tanggal_selesai' => now()->subWeek()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'prodi_ids' => [$prodi->id],
    ])->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('admin.beasiswa.index'));

    $this->assertDatabaseHas('beasiswa', ['nama' => 'Beasiswa Lampau']);

    // Yang tetap ditolak adalah tanggal selesai sebelum tanggal mulai.
    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Terbalik',
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addMonth()->format('Y-m-d'),
        'tanggal_selesai' => now()->subDay()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'prodi_ids' => [$prodi->id],
    ])->assertSessionHasErrors('tanggal_selesai');
});

test('scholarship store rejects zero kuota and zero ipk', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin)->post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Buruk',
        'kuota' => 0,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'prodi_ids' => [$prodi->id],
    ])->assertSessionHasErrors(['kuota', 'ipk_minimal']);

    $this->assertDatabaseMissing('beasiswa', ['nama' => 'Beasiswa Buruk']);
});

test('scholarship update rejects zero kuota and zero ipk', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);
    $scholarship = Scholarship::factory()->create();

    actingAs($this->admin)->put(route('admin.beasiswa.perbarui', $scholarship), [
        'nama' => $scholarship->nama,
        'kuota' => 0,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'prodi_ids' => [$prodi->id],
    ])->assertSessionHasErrors(['kuota', 'ipk_minimal']);
});

test('create scholarship form links to add fakultas and prodi when empty', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);

    actingAs($this->admin)->get(route('admin.beasiswa.buat'))
        ->assertOk()
        ->assertSee(route('admin.kampus.fakultas.buat', $kampus), false);

    $fakultas = $kampus->fakultas()->create(['nama' => 'Teknik']);

    actingAs($this->admin)->get(route('admin.beasiswa.buat'))
        ->assertOk()
        ->assertSee(route('admin.kampus.prodi.buat', [$kampus, $fakultas]), false);
});

test('super admin can access scholarship create form and create one', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Kampus']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);
    $this->actingAs($this->superAdmin);

    $this->get(route('admin.beasiswa.buat'))->assertOk();

    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Super Admin',
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'prodi_ids' => [$prodi->id],
    ])->assertRedirect(route('admin.beasiswa.index'));

    $this->assertDatabaseHas('beasiswa', ['nama' => 'Beasiswa Super Admin']);
});

test('super admin can edit and delete scholarship', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->superAdmin)->get(route('admin.beasiswa.ubah', $scholarship))->assertOk();
    actingAs($this->superAdmin)->delete(route('admin.beasiswa.hapus', $scholarship))->assertRedirect();

    $this->assertDatabaseMissing('beasiswa', ['id' => $scholarship->id]);
});
