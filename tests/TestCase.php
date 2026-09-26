<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Config yang ter-cache membuat nilai <env> di phpunit.xml diabaikan:
     * app.env tetap "local" sehingga CSRF aktif (setiap POST/PUT balas 419),
     * dan database.default tetap MySQL sehingga test menulis ke database dev.
     */
    protected function setUp(): void
    {
        if (file_exists(dirname(__DIR__).'/bootstrap/cache/config.php')) {
            throw new RuntimeException(
                'bootstrap/cache/config.php ada. Jalankan `php artisan config:clear` '
                .'sebelum menjalankan test, jika tidak test memakai config production/local.'
            );
        }

        parent::setUp();
    }
}
