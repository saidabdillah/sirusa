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

test('kesra sidebar shows admin menus plus Pengguna but not role and menu management', function () {
    $admin = User::factory()->admin()->create(['email' => 'sidebar-admin@test.com']);

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<span>Pendaftar</span>', false)
        ->assertSee('<span>Verifikasi Kesra</span>', false)
        // Halaman Kampus (Master Data) dikelola role kesra.
        ->assertSee('<span>Kampus</span>', false)
        // Kesra diberi grant `admin.pengguna`; group "Kelola Akses" ikut tampil karena
        // sidebarMenus() hanya mengambil menu parent, tapi anak lain tetap tersaring.
        ->assertSee('Kelola Akses', false)
        ->assertSee('<span>Pengguna</span>', false)
        ->assertDontSee('<span>Role</span>', false)
        ->assertDontSee('<span>Akses Menu</span>', false)
        ->assertDontSee('<span>Kelola Menu</span>', false);
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
