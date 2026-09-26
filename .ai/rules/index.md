# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| app/Http/Controllers/Admin/** | .ai/rules/admin.md |
| app/Http/Controllers/Admin/PenggunaController.php | .ai/rules/admin.md |
| app/Http/Middleware/** | .ai/rules/admin.md |
| app/Models/{User,Menu}.php | .ai/rules/admin.md |
| app/Models/Applicant.php, app/Http/Controllers/Admin/PendaftarController.php | .ai/rules/applicant.md |
| app/Http/Controllers/Auth/** | .ai/rules/auth.md |
| app/Http/Requests/User/** | .ai/rules/user.md |
| config/services.php | .ai/rules/config.md |
| tests/Feature/** | .ai/rules/feature.md |
| routes/web.php | .ai/rules/admin.md |
| .env | .ai/rules/general.md |
| app/Notifications/** | .ai/rules/notifications.md |
| resources/views/user/pendaftaran/** | .ai/rules/pendaftaran.md |
| tests/** | .ai/rules/tests.md |
| resources/views/admin/** | .ai/rules/views-admin.md |
| resources/views/** | .ai/rules/views.md |
| resources/views/layouts/** | .ai/rules/admin.md |
| resources/views/auth/** | .ai/rules/auth.md |

Note: `record-rule` rewrites this table from the globs it is given and will DROP
unrelated rows, so re-check the diff on `.ai/rules/index.md` after using it.
