---
paths:
  - 'app/Support/VerifikasiAntrean.php, app/Http/Controllers/Dashboard/**, resources/views/dasbor/**'
---

# Dasbor

## Queue counts and dashboard dispatch come from one source, not from role names
Every count on a verification queue or the dashboard must be produced by `App\Support\VerifikasiAntrean`, which filters with the same `UserProfile::inVerifQueue()` the admin queue uses. Never re-implement the rule in SQL — a duplicated rule guarantees the dashboard and the queue disagree. Dashboard dispatch is by how many verification stages the user can reach via `hasMenuAccess('admin.<prefix>.index')`: 0 = student, 1 = stage dashboard, >1 = cross-stage overview. Never branch on `auth()->user()->hasRole(...)` in the dashboard; a role with no matching menu grant is the bug that made Catpil and Campus see the student page.

## Kartu "Status Verifikasi" di dasbor mahasiswa membaca `verifStageDecision()`
`DashboardController::mahasiswa()` melewatkan `profile` dan `dasbor/mahasiswa.blade.php` merender kartu full-width berisi satu blok per tahap (label dari `verifStageLabels()`, badge dari `verifStageDecision($stage)`) — sumber label/warna yang sama dengan antrean admin, jadi tidak boleh ditulis ulang di view. Kartu itu hanya muncul `@if($profile)` (akun tanpa baris profil tidak memilikinya). Catatan kaki "Catpil dan Kampus berjalan paralel; Kesra memutuskan setelah keduanya disetujui" sudah DIHAPUS sengaja (permintaan pengguna) — jangan ditambahkan kembali; penghapusan dikunci test `DashboardTest` `assertDontSee`. Jangan menghidupkan lagi kartu "Status Profil Saya" lama (dihapus, lihat komentar di `DashboardTest`) atau stepper/tahapan berurutan apa pun.

Slot **Kesra** pada kartu itu memakai `$kesraDecision` yang dikirim `DashboardController::mahasiswa()` (hasil `User::kesraDecision()`), bukan `$profile->verifStageDecision('kesra')` — `verif_kesra` selalu `setuju` untuk semua keputusan sehingga membacanya membuat pendaftaran DITOLAK tampil "Disetujui". Lihat `app/Models/User.php`; label/warna berasal dari `UserProfile::decisionForStatus()`.

## Alert kelengkapan profil di dasbor mahasiswa
`DashboardController::mahasiswa()` mengirim `profileComplete` (= `$user->isProfileComplete()`, definisi yang SAMA dengan gate pendaftaran — jangan menulis ulang daftar kolomnya di view), dan `dasbor/mahasiswa.blade.php` merender `alert alert-warning alert-dismissible` "Data profil Anda belum lengkap." dengan tautan ke `route('profile')` selama `@unless($profileComplete)`. Alert ini pernah dihapus (Okt-2026) dengan alasan "pengingat cukup lewat Profil/sidebar", lalu dikembalikan atas permintaan pengguna karena mahasiswa tidak tahu di mana harus mengisi. Alert hanya dirender di dalam `@if($profile)`, jadi akun tanpa baris profil (staf) tidak pernah melihatnya. Jangan hapus catatan lama di `DashboardTest` soal kartu "Status Profil Saya" — yang itu tetap tidak boleh dihidupkan kembali; yang dikembalikan hanya alert kelengkapannya. Covered by tests/Feature/ProfilTeleponDanAlertTest.php.

## Jangan taruh HTML entity di dalam `{{ }}`
`{{ '&mdash;' }}` di-escape dua kali (`&amp;mdash;`) sehingga browser menampilkan teks literal `&mdash;`. Tulis karakter aslinya (`—`, `©`, `·`) di dalam `{{ }}`, atau letakkan entity di luar `{{ }}` sebagai HTML mentah. Satu instance ini pernah bug di `dasbor/partials/tahapan.blade.php` dan test-nya lolos karena cocok dengan `<title>`.
