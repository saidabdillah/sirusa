<?php

use App\Models\Kampus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class)->group('admin', 'kampus');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->admin()->create([
        'email' => 'admin@test.com',
    ]);

    $this->superAdmin = User::factory()->superAdmin()->create([
        'email' => 'superadmin@test.com',
    ]);

    $this->user = User::factory()->create([
        'email' => 'user@test.com',
    ]);
});

function createKampusHierarchy(array $kampusOverrides = [], array $fakultasOverrides = [], array $prodiOverrides = []): array
{
    $kampus = Kampus::create(array_merge(['nama_kampus' => 'Universitas Lambung Mangkurat'], $kampusOverrides));
    $fakultas = $kampus->fakultas()->create(array_merge(['nama' => 'Fakultas Teknik'], $fakultasOverrides));
    $prodi = $fakultas->prodi()->create(array_merge(['nama' => 'Teknik Informatika'], $prodiOverrides));

    return [$kampus, $fakultas, $prodi];
}

// ─── Admin: Kampus ───────────────────────────────────────────────

test('admin can view kampus list', function () {
    Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);

    $this->actingAs($this->admin);

    $response = get(route('admin.kampus.index'));

    $response->assertOk();
    $response->assertViewIs('admin.kampus.index');
    $response->assertSee('Universitas Lambung Mangkurat');
});

test('buat kampus form uses trash remove button and server-side validation', function () {
    $this->actingAs($this->admin);

    get(route('admin.kampus.buat'))
        ->assertOk()
        ->assertSee('name="nama_kampus[]" value="" placeholder="Contoh: Universitas Lambung Mangkurat">', false)
        ->assertSee('fas fa-trash', false)
        ->assertSee('col-auto align-self-center', false)
        ->assertDontSee('btn-block baris-hapus', false);
});

test('ubah kampus forms do not use html5 required attribute', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $this->actingAs($this->admin);

    get(route('admin.kampus.ubah', $kampus))->assertOk()->assertDontSee(' required>', false);
    get(route('admin.kampus.fakultas.ubah', [$kampus, $fakultas]))->assertOk()->assertDontSee(' required>', false);
    get(route('admin.kampus.prodi.ubah', [$kampus, $fakultas, $prodi]))->assertOk()->assertDontSee(' required>', false);
});

test('admin can store kampus', function () {
    $this->actingAs($this->admin);

    post(route('admin.kampus.simpan'), ['nama_kampus' => ['Universitas Gadjah Mada']])
        ->assertRedirect(route('admin.kampus.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('kampus', ['nama_kampus' => 'Universitas Gadjah Mada']);
});

test('admin can bulk store multiple kampus at once', function () {
    $this->actingAs($this->admin);

    post(route('admin.kampus.simpan'), [
        'nama_kampus' => ['Universitas Gadjah Mada', 'Institut Teknologi Bandung'],
    ])
        ->assertRedirect(route('admin.kampus.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseCount('kampus', 2);
    $this->assertDatabaseHas('kampus', ['nama_kampus' => 'Universitas Gadjah Mada']);
    $this->assertDatabaseHas('kampus', ['nama_kampus' => 'Institut Teknologi Bandung']);
});

test('admin validation requires kampus name and rejects duplicate', function () {
    Kampus::create(['nama_kampus' => 'Universitas Lambung Mangkurat']);
    $this->actingAs($this->admin);

    post(route('admin.kampus.simpan'), [])->assertSessionHasErrors('nama_kampus');

    post(route('admin.kampus.simpan'), ['nama_kampus' => ['Universitas Lambung Mangkurat']])
        ->assertSessionHasErrors('nama_kampus');
    $this->assertDatabaseCount('kampus', 1);
});

test('bulk store rejects duplicate kampus names within one submission', function () {
    $this->actingAs($this->admin);

    post(route('admin.kampus.simpan'), [
        'nama_kampus' => ['Universitas Gadjah Mada', 'Universitas Gadjah Mada'],
    ])->assertSessionHasErrors('nama_kampus');

    $this->assertDatabaseCount('kampus', 0);
});

test('admin can update kampus', function () {
    $kampus = Kampus::create(['nama_kampus' => 'Universitas Lama']);
    $this->actingAs($this->admin);

    put(route('admin.kampus.perbarui', $kampus), ['nama_kampus' => 'Universitas Baru'])
        ->assertRedirect(route('admin.kampus.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('kampus', ['id' => $kampus->id, 'nama_kampus' => 'Universitas Baru']);
});

test('admin can delete kampus and cascade removes fakultas and prodi', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $this->actingAs($this->admin);

    delete(route('admin.kampus.hapus', $kampus))
        ->assertRedirect(route('admin.kampus.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('kampus', ['id' => $kampus->id]);
    $this->assertDatabaseMissing('fakultas', ['id' => $fakultas->id]);
    $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
});

// ─── Admin: Fakultas ─────────────────────────────────────────────

test('admin can view and manage fakultas of a kampus', function () {
    [$kampus, $fakultas] = createKampusHierarchy();
    $this->actingAs($this->admin);

    get(route('admin.kampus.fakultas.index', $kampus))
        ->assertOk()
        ->assertViewIs('admin.kampus.fakultas.index')
        ->assertSee('Fakultas Teknik');

    post(route('admin.kampus.fakultas.simpan', $kampus), ['nama' => ['Fakultas Hukum']])
        ->assertRedirect(route('admin.kampus.fakultas.index', $kampus))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('fakultas', ['kampus_id' => $kampus->id, 'nama' => 'Fakultas Hukum']);

    put(route('admin.kampus.fakultas.perbarui', [$kampus, $fakultas]), ['nama' => 'Fakultas Teknik Umum'])
        ->assertRedirect(route('admin.kampus.fakultas.index', $kampus));

    $this->assertDatabaseHas('fakultas', ['id' => $fakultas->id, 'nama' => 'Fakultas Teknik Umum']);
});

test('fakultas validation requires name and rejects duplicate within same kampus', function () {
    [$kampus] = createKampusHierarchy();
    $this->actingAs($this->admin);

    post(route('admin.kampus.fakultas.simpan', $kampus), [])->assertSessionHasErrors('nama');

    post(route('admin.kampus.fakultas.simpan', $kampus), ['nama' => ['Fakultas Teknik']])
        ->assertSessionHasErrors('nama');
    $this->assertDatabaseCount('fakultas', 1);
});

test('admin can bulk store multiple fakultas for a kampus', function () {
    [$kampus] = createKampusHierarchy();
    $this->actingAs($this->admin);

    post(route('admin.kampus.fakultas.simpan', $kampus), [
        'nama' => ['Fakultas Hukum', 'Fakultas Kedokteran'],
    ])
        ->assertRedirect(route('admin.kampus.fakultas.index', $kampus))
        ->assertSessionHas('success');

    $this->assertDatabaseCount('fakultas', 3);
    $this->assertDatabaseHas('fakultas', ['kampus_id' => $kampus->id, 'nama' => 'Fakultas Kedokteran']);
});

test('bulk store rejects duplicate fakultas names within one submission', function () {
    [$kampus] = createKampusHierarchy();
    $this->actingAs($this->admin);

    post(route('admin.kampus.fakultas.simpan', $kampus), [
        'nama' => ['Fakultas Teknik', 'Fakultas Teknik'],
    ])->assertSessionHasErrors('nama');

    $this->assertDatabaseCount('fakultas', 1);
});

test('same fakultas name is allowed on different kampus', function () {
    [$kampus] = createKampusHierarchy();
    $kampus2 = Kampus::create(['nama_kampus' => 'Politeknik Negeri Banjarmasin']);
    $this->actingAs($this->admin);

    post(route('admin.kampus.fakultas.simpan', $kampus2), ['nama' => ['Fakultas Teknik']])
        ->assertRedirect(route('admin.kampus.fakultas.index', $kampus2));

    $this->assertDatabaseCount('fakultas', 2);
});

test('admin can delete fakultas and cascade removes prodi', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $this->actingAs($this->admin);

    delete(route('admin.kampus.fakultas.hapus', [$kampus, $fakultas]))
        ->assertRedirect(route('admin.kampus.fakultas.index', $kampus));

    $this->assertDatabaseMissing('fakultas', ['id' => $fakultas->id]);
    $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
});

test('fakultas of another kampus cannot be accessed', function () {
    [$kampus, $fakultas] = createKampusHierarchy();
    $kampus2 = Kampus::create(['nama_kampus' => 'Politeknik Negeri Banjarmasin']);
    $this->actingAs($this->admin);

    get(route('admin.kampus.fakultas.index', $kampus2))->assertOk();
    get(route('admin.kampus.fakultas.ubah', [$kampus2, $fakultas]))->assertNotFound();
});

// ─── Admin: Prodi ────────────────────────────────────────────────

test('admin can view and manage prodi of a fakultas', function () {
    [$kampus, $fakultas] = createKampusHierarchy();
    $this->actingAs($this->admin);

    get(route('admin.kampus.prodi.index', [$kampus, $fakultas]))
        ->assertOk()
        ->assertViewIs('admin.kampus.prodi.index')
        ->assertSee('Teknik Informatika');

    post(route('admin.kampus.prodi.simpan', [$kampus, $fakultas]), ['nama' => ['Teknik Mesin']])
        ->assertRedirect(route('admin.kampus.prodi.index', [$kampus, $fakultas]))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('prodi', ['fakultas_id' => $fakultas->id, 'nama' => 'Teknik Mesin']);
});

test('prodi validation requires name and rejects duplicate within same fakultas', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $this->actingAs($this->admin);

    post(route('admin.kampus.prodi.simpan', [$kampus, $fakultas]), [])->assertSessionHasErrors('nama');

    post(route('admin.kampus.prodi.simpan', [$kampus, $fakultas]), ['nama' => ['Teknik Informatika']])
        ->assertSessionHasErrors('nama');
    $this->assertDatabaseCount('prodi', 1);
});

test('admin can bulk store multiple prodi for a fakultas', function () {
    [$kampus, $fakultas] = createKampusHierarchy();
    $this->actingAs($this->admin);

    post(route('admin.kampus.prodi.simpan', [$kampus, $fakultas]), [
        'nama' => ['Teknik Mesin', 'Teknik Sipil'],
    ])->assertRedirect(route('admin.kampus.prodi.index', [$kampus, $fakultas]));

    $this->assertDatabaseCount('prodi', 3);
    $this->assertDatabaseHas('prodi', ['fakultas_id' => $fakultas->id, 'nama' => 'Teknik Sipil']);
});

test('bulk store rejects duplicate prodi names within one submission', function () {
    [$kampus, $fakultas] = createKampusHierarchy();
    $this->actingAs($this->admin);

    post(route('admin.kampus.prodi.simpan', [$kampus, $fakultas]), [
        'nama' => ['Teknik Informatika', 'Teknik Informatika'],
    ])->assertSessionHasErrors('nama');

    $this->assertDatabaseCount('prodi', 1);
});

test('admin can edit update and delete prodi', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $this->actingAs($this->admin);

    get(route('admin.kampus.prodi.ubah', [$kampus, $fakultas, $prodi]))
        ->assertOk()
        ->assertViewIs('admin.kampus.prodi.ubah');

    put(route('admin.kampus.prodi.perbarui', [$kampus, $fakultas, $prodi]), ['nama' => 'Teknik Informatika (SI)'])
        ->assertRedirect(route('admin.kampus.prodi.index', [$kampus, $fakultas]));

    $this->assertDatabaseHas('prodi', ['id' => $prodi->id, 'nama' => 'Teknik Informatika (SI)']);

    delete(route('admin.kampus.prodi.hapus', [$kampus, $fakultas, $prodi]))
        ->assertRedirect(route('admin.kampus.prodi.index', [$kampus, $fakultas]));

    $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
});

test('prodi of another fakultas cannot be accessed', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $fakultas2 = $kampus->fakultas()->create(['nama' => 'Fakultas Hukum']);
    $this->actingAs($this->admin);

    get(route('admin.kampus.prodi.ubah', [$kampus, $fakultas2, $prodi]))->assertNotFound();
});

// ─── Admin: Mass Delete ─────────────────────────────────────────

test('admin can mass delete multiple kampus and cascade children', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $kampus2 = Kampus::create(['nama_kampus' => 'Politeknik Negeri Banjarmasin']);
    $this->actingAs($this->admin);

    delete(route('admin.kampus.massDestroy'), ['ids' => [$kampus->id, $kampus2->id]])
        ->assertRedirect(route('admin.kampus.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseCount('kampus', 0);
    $this->assertDatabaseMissing('fakultas', ['id' => $fakultas->id]);
    $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
});

test('mass delete kampus requires selected ids', function () {
    $this->actingAs($this->admin);

    delete(route('admin.kampus.massDestroy'), ['ids' => []])->assertSessionHasErrors('ids');

    $this->assertDatabaseCount('kampus', 0);
});

test('admin can mass delete fakultas scoped to the kampus', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $kampus2 = Kampus::create(['nama_kampus' => 'Politeknik Negeri Banjarmasin']);
    $fakultas2 = $kampus2->fakultas()->create(['nama' => 'Fakultas Hukum']);
    $this->actingAs($this->admin);

    delete(route('admin.kampus.fakultas.massDestroy', $kampus), ['ids' => [$fakultas->id, $fakultas2->id]])
        ->assertRedirect(route('admin.kampus.fakultas.index', $kampus))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('fakultas', ['id' => $fakultas->id]);
    $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
    $this->assertDatabaseHas('fakultas', ['id' => $fakultas2->id]);
});

test('admin can mass delete prodi scoped to the fakultas', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $fakultas2 = $kampus->fakultas()->create(['nama' => 'Fakultas Hukum']);
    $prodi2 = $fakultas2->prodi()->create(['nama' => 'Ilmu Hukum']);
    $this->actingAs($this->admin);

    delete(route('admin.kampus.prodi.massDestroy', [$kampus, $fakultas]), ['ids' => [$prodi->id, $prodi2->id]])
        ->assertRedirect(route('admin.kampus.prodi.index', [$kampus, $fakultas]))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
    $this->assertDatabaseHas('prodi', ['id' => $prodi2->id]);
});

test('mass delete only removes prodi belonging to the fakultas', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $fakultas2 = $kampus->fakultas()->create(['nama' => 'Fakultas Hukum']);
    $prodi2 = $fakultas2->prodi()->create(['nama' => 'Ilmu Hukum']);
    $this->actingAs($this->admin);

    delete(route('admin.kampus.prodi.massDestroy', [$kampus, $fakultas]), ['ids' => [$prodi->id, $prodi2->id]])
        ->assertRedirect(route('admin.kampus.prodi.index', [$kampus, $fakultas]));

    $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
    $this->assertDatabaseHas('prodi', ['id' => $prodi2->id]);
});

test('super admin cannot mass delete', function () {
    [$kampus] = createKampusHierarchy();
    $this->actingAs($this->superAdmin);

    delete(route('admin.kampus.massDestroy'), ['ids' => [$kampus->id]])->assertForbidden();

    $this->assertDatabaseCount('kampus', 1);
});

// ─── Super Admin: View Only ──────────────────────────────────────

test('super admin can view structure but cannot manage', function () {
    [$kampus, $fakultas, $prodi] = createKampusHierarchy();
    $this->actingAs($this->superAdmin);

    get(route('admin.kampus.index'))->assertOk();
    get(route('admin.kampus.fakultas.index', $kampus))->assertOk();
    get(route('admin.kampus.prodi.index', [$kampus, $fakultas]))->assertOk();

    post(route('admin.kampus.simpan'), ['nama_kampus' => ['Universitas Baru']])->assertForbidden();
    get(route('admin.kampus.buat'))->assertForbidden();
    put(route('admin.kampus.perbarui', $kampus), ['nama_kampus' => 'Universitas Baru'])->assertForbidden();
    delete(route('admin.kampus.hapus', $kampus))->assertForbidden();
    post(route('admin.kampus.fakultas.simpan', $kampus), ['nama' => ['Fakultas Hukum']])->assertForbidden();
    post(route('admin.kampus.prodi.simpan', [$kampus, $fakultas]), ['nama' => ['Teknik Mesin']])->assertForbidden();

    $this->assertDatabaseMissing('kampus', ['nama_kampus' => 'Universitas Baru']);
    $this->assertDatabaseCount('prodi', 1);
});

// ─── Regular User & Unauthenticated ──────────────────────────────

test('regular user cannot access kampus management', function () {
    [$kampus] = createKampusHierarchy();
    $this->actingAs($this->user);

    get(route('admin.kampus.index'))->assertForbidden();
    get(route('admin.kampus.fakultas.index', $kampus))->assertForbidden();
    get(route('admin.kampus.buat'))->assertForbidden();
});

test('unauthenticated user cannot access kampus management', function () {
    get(route('admin.kampus.index'))->assertRedirect(route('login'));
    get(route('admin.kampus.buat'))->assertRedirect(route('login'));
});
