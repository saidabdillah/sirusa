<?php

use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pengumuman', 'jadwal');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'jadwal-admin@test.com']);
    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'jadwal-sa@test.com']);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->scholarship = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'kampus' => $this->kampus->nama_kampus,
    ]);
});

test('admin can access jadwal pengumuman index', function () {
    actingAs($this->admin)
        ->get(route('admin.pengumuman.index'))
        ->assertOk();
});

test('super admin can access jadwal pengumuman index', function () {
    actingAs($this->superAdmin)
        ->get(route('admin.pengumuman.index'))
        ->assertOk();
});

test('user cannot access jadwal pengumuman index', function () {
    $user = User::factory()->standardUser()->create();
    actingAs($user)
        ->get(route('admin.pengumuman.index'))
        ->assertForbidden();
});

test('admin can open jadwal edit form', function () {
    actingAs($this->admin)
        ->get(route('admin.pengumuman.ubah', $this->scholarship))
        ->assertOk()
        ->assertSee($this->scholarship->nama);
});

test('admin can update jadwal pengumuman', function () {
    $mulai = now()->addMonths(2)->toDateString();
    $selesai = now()->addMonths(2)->addDays(7)->toDateString();

    actingAs($this->admin)
        ->put(route('admin.pengumuman.perbarui', $this->scholarship), [
            'tanggal_pengumuman' => $mulai,
            'tanggal_pengumuman_selesai' => $selesai,
        ])
        ->assertRedirect(route('admin.pengumuman.index'));

    $this->scholarship->refresh();
    expect($this->scholarship->tanggal_pengumuman->toDateString())->toBe($mulai);
    expect($this->scholarship->tanggal_pengumuman_selesai->toDateString())->toBe($selesai);
});

test('admin cannot set selesai before mulai', function () {
    actingAs($this->admin)
        ->put(route('admin.pengumuman.perbarui', $this->scholarship), [
            'tanggal_pengumuman' => now()->addMonths(3)->toDateString(),
            'tanggal_pengumuman_selesai' => now()->addMonths(2)->toDateString(),
        ])
        ->assertSessionHasErrors('tanggal_pengumuman_selesai');
});

test('admin cannot set mulai before batas_waktu', function () {
    $batasWaktu = $this->scholarship->batas_waktu->toDateString();

    actingAs($this->admin)
        ->put(route('admin.pengumuman.perbarui', $this->scholarship), [
            'tanggal_pengumuman' => now()->toDateString(),
            'tanggal_pengumuman_selesai' => now()->addDays(7)->toDateString(),
        ])
        ->assertSessionHasErrors('tanggal_pengumuman');

    expect($this->scholarship->refresh()->batas_waktu->toDateString())->toBe($batasWaktu);
    expect($this->scholarship->tanggal_pengumuman)->toBeNull();
});

test('admin can clear jadwal by sending null dates via update', function () {
    $this->scholarship->update([
        'tanggal_pengumuman' => now()->addMonth(),
        'tanggal_pengumuman_selesai' => now()->addMonth()->addDays(7),
    ]);

    actingAs($this->admin)
        ->put(route('admin.pengumuman.perbarui', $this->scholarship), [
            'tanggal_pengumuman' => null,
            'tanggal_pengumuman_selesai' => null,
        ])
        ->assertRedirect(route('admin.pengumuman.index'));

    $this->scholarship->refresh();
    expect($this->scholarship->tanggal_pengumuman)->toBeNull();
    expect($this->scholarship->tanggal_pengumuman_selesai)->toBeNull();
});

test('admin can delete jadwal pengumuman', function () {
    $this->scholarship->update([
        'tanggal_pengumuman' => now()->addMonth(),
        'tanggal_pengumuman_selesai' => now()->addMonth()->addDays(7),
        'pengumuman_notified_at' => now(),
    ]);

    actingAs($this->admin)
        ->delete(route('admin.pengumuman.hapus', $this->scholarship))
        ->assertRedirect(route('admin.pengumuman.index'));

    $this->scholarship->refresh();
    expect($this->scholarship->tanggal_pengumuman)->toBeNull();
    expect($this->scholarship->tanggal_pengumuman_selesai)->toBeNull();
    expect($this->scholarship->pengumuman_notified_at)->toBeNull();
});

test('delete resets pengumuman_notified_at', function () {
    $this->scholarship->update([
        'tanggal_pengumuman' => now()->addMonth(),
        'tanggal_pengumuman_selesai' => now()->addMonth()->addDays(7),
        'pengumuman_notified_at' => now(),
    ]);

    actingAs($this->admin)
        ->delete(route('admin.pengumuman.hapus', $this->scholarship));

    $this->scholarship->refresh();
    expect($this->scholarship->pengumuman_notified_at)->toBeNull();
});

test('update resets pengumuman_notified_at for rescheduling', function () {
    $this->scholarship->update([
        'tanggal_pengumuman' => now()->addMonth(),
        'tanggal_pengumuman_selesai' => now()->addMonth()->addDays(7),
        'pengumuman_notified_at' => now(),
    ]);

    $mulai = now()->addMonths(2)->toDateString();
    $selesai = now()->addMonths(2)->addDays(7)->toDateString();

    actingAs($this->admin)
        ->put(route('admin.pengumuman.perbarui', $this->scholarship), [
            'tanggal_pengumuman' => $mulai,
            'tanggal_pengumuman_selesai' => $selesai,
        ]);

    $this->scholarship->refresh();
    expect($this->scholarship->pengumuman_notified_at)->toBeNull();
});
