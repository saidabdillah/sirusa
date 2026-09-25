<?php

use App\Models\Kampus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('accept', 'beasiswa');

beforeEach(function () {
    seedAkses();

    $this->admin = User::factory()->admin()->create(['email' => 'admin@accept.test']);
    $this->user = User::factory()->standardUser()->create(['email' => 'user@accept.test']);
});

test('admin creating scholarship gets error feedback for invalid required fields', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), [
            'nama' => 'Beasiswa Tanpa Kuota',
            'kampus_id' => $kampus->id,
            'kuota' => '',
            'tingkat_gelar' => 'S1',
            'tanggal_mulai' => '',
            'tanggal_selesai' => '',
            'ipk_minimal' => '',
            'semester_minimal' => '',
            'deskripsi' => '',
            'persyaratan' => '',
            'status' => 'aktif',
            'prodi_ids' => [$prodi->id],
        ])
        ->assertSessionHasErrors([
            'kuota' => 'Kuota harus diisi',
            'tanggal_mulai' => 'Tanggal mulai harus diisi',
            'tanggal_selesai' => 'Tanggal selesai harus diisi',
            'ipk_minimal' => 'IPK minimal harus diisi',
            'semester_minimal' => 'Semester minimal harus diisi',
            'deskripsi' => 'Deskripsi harus diisi',
            'persyaratan' => 'Persyaratan harus diisi',
        ]);

    $this->assertDatabaseMissing('beasiswa', ['nama' => 'Beasiswa Tanpa Kuota']);
});

test('admin creating scholarship with out-of-range semester gets red error feedback', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Indonesia']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), [
            'nama' => 'Beasiswa Semester 0',
            'kampus_id' => $kampus->id,
            'kuota' => 10,
            'tingkat_gelar' => 'S1',
            'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
            'ipk_minimal' => 3.0,
            'semester_minimal' => 0,
            'deskripsi' => 'Deskripsi',
            'status' => 'aktif',
            'prodi_ids' => [$prodi->id],
        ])
        ->assertSessionHasErrors(['semester_minimal' => 'Semester minimal harus antara 1 hingga 14']);

    $this->assertDatabaseMissing('beasiswa', ['nama' => 'Beasiswa Semester 0']);
});
