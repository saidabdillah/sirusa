<?php

use App\Models\Applicant;
use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('admin', 'datatable');

beforeEach(function () {
    seedAkses();

    $this->admin = User::factory()->superAdmin()->create(['email' => 'admin@datatable.test']);
    $this->kampusAdmin = User::factory()->admin()->create(['email' => 'kampus@datatable.test']);
    $this->user = User::factory()->standardUser()->create(['email' => 'user@datatable.test']);
});

test('kampus data endpoint returns datatables payload with server-rendered rows', function () {
    $kampus = Kampus::query()->create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    Kampus::query()->create(['nama_kampus' => 'Politeknik Negeri Banjarmasin']);

    actingAs($this->kampusAdmin)
        ->getJson(route('admin.kampus.data', ['draw' => 3, 'start' => 0, 'length' => 10]))
        ->assertOk()
        ->assertJson([
            'draw' => 3,
            'recordsTotal' => 2,
            'recordsFiltered' => 2,
        ])
        ->assertJsonPath('data.0.no', 1)
        ->assertJsonPath('data.1.no', 2)
        ->assertJsonPath('data.0.kampus', 'Politeknik Negeri Banjarmasin')
        ->assertJsonPath('data.1.kampus', 'Universitas Lambung Mangkurat');
});

test('kampus data rows include bulk-delete checkbox and standardised action buttons', function () {
    $kampus = Kampus::query()->create(['nama_kampus' => 'Universitas Lambung Mangkurat']);

    $html = actingAs($this->kampusAdmin)
        ->getJson(route('admin.kampus.data'))
        ->assertOk()
        ->json('data.0.aksi');

    $checkbox = actingAs($this->kampusAdmin)
        ->getJson(route('admin.kampus.data'))
        ->json('data.0.checkbox');

    expect($html)
        ->toContain('btn btn-info btn-sm mr-1 mb-1')
        ->toContain('btn btn-danger btn-sm btn-delete')
        ->toContain('d-inline-block align-middle mr-1 mb-1 btn-delete-form')
        ->toContain('/admin/kampus/'.$kampus->id)
        ->not->toContain('confirmDelete(');

    expect($checkbox)
        ->toContain('class="row-check"')
        ->toContain('value="'.$kampus->id.'"');
});

test('fakultas data endpoint returns rows scoped to the kampus', function () {
    $kampus = Kampus::query()->create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $kampus->fakultas()->create(['nama' => 'Fakultas Hukum']);

    actingAs($this->kampusAdmin)
        ->getJson(route('admin.kampus.fakultas.data', $kampus))
        ->assertOk()
        ->assertJson(['recordsTotal' => 2])
        ->assertJsonPath('data.0.nama', 'Fakultas Hukum')
        ->assertJsonPath('data.1.nama', 'Fakultas Teknik');
});

test('prodi data endpoint returns rows scoped to the fakultas', function () {
    $kampus = Kampus::query()->create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $fakultas = $kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $fakultas->prodi()->create(['nama' => 'Teknik Informatika']);

    actingAs($this->kampusAdmin)
        ->getJson(route('admin.kampus.prodi.data', [$kampus, $fakultas]))
        ->assertOk()
        ->assertJson(['recordsTotal' => 1])
        ->assertJsonPath('data.0.nama', 'Teknik Informatika')
        ->assertJsonPath('data.0.no', 1);
});

test('beasiswa data endpoint returns rows with status badge', function () {
    Scholarship::factory()->create(['nama' => 'Beasiswa Kaltim Tuntas']);

    actingAs($this->admin)
        ->getJson(route('admin.beasiswa.data'))
        ->assertOk()
        ->assertJson(['recordsTotal' => 1])
        ->assertJsonPath('data.0.nama', 'Beasiswa Kaltim Tuntas')
        ->assertJsonPath('data.0.status', '<span class="badge badge-success">Aktif</span>');
});

test('pendaftar data endpoint respects status filter', function () {
    $scholarship = Scholarship::factory()->create();
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'diterima']);
    Applicant::factory()->create(['beasiswa_id' => $scholarship->id, 'status' => 'verifikasi']);

    $response = actingAs($this->admin)
        ->getJson(route('admin.pendaftar.data', ['status' => 'diterima']))
        ->assertOk()
        ->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 1])
        ->assertJsonPath('data.0.status', '<span class="badge badge-success">Diterima</span>');

    expect($response->json('data.0.aksi'))->toBeNull();
});

test('pendaftar data endpoint searches by applicant name', function () {
    $scholarship = Scholarship::factory()->create();

    $user = User::factory()->standardUser()->create(['email' => 'penerima@datatable.test']);
    $user->profile()->create(['nama_lengkap' => 'Rahmawati']);
    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'user_id' => $user->id,
        'status' => 'verifikasi',
    ]);

    Applicant::factory()->create([
        'beasiswa_id' => $scholarship->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)
        ->getJson(route('admin.pendaftar.data', ['search[value]' => 'Rahmawati']))
        ->assertOk()
        ->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 1])
        ->assertJsonPath('data.0.nama', 'Rahmawati');
});

test('pendaftar data endpoint numbers rows from the pagination offset', function () {
    $scholarship = Scholarship::factory()->create();
    Applicant::factory()->count(3)->create(['beasiswa_id' => $scholarship->id, 'status' => 'verifikasi']);

    actingAs($this->admin)
        ->getJson(route('admin.pendaftar.data', ['start' => 2, 'length' => 10]))
        ->assertOk()
        ->assertJsonPath('data.0.no', 3)
        ->assertJsonCount(1, 'data');
});

test('datatable data endpoints are forbidden for users without the menu grant', function () {
    actingAs($this->user)
        ->getJson(route('admin.kampus.data'))
        ->assertForbidden();

    actingAs($this->user)
        ->getJson(route('admin.pendaftar.data'))
        ->assertForbidden();
});
