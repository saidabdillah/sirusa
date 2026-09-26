# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| app/Http/Controllers/Admin/**, app/Http/Controllers/Admin/PenggunaController.php, app/Http/Controllers/Admin/VerifikasiController.php | .ai/rules/admin.md |
| app/Http/Middleware/**, app/Models/{User,Menu}.php, routes/web.php, resources/views/layouts/** | .ai/rules/admin.md |
| app/Models/Applicant.php, app/Http/Controllers/Admin/PendaftarController.php, app/Models/UserProfile.php | .ai/rules/applicant.md |
| app/Http/Controllers/Auth/**, resources/views/auth/** | .ai/rules/auth.md |
| config/services.php, config/filesystems.php | .ai/rules/config.md |
| tests/Feature/** | .ai/rules/feature.md |
| .env | .ai/rules/general.md |
| app/Notifications/**, app/Notifications/*.php | .ai/rules/notifications.md |
| resources/views/user/pendaftaran/** | .ai/rules/pendaftaran.md |
| tests/** | .ai/rules/tests.md |
| app/Http/Requests/User/*.php, app/Http/Requests/User/** | .ai/rules/user.md |
| resources/views/admin/verifikasi/** | .ai/rules/verifikasi.md |
| resources/views/admin/** | .ai/rules/views-admin.md |
| resources/views/** | .ai/rules/views.md |

Note: `record-rule` rewrites this table from the globs it is given and will DROP
unrelated rows, so re-check the diff on `.ai/rules/index.md` after using it.
