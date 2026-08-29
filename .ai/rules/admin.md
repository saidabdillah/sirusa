---
paths:
  - 'app/Http/Controllers/Admin/**'
---

# Admin

## Role authorization for admin user management
User management (PenggunaController) splits capabilities by role: both super_admin and admin can index users and toggle status; only super_admin can create, edit, delete users, or change roles. destroy() blocks deleting self and deleting other super_admins; toggleStatus blocks non-super_admin toggling a super_admin. Store/UpdateUserRequest authorize only super_admin; UpdateUserRequest marks 'peran' nullable (non-super admins never send it). Admin view (admin/pengguna/index) conditionally shows buttons per role.

## Scholarship role split
Scholarship (BeasiswaController) routes are split: index & lihat in a `role:super_admin|admin` group; create/store/edit/update/destroy in a `role:admin` group (routes/web.php). Store/UpdateScholarshipRequest authorize only via `hasRole('admin')`. super_admin is limited to viewing index+detail (no action buttons — views admin/beasiswa/{index,lihat} wrap actions in `@if(hasRole('admin'))`).

## Applicant role split
Applicant (PendaftarController) routes: index/lihat in `role:super_admin|admin` (both can view); perbarui (PUT status) and destroy (DELETE) in the `role:admin` group only (routes/web.php). So only admin verifies (changes status) and deletes applicants; super_admin is view-only (index/detail, no form/card — admin/pendaftar/lihat hides the "Ubah Status" and "Aksi" cards for non-admin). update() always filters validated data to only `status`+`catatan` keys.
