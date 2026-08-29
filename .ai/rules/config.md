---
paths:
  - config/services.php
---

# Config

## Resend mail config: use RESEND_API_KEY + verified from-domain
Resend SDK (via Laravel mail transport "resend") resolves its API key from config('services.resend.key'), which maps to env('RESEND_API_KEY') in config/services.php. Ensure the .env var is named RESEND_API_KEY (not RESEND_KEY) or the mailer throws 'Resend::client(): Argument #1 must be of type string, null given'. MAIL_FROM_ADDRESS must be a domain verified in the Resend dashboard or sends are rejected.
