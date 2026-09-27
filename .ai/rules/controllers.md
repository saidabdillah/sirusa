---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## All write endpoints answer AJAX through RespondsToAjax
Every create/update/delete/decision method uses the `RespondsToAjax` trait and returns `RedirectResponse|JsonResponse`. Success is `ajaxOk()`, business failure is `ajaxFail()` (422), and never pass an explicit redirect when the old behaviour was `back()` -- `ajaxRedirect()` reads the `Referer` header instead. Non-AJAX requests must keep working: they get a redirect plus a `success`/`error` flash. Authorization stays in the controller; hiding buttons is never the guard.
