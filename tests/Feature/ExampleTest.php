<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the landing page returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Sistem Informasi Beasiswa');
});
