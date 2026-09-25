<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('sidebar', 'menu');

beforeEach(function () {
    seedAkses();
});

test('super admin sidebar shows role and menu access but not template surat', function () {
    $superAdmin = User::factory()->superAdmin()->create(['email' => 'sidebar-sa@test.com']);

    actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Kelola Akses', false)
        ->assertSee('<span>Role</span>', false)
        ->assertSee('<span>Akses Menu</span>', false)
        ->assertDontSee('<span>Template Surat</span>', false);
});

test('admin sidebar shows admin menus but not role and menu access', function () {
    $admin = User::factory()->admin()->create(['email' => 'sidebar-admin@test.com']);

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<span>Pendaftar</span>', false)
        ->assertSee('<span>Verifikasi Kesra</span>', false)
        // Halaman Kampus (Master Data) dikelola role kesra.
        ->assertSee('<span>Kampus</span>', false)
        ->assertDontSee('Kelola Akses', false)
        ->assertDontSee('<span>Role</span>', false)
        ->assertDontSee('<span>Akses Menu</span>', false);
});

test('kampus role sidebar does not show kampus master data menu', function () {
    $kampusAdmin = User::factory()->kampusAdmin()->create(['email' => 'sidebar-kampus@test.com']);

    actingAs($kampusAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<span>Verifikasi Kampus</span>', false)
        ->assertDontSee('<span>Kampus</span>', false)
        ->assertDontSee('Kelola Akses', false);
});

test('user sidebar only shows user module menus', function () {
    $user = User::factory()->standardUser()->create(['email' => 'sidebar-user@test.com']);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Daftar Beasiswa', false)
        ->assertSee('Pendaftaran Saya', false)
        ->assertDontSee('Kelola Akses', false)
        ->assertDontSee('Template Surat', false);
});
