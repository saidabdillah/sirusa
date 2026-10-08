---
paths:
  - .env
---

# General

## No outbound email is required
There is no notification feature left (it was removed entirely: routes, `NotificationController`, `app/Notifications/*`, the navbar bell and the `notifications` table). No mail driver, mailtrap, or OTP infrastructure is used. MAIL_* env vars are not load-bearing for the app's features. If the project outputs any email at all it is optional, and nothing in the code depends on it.