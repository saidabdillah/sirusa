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

// ─── Scholarship: menu-granted roles manage ─────────────────────

test('admin can access scholarship create form and create one', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $fakultas = $kampus->fakultas()->create(['nama' => 'Teknik']);
    $prodi = $fakultas->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin)->get(route('admin.beasiswa.buat'))
        ->assertOk()
        ->assertDontSee(' required>', false)
        ->assertSee('>Kampus <span class="text-danger">*</span></label>', false)
        ->assertSee('>Persyaratan <span class="text-danger">*</span></label>', false)
        ->assertSee('data-kampus-id="'.$kampus->id.'"', false)
        ->assertDontSee('value="'.$kampus->id.'" selected', false)
        ->assertSee('placeholder="contoh 3.00"', false)
        ->assertSee('placeholder="contoh 3"', false)
        ->assertSee('placeholder="contoh 10"', false);

    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Prestasi',
        'kampus_id' => $kampus->id,
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'IPK >= 3.0',
        'status' => 'aktif',
        'prodi_ids' => [$prodi->id],
    ])->assertRedirect(route('admin.beasiswa.index'));

    $this->assertDatabaseHas('beasiswa', ['nama' => 'Beasiswa Prestasi']);
    $this->assertEquals('Universitas Indonesia', Scholarship::where('nama', 'Beasiswa Prestasi')->first()->kampus);
});

test('admin can access scholarship edit form without html5 required attribute', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->admin)->get(route('admin.beasiswa.ubah', $scholarship))
        ->assertOk()
        ->assertDontSee(' required>', false);
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
        'kampus_id' => $kampus->id,
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
        'prodi_ids' => [$prodiPertama->id],
    ])->assertSessionDoesntHaveErrors();

    $beasiswa = Scholarship::where('nama', 'Beasiswa Satu Prodi')->firstOrFail();
    $tersimpan = $beasiswa->fakultas()->with('prodi')->get()
        ->flatMap(fn ($fakultas) => $fakultas->prodi->pluck('id')->all());

    $this->assertSame([$prodiPertama->id], $tersimpan->values()->all());

    // Dua prodi dari kampus yang sama juga sah.
    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Dua Prodi',
        'kampus_id' => $kampus->id,
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
        'prodi_ids' => [$prodiPertama->id, $prodiKedua->id],
    ])->assertSessionDoesntHaveErrors();
});

test('prodi dari kampus lain tetap ditolak', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    $kampusLain = Kampus::create(['nama_kampus' => 'Universitas Ranking Dua']);
    $prodiLain = $kampusLain->fakultas()->create(['nama' => 'Ekonomi'])->prodi()->create(['nama' => 'Akuntansi']);

    $this->actingAs($this->admin);

    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Salah Kampus',
        'kampus_id' => $kampus->id,
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
        'prodi_ids' => [$prodiLain->id],
    ])->assertSessionHasErrors('prodi_ids');

    // Pesan lama ("Semua program studi harus berada di kampus tujuan beasiswa")
    // tidak bisa ditindaklanjuti: admin tidak pernah mencentang prodi itu,
    // dan pesan itu tidak memberi tahu prodi mana yang salah. Yang muncul
    // harus menyebut nama program studinya.
    expect(session('errors')->first('prodi_ids'))
        ->toContain('Akuntansi')
        ->toContain('tidak berada di kampus tujuan');

    $this->assertDatabaseMissing('beasiswa', ['nama' => 'Beasiswa Salah Kampus']);
});

test('checkbox prodi di kampus tersembunyi dinonaktifkan agar tidak ikut terkirim', function () {
    // Checkbox yang hanya disembunyikan (d-none) tetap terkirim bersama form,
    // sehingga prodi dari kampus lain ikut masuk ke `prodi_ids[]` dan membuat
    // admin ditolak dengan pesan yang tidak bisa ditindaklanjuti: ia tidak
    // pernah mencentang prodi itu. `showKampusTree()` harus menonaktifkan
    // checkbox di luar kampus terpilih, bukan hanya menyembunyikannya.
    foreach (['buat', 'ubah'] as $view) {
        $source = file_get_contents(resource_path("views/admin/beasiswa/{$view}.blade.php"));

        expect($source)
            ->toContain(".prop('disabled', !isSelected)");
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
        'kampus_id' => $kampus->id,
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->subMonth()->format('Y-m-d'),
        'tanggal_selesai' => now()->subWeek()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
        'prodi_ids' => [$prodi->id],
    ])->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('admin.beasiswa.index'));

    $this->assertDatabaseHas('beasiswa', ['nama' => 'Beasiswa Lampau']);

    // Yang tetap ditolak adalah tanggal selesai sebelum tanggal mulai.
    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Terbalik',
        'kampus_id' => $kampus->id,
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addMonth()->format('Y-m-d'),
        'tanggal_selesai' => now()->subDay()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
        'prodi_ids' => [$prodi->id],
    ])->assertSessionHasErrors('tanggal_selesai');
});

test('scholarship store rejects zero kuota and zero ipk', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin)->post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Buruk',
        'kampus_id' => $kampus->id,
        'kuota' => 0,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
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
        'kampus_id' => $kampus->id,
        'kuota' => 0,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
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
        'kampus_id' => $kampus->id,
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
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
