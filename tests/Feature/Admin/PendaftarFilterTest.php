<?php

use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('filter', 'pendaftar');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create(['email' => 'admin@filter.test']);
    $this->user = User::factory()->standardUser()->create(['email' => 'user@filter.test']);
});

test('pendaftar index page renders with filter form', function () {
    actingAs($this->admin)
        ->get(route('admin.pendaftar.index'))
        ->assertOk()
        ->assertSee('Daftar Pendaftar')
        ->assertSee('-- Semua Status --')
        ->assertSee('-- Semua Beasiswa --');
});

test('pendaftar index filters applicants by status', function () {
    $scholarship = Scholarship::factory()->create();

    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $this->user->id,
        'status' => 'diterima',
    ]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);

    $response = actingAs($this->admin)
        ->get(route('admin.pendaftar.index', ['status' => 'diterima']))
        ->assertOk();

    $applicants = $response->viewData('applicants');

    expect($applicants)->count()->toBe(1);
    expect($applicants->first()->status)->toBe('diterima');
});

test('pendaftar index filters applicants by beasiswa', function () {
    $scholarshipA = Scholarship::factory()->create(['nama' => 'Beasiswa Filter A']);
    $scholarshipB = Scholarship::factory()->create(['nama' => 'Beasiswa Filter B']);

    Applicant::factory()->create([
        'beasiswa_id' => $scholarshipA->id,
        'user_id' => $this->user->id,
        'status' => 'verifikasi',
    ]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarshipB->id,
        'status' => 'verifikasi',
    ]);

    $response = actingAs($this->admin)
        ->get(route('admin.pendaftar.index', ['beasiswa_id' => $scholarshipA->id]))
        ->assertOk();

    $applicants = $response->viewData('applicants');

    expect($applicants)->count()->toBe(1);
    expect($applicants->first()->beasiswa_id)->toBe($scholarshipA->id);
});

test('pendaftar index combines status and beasiswa filter', function () {
    $scholarshipA = Scholarship::factory()->create(['nama' => 'Beasiswa Kombinasi A']);
    $scholarshipB = Scholarship::factory()->create(['nama' => 'Beasiswa Kombinasi B']);

    Applicant::factory()->create([
        'beasiswa_id' => $scholarshipA->id,
        'user_id' => $this->user->id,
        'status' => 'diterima',
    ]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarshipA->id,
        'status' => 'verifikasi',
    ]);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarshipB->id,
        'status' => 'diterima',
    ]);

    $response = actingAs($this->admin)
        ->get(route('admin.pendaftar.index', ['status' => 'diterima', 'beasiswa_id' => $scholarshipA->id]))
        ->assertOk();

    $applicants = $response->viewData('applicants');

    expect($applicants)->count()->toBe(1);
    expect($applicants->first()->beasiswa_id)->toBe($scholarshipA->id);
    expect($applicants->first()->status)->toBe('diterima');
});

test('pendaftar index rejects invalid status filter', function () {
    actingAs($this->admin)
        ->get(route('admin.pendaftar.index', ['status' => 'bogus']))
        ->assertSessionHasErrors('status');
});

test('pendaftar index rejects invalid beasiswa filter', function () {
    actingAs($this->admin)
        ->get(route('admin.pendaftar.index', ['beasiswa_id' => 9999]))
        ->assertSessionHasErrors('beasiswa_id');
});

test('pendaftar index inaccessible to non-admin users', function () {
    actingAs($this->user)
        ->get(route('admin.pendaftar.index'))
        ->assertForbidden();
});
