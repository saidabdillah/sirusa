<?php

use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('beasiswa', 'pengumuman');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'admin@ann.test']);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $fakultas = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $this->prodi = $fakultas->prodi()->create(['nama' => 'Teknik Informatika']);
});

function announcementPayload(int $kampusId, int $prodiId, array $overrides = []): array
{
    return array_merge([
        'nama' => 'Beasiswa Pengumuman',
        'kampus_id' => $kampusId,
        'prodi_ids' => [$prodiId],
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'cakupan' => 'penuh',
        'batas_waktu' => now()->addMonth()->toDateString(),
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'IPK >= 3.0',
        'status' => 'aktif',
    ], $overrides);
}

test('create beasiswa ignores submitted announcement dates', function () {
    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), announcementPayload($this->kampus->id, $this->prodi->id, [
            'tanggal_pengumuman' => now()->addMonths(2)->toDateString(),
            'tanggal_pengumuman_selesai' => now()->addMonths(2)->addDays(7)->toDateString(),
        ]))
        ->assertRedirect(route('admin.beasiswa.index'));

    $scholarship = Scholarship::where('nama', 'Beasiswa Pengumuman')->first();

    expect($scholarship->tanggal_pengumuman)->toBeNull();
    expect($scholarship->tanggal_pengumuman_selesai)->toBeNull();
});

test('create beasiswa unchanged window dates cannot fail validation', function () {
    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), announcementPayload($this->kampus->id, $this->prodi->id, [
            'tanggal_pengumuman' => now()->addMonths(2)->addDays(7)->toDateString(),
            'tanggal_pengumuman_selesai' => now()->addMonths(2)->toDateString(),
        ]))
        ->assertRedirect(route('admin.beasiswa.index'));

    $scholarship = Scholarship::where('nama', 'Beasiswa Pengumuman')->first();

    expect($scholarship->tanggal_pengumuman)->toBeNull();
    expect($scholarship->tanggal_pengumuman_selesai)->toBeNull();
});

test('update beasiswa ignores submitted announcement dates', function () {
    $scholarship = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'kampus' => $this->kampus->nama_kampus,
        'tanggal_pengumuman' => now()->addMonths(2)->toDateString(),
        'tanggal_pengumuman_selesai' => now()->addMonths(2)->addDays(7)->toDateString(),
    ]);
    $prodiIds = [$this->prodi->id];

    actingAs($this->admin)
        ->put(route('admin.beasiswa.perbarui', $scholarship), [
            'nama' => $scholarship->nama,
            'kampus_id' => $this->kampus->id,
            'prodi_ids' => $prodiIds,
            'kuota' => 10,
            'tingkat_gelar' => 'S1',
            'cakupan' => 'penuh',
            'batas_waktu' => $scholarship->batas_waktu->toDateString(),
            'ipk_minimal' => 3.0,
            'semester_minimal' => 3,
            'deskripsi' => 'Deskripsi',
            'persyaratan' => 'IPK >= 3.0',
            'status' => 'aktif',
            'tanggal_pengumuman' => now()->addMonths(3)->toDateString(),
            'tanggal_pengumuman_selesai' => now()->addMonths(3)->addDays(7)->toDateString(),
        ])
        ->assertRedirect(route('admin.beasiswa.index'));

    $scholarship->refresh();

    expect($scholarship->tanggal_pengumuman->toDateString())->toBe(now()->addMonths(2)->toDateString());
    expect($scholarship->tanggal_pengumuman_selesai->toDateString())->toBe(now()->addMonths(2)->addDays(7)->toDateString());
});
