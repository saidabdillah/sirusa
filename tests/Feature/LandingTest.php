<?php

use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('landing');

beforeEach(function () {
    seedAkses();
});

test('guest sees masuk button on landing', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Masuk')
        ->assertDontSee('Dasbor');
});

test('authenticated user sees dasbor button instead of masuk on landing', function () {
    $user = User::factory()->standardUser()->create();

    actingAs($user)
        ->get(route('landing'))
        ->assertOk()
        ->assertSee('Dasbor')
        ->assertSee('Ke Dasbor')
        ->assertDontSee('Masuk');
});

test('admin sees lihat beasiswa linking to admin daftar beasiswa on landing', function () {
    $user = User::factory()->admin()->create();
    $scholarship = Scholarship::factory()->create(['status' => 'aktif']);

    actingAs($user)
        ->get(route('landing'))
        ->assertOk()
        ->assertSee('Lihat Beasiswa')
        ->assertSee(route('admin.beasiswa.index'))
        ->assertDontSee(route('user.beasiswa.lihat', $scholarship));
});

test('super admin sees lihat beasiswa linking to admin daftar beasiswa on landing', function () {
    $user = User::factory()->superAdmin()->create();
    $scholarship = Scholarship::factory()->create(['status' => 'aktif']);

    actingAs($user)
        ->get(route('landing'))
        ->assertOk()
        ->assertSee('Lihat Beasiswa')
        ->assertSee(route('admin.beasiswa.index'))
        ->assertDontSee(route('user.beasiswa.lihat', $scholarship));
});

test('user sees lihat beasiswa linking to the scholarship detail on landing', function () {
    $user = User::factory()->standardUser()->create();
    $scholarship = Scholarship::factory()->create(['status' => 'aktif']);

    actingAs($user)
        ->get(route('landing'))
        ->assertOk()
        ->assertSee('Lihat Beasiswa')
        ->assertSee(route('user.beasiswa.lihat', $scholarship))
        ->assertDontSee(route('admin.beasiswa.index'));
});

test('single campus is shown statically on landing', function () {
    Scholarship::factory()->create(['kampus' => 'Universitas Tunggal']);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Universitas Tunggal')
        ->assertDontSee('kampus-marquee__track"');
});

test('multiple campuses render the animated campus marquee', function () {
    Scholarship::factory()->create(['kampus' => 'Universitas A']);
    Scholarship::factory()->create(['kampus' => 'Universitas B']);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Universitas A')
        ->assertSee('Universitas B')
        ->assertSee('kampusTrack');
});

test('empty campus list shows no-campus message on landing', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Belum ada kampus mitra.');
});
