---
paths:
  - resources/views/dasbor/verifikasi.blade.php
---

# Views Dasbor

## No "next stage" widget on stage dashboards
The "Tahapan Berikutnya" widget was removed. Keep stage dashboards to the queue table plus (kesra only) the pending-registration table, and keep the summary stat cards. Cross-stage statistics stay in `dasbor/partials/tahapan.blade.php` and `funnel.blade.php` — do not reintroduce stage-order navigation as a widget.

## Kolom kartu antrean dasbor harus per tahap
Kartu "Menunggu Tindakan Anda" di dasbor Catpil/Kampus memakai `VerifikasiAntrean::antreanKolom()`, bukan daftar kolom tetap di view. Catpil = NIK/No. KK/Desil (kependudukan), Kampus = NIM/Program Studi/IPK (status mahasiswa). Jangan pakai satu daftar yang sama untuk keduanya: prodi dan kampus tidak pernah jadi dasar keputusan Catpil, dan NIK/KK tidak relevan untuk Kampus. Daftar lengkap ada di `admin/verifikasi/index.blade.php`; yang di dasbor hanya potongan tiga kolom.
