---
paths:
  - 'resources/views/**'
---

# Views

## No HTML required attribute in forms
Do not add the HTML `required` attribute to form fields. Validation is enforced server-side via Form Requests (including conditional `required_if` for parent/guardian documents based on status_orang_tua). Keep the red `*` span in labels as a visual hint only.

## No client-side file validation in pendaftaran forms
The ajukan-pendaftaran form (`user/pendaftaran/buat.blade.php`) must NOT contain client-side file validation (`preventDefault`, `validateFiles`, SweetAlert size/extension blocking). All file rules live only in `StoreApplicantRequest` / `UpdateApplicantRequest` (`mimes:pdf,jpg,jpeg,png`, pas foto `mimes:jpg,jpeg,png`, `max:2048`, `required_if` for ktp/kk). Errors render server-side via `@error(...) is-invalid`. This file has no `@push('script')` block at all.
