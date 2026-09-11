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
        ->get(route('pengumuman.export-pdf', $this->scholarship))
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
});

test('super admin can export penerima as pdf', function () {
    createAcceptedApplicant($this->scholarship, $this->user);

    actingAs($this->superAdmin)
        ->get(route('pengumuman.export-pdf', $this->scholarship))
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
});

test('pdf export returns 404 when no penerima', function () {
    actingAs($this->admin)
        ->get(route('pengumuman.export-pdf', $this->scholarship))
        ->assertNotFound();
});

test('applicant can export penerima as pdf during active window', function () {
    $this->scholarship->update([
        'tanggal_pengumuman' => now()->subDay()->toDateString(),
        'tanggal_pengumuman_selesai' => now()->addDays(5)->toDateString(),
    ]);
    createAcceptedApplicant($this->scholarship, $this->user);

    actingAs($this->user)
        ->get(route('pengumuman.export-pdf', $this->scholarship))
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
});

test('non-applicant user cannot export penerima as pdf during active window', function () {
    $this->scholarship->update([
        'tanggal_pengumuman' => now()->subDay()->toDateString(),
        'tanggal_pengumuman_selesai' => now()->addDays(5)->toDateString(),
    ]);
    $stranger = User::factory()->standardUser()->create(['email' => 'stranger@test.com']);
    createAcceptedApplicant($this->scholarship, $this->user);

    actingAs($stranger)
        ->get(route('pengumuman.export-pdf', $this->scholarship))
        ->assertNotFound();
});

test('penerima pdf view renders kop surat and daftar penerima title', function () {
    createAcceptedApplicant($this->scholarship, $this->user);

    $penerima = $this->scholarship->penerima()->get();
    $html = view('admin.exports.penerima_pdf', [
        'scholarship' => $this->scholarship,
        'penerima' => $penerima,
    ])->render();

    expect($html)
        ->toContain('PEMERINTAH KABUPATEN BALANGAN')
        ->toContain('SEKRETARIAT DAERAH')
        ->toContain('Jl. Jenderal Ahmad Yani No. 1')
        ->toContain('71662')
        ->toContain('Daftar Penerima Beasiswa')
        ->toContain(strtoupper($this->scholarship->nama))
        ->toContain('<th>NIM</th>')
        ->toContain('<th>Jenis Kelamin</th>')
        ->toContain('<th>Kampus</th>')
        ->toContain('<th>Fakultas</th>')
        ->toContain('<th>Program Studi</th>')
        ->not->toContain('<strong>Kampus:</strong>')
        ->not->toContain('<th>IPK</th>')
        ->not->toContain('Total Penerima')
        ->not->toContain('Periode Pengumuman')
        ->not->toContain('Dicetak pada');
});
