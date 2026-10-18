---
paths:
  - 'tests/Feature/**'
---

# Feature

## Tests run on MySQL `sirusa_testing`, NOT sqlite (pdo_sqlite is not installed)
phpunit.xml defaults to sqlite `:memory:` but this host's PHP 8.5 CLI has no `pdo_sqlite` (installing php8.5-sqlite3 needs root; sudo is not available). Run the suite with the MySQL test database (created Oct-2026): `DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=sirusa_testing DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2) DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2) php artisan test`. As of the MySQL test-port work (Oct-2026) the FULL suite is green on MySQL (908/908) — the old sqlite-ism failures (17-digit NIK fixtures, `3.5` vs `3.50` decimal rendering, case-sensitive scope assertions, `whereNotNull('children')` on a non-column, auto-increment id equality) are all fixed; do not reintroduce them. Two hard rules: NEVER run two `php artisan test` processes at once (shared `sirusa_testing` → deadlocks and phantom column/schema errors), and NEVER leave `bootstrap/cache/config.php` behind (TestCase.php hard-fails every test; run `php artisan config:clear` if a `view:cache`/`config:cache` was done earlier in the session).

## Feature tests can't replay flashed validation errors across requests
Feature tests do not carry the session cookie, so each request gets a new session id and flashed `errors`/`_old_input` do not reliably survive PUT->GET. `withSession(['errors' => ...])` also gets reset before render. To assert that a page shows `@error`/`is-invalid` blocks, do: PUT -> `assertSessionHasErrors([...])` (proves the validator keys), then GET the page and render the returned `$response->original` View with `->withErrors([...])->render()` and assert the HTML toContain the messages. (ProfileController / UpdateProfilRequest)
