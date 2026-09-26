<?php

use App\Models\Kampus;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\NewApplication;
use App\Notifications\NewScholarship;
use App\Notifications\UserActivated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class)->group('notification');

beforeEach(function () {
    seedAkses();

    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'sa@test.com']);
    $this->admin = User::factory()->admin()->create(['email' => 'admin@test.com']);
    $this->standardUser = User::factory()->standardUser()->create(['email' => 'user@test.com']);
});

// ─── NewScholarship: admin creates scholarship → all users ──────

test('admin creating scholarship notifies all standard users', function () {
    Notification::fake();

    $kampus = Kampus::create(['nama_kampus' => 'Universitas']);
    $prodi = $kampus->fakultas()->create(['nama' => 'Teknik'])->prodi()->create(['nama' => 'Informatika']);

    actingAs($this->admin);

    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Test',
        'kampus_id' => $kampus->id,
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
        'tanggal_selesai' => now()->addMonth()->format('Y-m-d'),
        'ipk_minimal' => 3.0,
        'semester_minimal' => 3,
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Syarat',
        'status' => 'aktif',
        'prodi_ids' => [$prodi->id],
    ])->assertRedirect(route('admin.beasiswa.index'));

    $scholarship = Scholarship::where('nama', 'Beasiswa Test')->first();

    Notification::assertSentTo($this->standardUser, NewScholarship::class, function ($notification) use ($scholarship) {
        return $notification->scholarship->is($scholarship);
    });

    Notification::assertNotSentTo($this->admin, NewScholarship::class);
    Notification::assertNotSentTo($this->superAdmin, NewScholarship::class);
});

// ─── NewApplication: user registers → users granted admin.pendaftar ──

test('user registering scholarship notifies users granted admin.pendaftar', function () {
    Notification::fake();

    $kampus = Kampus::create(['nama_kampus' => 'Universitas']);
    $fakultas = $kampus->fakultas()->create(['nama' => 'Fakultas Teknik']);
    $prodi = $fakultas->prodi()->create(['nama' => 'Informatika']);

    UserProfile::create([
        'user_id' => $this->standardUser->id,
        'nama_lengkap' => 'Ahmad Fauzi',
        'nik' => '6302000000000001',
        'no_kk' => '6302000000000002',
        'tempat_lahir' => 'Balangan',
        'tanggal_lahir' => '2000-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'telepon' => '081234567890',
        'alamat' => 'RT 01 RW 02',
        'kecamatan' => 'Awayan',
        'desa_kelurahan' => 'Ambakiang',
        'prodi_id' => $prodi->id,
        'ipk' => 3.5,
        'semester' => 4,
        'ukt' => 2500000,
        'desil' => 3,
        'nama_ayah' => 'Ayah Ahmad',
        'nik_ayah' => '6302000000000003',
        'pekerjaan_ayah' => 'Petani',
        'nama_ibu' => 'Ibu Ahmad',
        'nik_ibu' => '6302000000000004',
        'pekerjaan_ibu' => 'Petani',
        'foto_profil' => 'profil/1/foto.jpg',
        'dokumen_ktp' => 'profil/1/ktp.pdf',
        'dokumen_kk' => 'profil/1/kk.pdf',
        'dokumen_desil' => 'profil/1/desil.pdf',
        'dokumen_sktm' => 'profil/1/sktm.pdf',
        'dokumen_transkrip' => 'profil/1/transkrip.pdf',
        'dokumen_surat_aktif' => 'profil/1/surat_aktif.pdf',
        'dokumen_surat_pernyataan' => 'profil/1/pernyataan.pdf',
        'dokumen_bukti_ukt' => 'profil/1/ukt.pdf',
        'ktp_ayah' => 'profil/1/ktp_ayah.pdf',
        'ktp_ibu' => 'profil/1/ktp_ibu.pdf',
        'verif_capil' => 'setuju',
        'verif_kampus' => 'setuju',
        'verif_kesra' => 'setuju',
    ]);

    $scholarship = Scholarship::factory()->create([
        'status' => 'aktif',
        'kampus_id' => $kampus->id,
        'ipk_minimal' => 0,
        'semester_minimal' => 0,
    ]);

    actingAs($this->standardUser);

    post(route('user.pendaftaran.simpan'), [
        'beasiswa_id' => $scholarship->id,
    ])->assertRedirect(route('user.pendaftaran.index'));

    Notification::assertSentTo($this->admin, NewApplication::class);
    Notification::assertSentTo($this->superAdmin, NewApplication::class);

    Notification::assertNotSentTo($this->standardUser, NewApplication::class);
});

// ─── NotificationController page ────────────────────────────────

test('user can view notifications page', function () {
    actingAs($this->admin)->get(route('notifications.index'))->assertOk();
});

test('unread notifications are marked as read when opened', function () {
    $this->standardUser->notifyNow(new NewScholarship(Scholarship::factory()->create()));

    $latest = $this->standardUser->notifications()->first();

    actingAs($this->standardUser)->get(route('notifications.show', $latest))->assertRedirect();

    expect($latest->refresh()->read_at)->not->toBeNull();
});

test('notification with absolute stored url redirects to same path on current host', function () {
    $this->standardUser->notify(new NewScholarship(Scholarship::factory()->create()));

    $notification = $this->standardUser->notifications()->first();
    $notification->data = ['url' => 'http://sirusa.test/pengguna/beasiswa/5'];
    $notification->save();

    actingAs($this->standardUser)
        ->get(route('notifications.show', $notification))
        ->assertRedirect(url('/pengguna/beasiswa/5'));
});

test('notification with relative stored url keeps path and query string', function () {
    $this->standardUser->notify(new NewScholarship(Scholarship::factory()->create()));

    $notification = $this->standardUser->notifications()->first();
    $notification->data = ['url' => '/admin/pengguna?page=2'];
    $notification->save();

    actingAs($this->standardUser)
        ->get(route('notifications.show', $notification))
        ->assertRedirect(url('/admin/pengguna?page=2'));
});

test('notification without stored url redirects to dashboard', function () {
    $this->standardUser->notify(new NewScholarship(Scholarship::factory()->create()));

    $notification = $this->standardUser->notifications()->first();
    $notification->data = [];
    $notification->save();

    actingAs($this->standardUser)
        ->get(route('notifications.show', $notification))
        ->assertRedirect(route('dashboard'));
});

// ─── Read-all action: must be POST, not GET ─────────────────────

test('read all notifications uses POST form on index page', function () {
    $response = actingAs($this->standardUser)->get(route('notifications.index'));

    $response->assertOk();
    $response->assertSee('name="_token"', false);
    $response->assertSee('action="'.route('notifications.read-all').'"', false);
});

test('read all notifications works via POST', function () {
    $this->standardUser->notifyNow(new NewScholarship(Scholarship::factory()->create()));

    $response = actingAs($this->standardUser)->post(route('notifications.read-all'));

    $response->assertRedirect();
    expect($this->standardUser->unreadNotifications()->count())->toBe(0);
});

// ─── Navbar dropdown only shows unread notifications ───────────

test('navbar dropdown shows unread notifications', function () {
    $scholarship = Scholarship::factory()->create();

    $this->admin->notifyNow(new NewScholarship($scholarship));

    $response = actingAs($this->admin)->get(route('notifications.index'));

    $response->assertOk();
    $response->assertSee('Beasiswa Baru Tersedia');
    $response->assertSee(route('notifications.show', $this->admin->notifications()->first()));
});

test('navbar dropdown hides notification after it is read', function () {
    $scholarship = Scholarship::factory()->create();

    $this->admin->notifyNow(new NewScholarship($scholarship));

    $latest = $this->admin->notifications()->first();

    actingAs($this->admin)->get(route('notifications.show', $latest));

    expect($this->admin->unreadNotifications()->count())->toBe(0);
});

test('navbar dropdown is empty after marking all as read', function () {
    $this->admin->notifyNow(new NewScholarship(Scholarship::factory()->create()));

    actingAs($this->admin)->post(route('notifications.read-all'));

    expect($this->admin->unreadNotifications()->count())->toBe(0);
});

// ─── UserActivated: admin toggles user to aktif → owner ─────────

test('activating a user sends UserActivated notification', function () {
    Notification::fake();

    $user = User::factory()->standardUser()->create(['status' => 'non-aktif']);

    actingAs($this->superAdmin)
        ->patch(route('admin.pengguna.toggle-status', $user))
        ->assertRedirect();

    Notification::assertSentTo($user, UserActivated::class);
});

test('deactivating a user does not send UserActivated notification', function () {
    Notification::fake();

    $user = User::factory()->standardUser()->create(['status' => 'aktif']);

    actingAs($this->superAdmin)
        ->patch(route('admin.pengguna.toggle-status', $user))
        ->assertRedirect();

    Notification::assertNotSentTo($user, UserActivated::class);
});

// ─── Delete notifications (A: per item, B: all, C: read only) ──

test('user can delete a single notification', function () {
    $this->admin->notifyNow(new NewScholarship(Scholarship::factory()->create()));
    $notification = $this->admin->notifications()->first();

    actingAs($this->admin)
        ->delete(route('notifications.destroy', $notification))
        ->assertRedirect();

    expect(DatabaseNotification::find($notification->id))->toBeNull();
});

test('user cannot delete another users notification', function () {
    $this->admin->notifyNow(new NewScholarship(Scholarship::factory()->create()));
    $notification = $this->admin->notifications()->first();

    actingAs($this->standardUser)
        ->delete(route('notifications.destroy', $notification))
        ->assertForbidden();

    expect(DatabaseNotification::find($notification->id))->not->toBeNull();
});

test('user can delete all notifications', function () {
    $this->admin->notifyNow(new NewScholarship(Scholarship::factory()->create()));
    $this->admin->notifyNow(new NewScholarship(Scholarship::factory()->create()));

    actingAs($this->admin)
        ->delete(route('notifications.destroy-all'))
        ->assertRedirect();

    expect($this->admin->notifications()->count())->toBe(0);
});

test('user can delete only read notifications', function () {
    $scholarship = Scholarship::factory()->create();

    $this->admin->notifyNow(new NewScholarship($scholarship));
    $readNotification = $this->admin->notifications()->first();
    $readNotification->markAsRead();

    $this->admin->notifyNow(new NewScholarship(Scholarship::factory()->create()));
    $unreadNotification = $this->admin->notifications()->whereNull('read_at')->first();

    actingAs($this->admin)
        ->delete(route('notifications.destroy-read'))
        ->assertRedirect();

    expect(DatabaseNotification::find($readNotification->id))->toBeNull();
    expect(DatabaseNotification::find($unreadNotification->id))->not->toBeNull();
});

test('notification index shows delete buttons', function () {
    $this->admin->notifyNow(new NewScholarship(Scholarship::factory()->create()));
    $notification = $this->admin->notifications()->first();

    $response = actingAs($this->admin)->get(route('notifications.index'));

    $response->assertOk();
    $response->assertSee(route('notifications.destroy', $notification), false);
    $response->assertSee(route('notifications.destroy-all'), false);
    $response->assertSee(route('notifications.destroy-read'), false);
    $response->assertSee('d-inline-block align-middle mr-1 mb-1 btn-delete-form', false);
    $response->assertDontSee('d-inline mr-1 mb-1 btn-delete-form', false);
});

test('notification index uses DataTables without colspan on empty state', function () {
    $response = actingAs($this->admin)->get(route('notifications.index'));

    $response->assertOk();
    $response->assertDontSee('colspan', false);
});
