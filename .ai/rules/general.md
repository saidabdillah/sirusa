---
paths:
  - .env
---

# General

## Mailer is Mailtrap SMTP (not Resend)
Outbound mail uses Mailtrap SMTP: MAIL_MAILER=smtp, host sandbox.smtp.mailtrap.io, port 2525, with MAIL_USERNAME/MAIL_PASSWORD tokens and MAIL_FROM_ADDRESS=noreply@sirusa.test. Not Resend. For production, switch host to live.smtp.mailtrap.io with a sending-domain verified in Mailtrap. OTP notification is synchronous (no ShouldQueue).
