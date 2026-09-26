---
paths:
  - 'app/Support/VerifikasiAntrean.php, app/Http/Controllers/Dashboard/**, resources/views/dasbor/**'
---

# Dasbor

## Queue counts and dashboard dispatch come from one source, not from role names
Every count on a verification queue or the dashboard must be produced by `App\Support\VerifikasiAntrean`, which filters with the same `UserProfile::canVerifStage()` the admin actually clicks. Never re-implement the rule in SQL — a duplicated rule guarantees the dashboard and the queue disagree. Dashboard dispatch is by how many verification stages the user can reach via `hasMenuAccess('admin.<prefix>.index')`: 0 = student, 1 = stage dashboard, >1 = cross-stage overview. Never branch on `auth()->user()->hasRole(...)` in the dashboard; a role with no matching menu grant is the bug that made Capil and Campus see the student page.
