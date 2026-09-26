---
paths:
  - 'tests/**'
---

# Tests

## Never run tests with a cached config
`bootstrap/cache/config.php` makes Laravel skip `LoadEnvironmentVariables`, so the `<env>` values in `phpunit.xml` are ignored: `app.env` stays `local`, `runningUnitTests()` is false, CSRF is enforced and EVERY POST/PUT feature test fails with 419, and `database.default` stays MySQL so the suite silently writes to the DEV database. `tests/TestCase.php::setUp()` throws a RuntimeException telling you to run `php artisan config:clear` whenever that file exists. If you see mass 419s or a `Connection: mysql ... Database: <dev db>` in a failure message, clear the config cache — do not debug the test.
