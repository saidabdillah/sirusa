<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class)->group('errors');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);
});

test('404 page uses stisla error view', function () {
    get('/route-tidak-ada')
        ->assertNotFound()
        ->assertSee('404')
        ->assertSee('Halaman yang Anda cari tidak ditemukan');
});

test('403 page uses stisla error view', function () {
    $superAdmin = User::factory()->superAdmin()->create(['email' => 'sa@test.com']);

    actingAs($superAdmin)
        ->get(route('admin.beasiswa.buat'))
        ->assertForbidden()
        ->assertSee('403')
        ->assertSee('Anda tidak memiliki akses ke halaman ini');
});

test('401 page uses stisla error view', function () {
    Route::get('/_errors/401', fn () => abort(401));

    get('/_errors/401')
        ->assertStatus(401)
        ->assertSee('401')
        ->assertSee('Sesi Anda telah berakhir');
});

test('419 page uses stisla error view', function () {
    Route::get('/_errors/419', fn () => abort(419));

    get('/_errors/419')
        ->assertStatus(419)
        ->assertSee('419')
        ->assertSee('Halaman ini kedaluwarsa');
});

test('500 page uses stisla error view', function () {
    Route::get('/_errors/500', fn () => abort(500));

    get('/_errors/500')
        ->assertStatus(500)
        ->assertSee('500')
        ->assertSee('Terjadi kesalahan pada server');
});

test('503 page uses stisla error view', function () {
    Route::get('/_errors/503', fn () => abort(503));

    get('/_errors/503')
        ->assertStatus(503)
        ->assertSee('503')
        ->assertSee('Sistem sedang dalam pemeliharaan');
});
