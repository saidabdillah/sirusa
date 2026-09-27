---
paths:
  - 'resources/views/admin/beasiswa/**'
---

# Beasiswa

## Both beasiswa date forms go through window.initDatePicker
`buat.blade.php` and `ubah.blade.php` must both call `initDatePicker('.flatpickr')` from `custom.js` and both inputs must be `type="text"` with the `flatpickr` class. The helper keeps the submitted value as `Y-m-d` while showing `d/m/Y` via `altInput`, and `disableMobile: true` so the native picker opens on Android/iOS. Do not re-declare a local `flatpickr(...)` call in a view -- that is how the two forms drifted apart.
