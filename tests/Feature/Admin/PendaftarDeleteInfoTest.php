<?php

use App\Models\Applicant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class)->group('pendaftar-delete-info');

beforeEach(function () {
    Role::create(['name' => 'admin']);
});

it('returns delete info json for pendaftar', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@deleteinfo.test']);
    $applicant = Applicant::factory()->create();
    $name = $applicant->user->profile->nama_lengkap ?? $applicant->user->username;

    $this->actingAs($admin)
        ->getJson(route('admin.pendaftar.info', $applicant))
        ->assertOk()
        ->assertJson([
            'title' => 'Hapus Data Pendaftar?',
            'icon' => 'warning',
            'confirmButtonText' => 'Ya, Hapus',
            'confirmButtonColor' => '#d33',
        ])
        ->assertJsonFragment(['text' => 'Data pendaftar atas nama "'.$name.'" beserta seluruh dokumennya akan dihapus permanen.']);
});
