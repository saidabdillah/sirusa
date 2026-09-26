---
paths:
  - 'app/Models/Applicant.php, app/Http/Controllers/Admin/KeputusanPendaftaranController.php, app/Http/Controllers/User/PendaftaranController.php'
---

# Controllers User

## Scholarship decisions are per `pendaftar` row, never derived from `verif_kesra`
Layer 1 is identity verification per person (verif_capil/kampus/kesra). Layer 2 is the award decision per `pendaftar` row, and Kesra writes it explicitly. Deriving acceptance from `verif_kesra` auto-approves a second applicant after the first was rejected, because `verif_kesra` stays `setuju` across rounds. A student may hold only ONE blocking application at a time (`Applicant::STATUS_BLOCKING` = verifikasi + diterima); `ditolak` and `dibatalkan` both reopen the door, and `dibatalkan` is the student's own withdraw, not a Kesra decision. Re-applying to the same scholarship revives the cancelled row instead of inserting a new one, because `pendaftar` has a unique index on (user_id, beasiswa_id). Quota is re-checked inside a transaction with `lockForUpdate()` on the scholarship row, otherwise two admins take the last slot.
