---
paths:
  - 'database/migrations/**'
---

# Migrations

## Edit the existing migration, never add a compensating one
Schema changes go straight into the migration that owns the column (e.g. adding a `dibatalkan` value to the `pendaftar.status` enum means editing `create_pendaftar_table.php`). Do not add a follow-up `alter` migration. The project is pre-release, so nobody has data worth preserving. The one thing you MUST tell the user: their local database has already run the old migration, so the edit will not apply until they run `migrate:fresh`.
