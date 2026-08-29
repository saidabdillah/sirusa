---
paths:
  - 'app/Http/Controllers/Auth/**'
---

# Auth

## New registrations default to non-active
AuthController::simpanDaftar creates users with status 'non-aktif' (they must be activated by an admin before they can log in). Login (simpanMasuk) rejects accounts where status !== 'aktif'. Always seed roles/permissions before tests that use Spatie roles, since RefreshDatabase wipes them.
