---
paths:
  - 'app/Notifications/**'
  - 'app/Notifications/*.php'
---

# Notifications

## Channel policy: database-only (no email)
Every notification is database-only (via() = ['database']). There is no more email/OTP flow. Notifications:
- `NewScholarship` -> database, to all users with role `user` when admin creates a scholarship.
- `NewApplication` -> database, to all users with access to `admin.pendaftar` (kesra/super_admin by default; resolved via `User::usersGrantedMenu('admin.pendaftar')`). Its stored URL points to `admin.pendaftar.index` (the pendaftar module is read-only; the old `admin.pendaftar.lihat` link was removed).
- `UserActivated` -> database, to the owner user when admin toggles their status to aktif.
- `DataVerificationChanged` -> database, to the profil owner when a verifikasi stage (capil/kampus/kesra) sets setuju/revisi/tolak.

## Queue
All notifications send synchronously with `notify()` (no `ShouldQueue`, no scheduled announcement command — `SendScholarshipAnnouncement` and `PengumumanBeasiswa` were removed with the announcement feature).

## Payload & assertions
Notification data payload uses keys title/message/icon/url with a named route URL; navbar + NotificationController read these. Recipients for NewScholarship/NewApplication/UserActivated are resolved via the user relation `menus()`/`menuScopes()` or `role('user')` — see admin.md. Tests: in Pest, bare class names passed to Notification::assertSentTo must use `::class` (e.g. NewApplication::class) because the file-level `use` import does not resolve a bare identifier inside the test closure namespace.

## Verification notifications identify the stage that acted
Verification notifications name the responsible stage, not the admin account: the controller passes `$stage` into `DataVerificationChanged` and the notification resolves title, icon and body from it (capil = Admin Dukcapil / `fa-id-card`, kampus = Admin Kampus / `fa-university`, kesra = Admin Sirusa (Kesra) / `fa-hand-holding-heart`). Append ` Catatan: {catatan}` only for revisi/tolak. Do not read the stage from `verifikasi_capil`/`kampus`/`kesra` — those tables are dead and their enums do not match the statuses actually stored.
