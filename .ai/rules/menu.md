---
paths:
  - 'resources/views/admin/menu/**'
---

# Menu

## Menu modals have exactly five fields
Kelola Menu add/edit modals expose ONLY label, parent_id, icon, section, urutan. route/scope/aktif/wajib inputs are removed by user decision and must not come back; no <small> hint under inputs. parent_id + section are select2 tags (dropdownParent = closest modal) so a new parent/section is typed inline; server resolves it in MenuController::resolveParentId. aktif/wajib are forced true/false on create and never updated.
