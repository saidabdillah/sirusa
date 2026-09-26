<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('reset', 'password', 'pengguna');

beforeEach(function () {
    seedAkses();

    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'reset-sa@test.com']);
    $this->target = User::factory()->standardUser()->create([
        'email' => 'reset-target@test.com',
        'password' => bcrypt('oldpassword1'),
    ]);
});

test('edit form does not expose password or email fields', function () {
    actingAs($this->superAdmin)
        ->get(route('admin.pengguna.ubah', $this->target))
        ->assertOk()
        ->assertDontSee('name="password"', false)
        ->assertDontSee('name="password_confirmation"', false)
        ->assertDontSee('name="email"', false)
        ->assertDontSee('Reset Kata Sandi')
        ->assertDontSee('Konfirmasi Kata Sandi');
});

test('password cannot be changed through the edit form', function () {
    actingAs($this->superAdmin)
        ->put(route('admin.pengguna.perbarui', $this->target), [
            'peran' => 'user',
            'status' => 'aktif',
            'password' => 'barusandi123',
            'password_confirmation' => 'barusandi123',
        ])
        ->assertRedirect(route('admin.pengguna.index'));

    expect(Hash::check('oldpassword1', $this->target->fresh()->password))->toBeTrue();
});

test('password stays unchanged after a normal edit', function () {
    actingAs($this->superAdmin)
        ->put(route('admin.pengguna.perbarui', $this->target), ['peran' => 'user', 'status' => 'aktif'])
        ->assertRedirect(route('admin.pengguna.index'));

    expect(Hash::check('oldpassword1', $this->target->fresh()->password))->toBeTrue();
});
