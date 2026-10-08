---
paths:
  - 'public/assets/css/**'
---

# Css

## Filter toolbar & DataTables controls are pinned to 38px
The 5 toolbar controls are 38px to align with the 38px buttons: filter selects inside .form-row.align-items-end (#filter, #status, #beasiswa_id) and .dataTables_filter input /.dataTables_length select. These override the global 46px form-control size via id-scoped selectors (specificity), so a plain form select (these ids do not clash) stays 46px. DataTables inputs lack .form-control, so they also get explicit border/radius/bg. Do not bump these to 46px or the filter row's Terapkan/Reset buttons will no longer line up.

## Semua input, select, dan group text setinggi 38px
Height form seragam 38px di seluruh aplikasi. Atur lewat rule global `.form-control`/`.input-group-text`/select di custom.css (`height:auto; min-height & pin = calc(1.5em + 0.75rem + 2px); padding: .375rem .75rem; line-height:1.5`). TIDAK boleh memakai kelas `form-control-sm`/`input-group-sm` (memperkecil ukuran) atau CSS tinggi sendiri. Satu-satunya pengecualian: control asli milik DataTables di toolbar tabel (tapak `toolbar-data-tables`).
