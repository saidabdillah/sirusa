---
paths:
  - resources/views/dasbor/verifikasi.blade.php
---

# Views Dasbor

## No "next stage" widget on stage dashboards
The "Tahapan Berikutnya" widget was removed. Keep stage dashboards to the queue table plus (kesra only) the pending-registration table, and keep the summary stat cards. Cross-stage statistics stay in `dasbor/partials/tahapan.blade.php` and `funnel.blade.php` — do not reintroduce stage-order navigation as a widget.
