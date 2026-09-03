<?php

use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Notifications\NewScholarship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('beasiswa', 'notification');

function beasiswaPayload(int $kampusId, array $prodiIds, array $overrides = []): array
{
    return array_merge([
        'nama' => 'Beasiswa Prestasi',
        'kampus_id' => $kampusId,
        'prodi_ids' => $prodiIds,
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

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'admin@notif.test']);
    $this->users = User::factory()->count(3)->standardUser()->create();

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $fakultas = $this->kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $this->prodiInformatika = $fakultas->prodi()->create(['nama' => 'Teknik Informatika']);
    $this->prodiMesin = $fakultas->prodi()->create(['nama' => 'Teknik Mesin']);
});

test('admin creating a scholarship sends NewScholarship to every user and stores snapshots', function () {
    Notification::fake();

    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), beasiswaPayload($this->kampus->id, [
            $this->prodiInformatika->id,
            $this->prodiMesin->id,
        ]))
        ->assertRedirect(route('admin.beasiswa.index'));

    Notification::assertSentTo($this->users, NewScholarship::class);

    $this->assertDatabaseHas('beasiswa', [
        'nama' => 'Beasiswa Prestasi',
        'kampus_id' => $this->kampus->id,
        'kampus' => 'Universitas Lambung Mangkurat',
    ]);
    $this->assertDatabaseHas('beasiswa_fakultas', ['nama' => 'Fakultas Teknik']);
    $this->assertDatabaseHas('beasiswa_prodi', ['nama' => 'Teknik Informatika']);
    $this->assertDatabaseHas('beasiswa_prodi', ['nama' => 'Teknik Mesin']);
});

test('NewScholarship is delivered via both email and database channels', function () {
    $scholarship = Scholarship::factory()->create();
    $notification = new NewScholarship($scholarship);

    expect($notification->via($this->users->first()))->toContain('mail')->toContain('database');
});

test('prodi from another campus is rejected when creating a scholarship', function () {
    Notification::fake();

    $kampusLain = Kampus::create(['nama_kampus' => 'Universitas Gadjah Mada']);
    $prodiLain = $kampusLain->fakultas()->create(['nama' => 'Fakultas Biologi'])->prodi()->create(['nama' => 'Biologi']);

    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), beasiswaPayload($this->kampus->id, [$prodiLain->id]))
        ->assertSessionHasErrors('prodi_ids');

    $this->assertDatabaseCount('beasiswa', 0);
    Notification::assertNothingSent();
});

test('creating a scholarship requires at least one program studi', function () {
    Notification::fake();

    actingAs($this->admin)
        ->post(route('admin.beasiswa.simpan'), beasiswaPayload($this->kampus->id, []))
        ->assertSessionHasErrors('prodi_ids');

    $this->assertDatabaseCount('beasiswa', 0);
    Notification::assertNothingSent();
});

test('admin updating a scholarship replaces kampus and prodi snapshots without notification', function () {
    Notification::fake();

    $scholarship = Scholarship::factory()->create();
    $kampusLain = Kampus::create(['nama_kampus' => 'Universitas Gadjah Mada']);
    $prodiLain = $kampusLain->fakultas()->create(['nama' => 'Fakultas Biologi'])->prodi()->create(['nama' => 'Biologi']);

    actingAs($this->admin)
        ->put(route('admin.beasiswa.perbarui', $scholarship), beasiswaPayload($kampusLain->id, [$prodiLain->id]))
        ->assertRedirect(route('admin.beasiswa.index'));

    $scholarship->refresh();
    expect($scholarship->kampus_id)->toBe($kampusLain->id);
    expect($scholarship->kampus)->toBe('Universitas Gadjah Mada');
    $this->assertDatabaseHas('beasiswa_fakultas', ['nama' => 'Fakultas Biologi']);
    $this->assertDatabaseHas('beasiswa_prodi', ['nama' => 'Biologi']);

    Notification::assertNothingSent();
});
