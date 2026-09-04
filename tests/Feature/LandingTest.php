<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('landing');

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);
});

test('guest sees masuk and daftar buttons on landing', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Masuk')
        ->assertSee('Daftar')
        ->assertDontSee('Dasbor');
});

test('authenticated user sees dashbor button instead of masuk and daftar on landing', function () {
    $user = User::factory()->standardUser()->create();

    actingAs($user)
        ->get(route('landing'))
        ->assertOk()
        ->assertSee('Dasbor')
        ->assertSee('Ke Dasbor')
        ->assertDontSee('Masuk')
        ->assertDontSee(route('register'))
        ->assertDontSee(route('login'));
});
