---
paths:
  - 'resources/views/admin/verifikasi/**'
---

# Verifikasi

## No server-rendered empty-state row inside DataTables tbody
DataTables 1.10 maps `<td>` to columns by position and never honours `colspan`, so a single `@empty` row makes `_fnGetRowElements` return one cell and the initialiser throw on `_DT_CellIndex`. Use a bare `@foreach` (no `@empty`) and put the copy in the `language.emptyTable` option. `verifikasi/index.blade.php` was the only page that broke this.
