---
paths:
  - 'resources/views/admin/menu/**'
---

# Menu

## Akses Menu is per-role, not a roles×menus matrix
`admin.menu.index` (`resources/views/admin/menu/index.blade.php`) renders ONE role at a time chosen with a GET filter (`<select name="role" id="roleFilter">` + Cari/Reset) whose initial value is EMPTY (`-- Pilih Role --`); until a role is picked the page shows a prompt and no checklist (no super_admin default). The checklist is ONE `table-striped` table in a single tbody grouped per section: each section opens with a NON-collapsible `table-active` header row (rows carry `[data-section-body]` only to scope the per-section sync) with an inline "Pilih semua" checkbox, then parent + `table-light` `pl-5` child rows, auto-coupling parent↔child, and locks `wajib`/super_admin module menus (`checked disabled`, no "otomatis" badge). There is NO search box and NO collapse chevron. The form PUTs `role_id` + `menus[]` to `admin.menu.grants` and the controller syncs ONLY that role. Do NOT reintroduce the old all-roles matrix, `grants[role_id][]`, tabs, `tree-toggle`, or `data-tree-parent`; `UpdateMenuAccessRequest` no longer validates `grants`.

## Menu modals expose seven fields; route/scope opsional
Kelola Menu add/edit modals expose **seven** fields: `label`, `parent_id`, `icon`, `section`, `urutan`, **route**, **scope** — route/scope **OPSIONAL** (teks bebas, boleh dikosongkan). Bintang (*) hanya tampil pada Label Menu, Section, dan Urutan. Route boleh berupa teks apa pun (tidak harus sudah terdaftar di `routes/`), dan Scope dipasang pola huruf kecil/titik karena `EnsureMenuAccess` mencocokkan `str_starts_with($routeName, $scope.'.')`. Form tidak memaksa nilai ini melalui HTML `required`; validasi dilakukan server-side melalui Form Request (`StoreMenuRequest`/`UpdateMenuRequest`) — rule `nullable` memperbolehkan field dikosongkan. Field tetap muncul di modal sebagai `<input type="text">` biasa (tanpa `datalist`), dan CSS custom.css menambah border merah `.is-invalid` pada kotak select2 saat terjadi error validasi.

**Mengisi route/scope di form**: admin mengetik nilai langsung di form. Jika kosong, menu tetap tampil (link menuju 404 sampai didaftarkan). Jika diisi, nilai disimpan apa adanya. Untuk menyimpan route/scope agar tetap ada setelah `db:seed`, tambahkan baris `(section, label, parent_id)` yang sama di `MenuSeeder` dengan `route`/`scope` diisi — `updateOrCreate` akan menemukan row lama dan mengisinya kembali. Satu-satunya jalan lain: `php artisan tinker` sekali jalan.

Kelola Menu add/edit modals expose label, parent_id, icon, section, urutan, route, scope — route/scope OPSIONAL (boleh dikosongkan, input teks biasa, TANPA datalist, tanpa validasi Route::has). Bintang (*) hanya Label Menu, Section, dan Urutan.

## Akses Menu: don't reuse `section-header` class on table rows
Never reuse Stisla component class names on table rows. `class="section-header"` on a `<tr>` inside `.section` matches Stisla's `.section .section-header` rule (display:flex, padding:20px, box-shadow, margin-bottom:30px) and breaks the table layout. The Akses Menu section rows must use `menu-section-row` + `data-section-header`; keep Stisla's `section-header`/`section-body` classes only on the page-title bar. Also, Stisla's style.css forces `.table:not(.table-sm):not(.table-md):not(.dataTable) td { height: 60px }` — every plain table row is 60px tall (app-wide norm, do not fight it).
