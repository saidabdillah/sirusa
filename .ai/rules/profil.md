---
paths:
  - 'app/Http/Requests/Profil/**'
---

# Profil

## Every * field in the profile form is required; files are exempt only when already stored
The `*` in resources/views/profil/index.blade.php is a real contract: a field marked `*` MUST have a `required` rule, an Indonesian `@error` message below the input, and `is-invalid` on the control (radios need it on the input itself). Rules come from `UpdateProfilRequest::rules()`. Two traps: (1) file fields use the private `dokumen()` helper, which returns `nullable` instead of `required` when `$this->user()?->profile?->{$field}` is already filled, so editing a name does not force re-uploading 11 documents; (2) wali fields are gated on `$this->input('ikut_kk') === 'wali'`, NOT `required_if` — `required_if` validates hidden fields and the user cannot see or fix the error. Never add an HTML `required` attribute (asserted in ProfilTest). `nama_kampus`/`fakultas` are validated but not fillable, so they are dropped by `fill()` by design.
