<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('pengumuman', 'pdf');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'pdf-admin@test.com']);
    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'pdf-sa@test.com']);
    $this->user = User::factory()->standardUser()->create(['email' => 'pdf-user@test.com']);

    $this->kampus = Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->scholarship = Scholarship::factory()->create([
        'kampus_id' => $this->kampus->id,
        'kampus' => $this->kampus->nama_kampus,
    ]);
});

function createAcceptedApplicant(Scholarship $scholarship, User $user): Applicant
{
    return Applicant::factory()->create([
        'user_id' => $user->id,
        'beasiswa_id' => $scholarship->id,
        'status' => 'diterima',
    ]);
}

test('admin can export penerima as pdf', function () {
    createAcceptedApplicant($this->scholarship, $this->user);

    actingAs($this->admin)
        ->get(route('admin.pengumuman.export-pdf', $this->scholarship))
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
});

test('super admin can export penerima as pdf', function () {
    createAcceptedApplicant($this->scholarship, $this->user);

    actingAs($this->superAdmin)
        ->get(route('admin.pengumuman.export-pdf', $this->scholarship))
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
});

test('regular user cannot export penerima as pdf', function () {
    createAcceptedApplicant($this->scholarship, $this->user);

    actingAs($this->user)
        ->get(route('admin.pengumuman.export-pdf', $this->scholarship))
        ->assertForbidden();
});

test('pdf export returns 404 when no penerima', function () {
    actingAs($this->admin)
        ->get(route('admin.pengumuman.export-pdf', $this->scholarship))
        ->assertNotFound();
});
