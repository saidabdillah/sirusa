<?php

use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use App\Notifications\ApplicantStatusChanged;
use App\Notifications\NewApplication;
use App\Notifications\NewScholarship;
use App\Notifications\NewUserRegistered;
use App\Notifications\UserActivated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class)->group('notification');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'sa@test.com']);
    $this->admin = User::factory()->admin()->create(['email' => 'admin@test.com']);
    $this->standardUser = User::factory()->standardUser()->create(['email' => 'user@test.com']);
});

// ─── NewScholarship: admin creates scholarship → all users ──────

test('admin creating scholarship notifies all standard users', function () {
    Notification::fake();

    actingAs($this->admin);

    post(route('admin.beasiswa.simpan'), [
        'nama' => 'Beasiswa Test',
        'kampus' => 'Universitas',
        'kuota' => 10,
        'tingkat_gelar' => 'S1',
        'cakupan' => 'penuh',
        'batas_waktu' => now()->addMonth()->format('Y-m-d'),
        'deskripsi' => 'Deskripsi',
        'persyaratan' => 'Syarat',
        'status' => 'aktif',
        'fakultas' => [
            ['nama' => 'Teknik', 'prodi' => [['nama' => 'Informatika']]],
        ],
    ])->assertRedirect(route('admin.beasiswa.index'));

    $scholarship = Scholarship::where('nama', 'Beasiswa Test')->first();

    Notification::assertSentTo($this->standardUser, NewScholarship::class, function ($notification) use ($scholarship) {
        return $notification->scholarship->is($scholarship);
    });

    Notification::assertNotSentTo($this->admin, NewScholarship::class);
    Notification::assertNotSentTo($this->superAdmin, NewScholarship::class);
});

// ─── NewApplication: user registers scholarship → admins ────────

test('user registering scholarship notifies all admins and super admins', function () {
    Notification::fake();

    $scholarship = Scholarship::factory()->create(['status' => 'aktif']);

    actingAs($this->standardUser);

    post(route('user.pendaftaran.simpan'), array_merge([
        'beasiswa_id' => $scholarship->id,
        'fakultas' => 'Fakultas Teknik',
        'prodi' => 'Informatika',
        'ipk' => 3.5,
        'semester' => 4,
        'dokumen_ktp' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        'dokumen_kk' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
        'dokumen_surat_permohonan' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
        'dokumen_transkrip' => UploadedFile::fake()->create('transkrip.pdf', 100, 'application/pdf'),
        'dokumen_surat_aktif' => UploadedFile::fake()->create('aktif.pdf', 100, 'application/pdf'),
        'dokumen_pas_foto' => UploadedFile::fake()->image('foto.jpg'),
    ]))->assertRedirect(route('user.pendaftaran.index'));

    Notification::assertSentTo($this->admin, NewApplication::class);
    Notification::assertSentTo($this->superAdmin, NewApplication::class);

    Notification::assertNotSentTo($this->standardUser, NewApplication::class);
});

// ─── ApplicantStatusChanged: admin changes status → owner ───────

test('admin changing applicant status notifies the owner user', function () {
    Notification::fake();

    $applicant = Applicant::factory()->create([
        'user_id' => $this->standardUser->id,
        'status' => 'verifikasi',
    ]);

    actingAs($this->admin)
        ->put(route('admin.pendaftar.perbarui', $applicant), [
            'status' => 'revisi',
            'catatan' => 'Perbaiki dokumen',
        ])
        ->assertRedirect();

    Notification::assertSentTo($this->standardUser, ApplicantStatusChanged::class, function ($notification) {
        return $notification->newStatus === 'Perlu Revisi';
    });

    Notification::assertNotSentTo($this->admin, ApplicantStatusChanged::class);
});

// ─── NotificationController page ────────────────────────────────

test('user can view notifications page', function () {
    actingAs($this->admin)->get(route('notifications.index'))->assertOk();
});

test('unread notifications are marked as read when opened', function () {
    $notification = $this->standardUser->notifyNow(new NewScholarship(Scholarship::factory()->create()));

    $latest = $this->standardUser->notifications()->first();

    actingAs($this->standardUser)->get(route('notifications.show', $latest))->assertRedirect();

    expect($latest->refresh()->read_at)->not->toBeNull();
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

// ─── UserActivated: admin/super_admin activates user → email ────

test('activating a user sends UserActivated email', function () {
    Notification::fake();

    $user = User::factory()->standardUser()->create(['status' => 'non-aktif']);

    actingAs($this->admin)
        ->patch(route('admin.pengguna.toggle-status', $user))
        ->assertRedirect();

    Notification::assertSentTo($user, UserActivated::class);
});

test('deactivating a user does not send UserActivated email', function () {
    Notification::fake();

    $user = User::factory()->standardUser()->create(['status' => 'aktif']);

    actingAs($this->admin)
        ->patch(route('admin.pengguna.toggle-status', $user))
        ->assertRedirect();

    Notification::assertNotSentTo($user, UserActivated::class);
});

// ─── NewUserRegistered: user registers → admins & super admins ──

test('registering a new user notifies all admins and super admins', function () {
    Notification::fake();

    post(route('register.store'), [
        'email' => 'newuser.sirusa@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree' => '1',
    ])->assertRedirect(route('login'));

    Notification::assertSentTo($this->admin, NewUserRegistered::class);
    Notification::assertSentTo($this->superAdmin, NewUserRegistered::class);
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
    $this->admin->notifyNow(new NewUserRegistered('test@test.com'));

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

    $this->admin->notifyNow(new NewUserRegistered('test@test.com'));
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
});
