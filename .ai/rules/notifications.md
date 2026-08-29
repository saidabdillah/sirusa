---
paths:
  - 'app/Notifications/**'
---

# Notifications

## Channel policy by notification type
- `NewScholarship` -> database (to all user role).
- `NewApplication` -> database (to all admin+super_admin).
- `ApplicantStatusChanged` -> `['mail', 'database']` (mail + in-app) to the owner user.
- `UserActivated` -> `['mail']` only (sent when admin/super_admin activates a user via toggle-status).
- `NewUserRegistered` -> `['database']` only (sent to all admin+super_admin when a new user registers).
- `OtpPasswordReset` -> `['mail']` only, synchronous (not ShouldQueue).

## Queue
All email/database notifications implement `ShouldQueue` except `OtpPasswordReset`. They are queued and require a running worker (`php artisan queue:work`) to be delivered.

Recipients: user registers scholarship -> NewApplication to all admin+super_admin; admin creates scholarship -> NewScholarship to all user role; applicant status changes -> ApplicantStatusChanged to the owner user. NO notifications on scholarship edit/delete or applicant delete. Notification data payload uses keys title/message/icon/url with a named route URL; navbar (View::composer) + NotificationController read these. Tests: in Pest, bare class names passed to Notification::assertSentTo must use `::class` (e.g. NewApplication::class) because the file-level `use` import does not resolve a bare identifier inside the test closure namespace.
