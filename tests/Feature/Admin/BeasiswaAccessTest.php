<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class)->group('admin', 'beasiswa');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

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

// ─── Scholarship: only admin can manage ─────────────────────────

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
        'cakupan' => 'penuh',
        'batas_waktu' => now()->addMonth()->format('Y-m-d'),
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
        ->assertDontSee('d-flex gap-1', false);
});

test('password batas waktu validation fails for past dates', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $this->actingAs($this->admin);

    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Gagal',
        'kampus_id' => $kampus->id,
        'kuota' => 5,
        'tingkat_gelar' => 'S1',
        'cakupan' => 'penuh',
        'batas_waktu' => now()->subDay()->format('Y-m-d'),
        'ipk_minimal' => 3,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
        'prodi_ids' => [],
    ])->assertSessionHasErrors('batas_waktu');
});

test('scholarship store rejects zero kuota and zero ipk', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin)->post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Buruk',
        'kampus_id' => $kampus->id,
        'kuota' => 0,
        'tingkat_gelar' => 'S1',
        'cakupan' => 'penuh',
        'batas_waktu' => now()->addMonth()->format('Y-m-d'),
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
        'cakupan' => 'penuh',
        'batas_waktu' => now()->addMonth()->format('Y-m-d'),
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

test('super admin cannot access scholarship create form', function () {
    actingAs($this->superAdmin)->get(route('admin.beasiswa.buat'))->assertForbidden();
});

test('super admin cannot create scholarship', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Kampus']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->superAdmin)->post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Terlarang',
        'kampus_id' => $kampus->id,
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'cakupan' => 'penuh',
        'batas_waktu' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Persyaratan',
        'status' => 'aktif',
        'prodi_ids' => [$prodi->id],
    ])->assertForbidden();

    $this->assertDatabaseMissing('beasiswa', ['nama' => 'Beasiswa Terlarang']);
});

test('super admin cannot edit or delete scholarship', function () {
    $scholarship = Scholarship::factory()->create();

    actingAs($this->superAdmin)->get(route('admin.beasiswa.ubah', $scholarship))->assertForbidden();
    actingAs($this->superAdmin)->put(route('admin.beasiswa.perbarui', $scholarship), ['nama' => 'X'])->assertForbidden();
    actingAs($this->superAdmin)->delete(route('admin.beasiswa.hapus', $scholarship))->assertForbidden();

    $this->assertDatabaseHas('beasiswa', ['id' => $scholarship->id]);
});

// ─── Applicant: only admin deletes ──────────────────────────────

test('admin can delete applicant', function () {
    $applicant = Applicant::factory()->create();

    actingAs($this->admin)->delete(route('admin.pendaftar.hapus', $applicant))->assertRedirect();

    $this->assertDatabaseMissing('pendaftar', ['id' => $applicant->id]);
});

test('super admin cannot delete applicant', function () {
    $applicant = Applicant::factory()->create();

    actingAs($this->superAdmin)->delete(route('admin.pendaftar.hapus', $applicant))->assertForbidden();

    $this->assertDatabaseHas('pendaftar', ['id' => $applicant->id]);
});

// ─── Applicant: only admin verifies status ──────────────────────

test('admin updating applicant only changes status and catatan', function () {
    $applicant = Applicant::factory()->create([
        'fakultas' => 'Fakultas Awal',
        'prodi' => 'Prodi Awal',
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)->put(route('admin.pendaftar.perbarui', $applicant), [
        'status' => 'revisi',
        'catatan' => 'Lengkapi IPK',
        'fakultas' => 'Fakultas Baru',
        'prodi' => 'Prodi Baru',
        'ipk' => 3.9,
    ])->assertRedirect();

    $applicant->refresh();
    expect($applicant->status)->toBe('revisi');
    expect($applicant->catatan)->toBe('Lengkapi IPK');
    expect($applicant->fakultas)->toBe('Fakultas Awal');
    expect($applicant->prodi)->toBe('Prodi Awal');
});

test('super admin cannot verify or change applicant status', function () {
    $applicant = Applicant::factory()->create([
        'fakultas' => 'Fakultas Awal',
        'prodi' => 'Prodi Awal',
    ]);

    actingAs($this->superAdmin)->put(route('admin.pendaftar.perbarui', $applicant), [
        'status' => 'diterima',
        'fakultas' => 'Fakultas Baru',
        'prodi' => 'Prodi Baru',
        'ipk' => 3.8,
        'semester' => 6,
    ])->assertForbidden();

    $applicant->refresh();
    expect($applicant->status)->toBe('verifikasi');
    expect($applicant->fakultas)->toBe('Fakultas Awal');
});

// ─── Template: only admin ───────────────────────────────────────

test('admin can access template page', function () {
    actingAs($this->admin)->get(route('admin.template.index'))->assertOk();
});

test('super admin cannot access template page', function () {
    actingAs($this->superAdmin)->get(route('admin.template.index'))->assertForbidden();
});
