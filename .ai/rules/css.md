---
paths:
  - 'public/assets/css/**'
---

# Css

## Filter toolbar & DataTables controls are pinned to 38px
The 5 toolbar controls are 38px to align with the 38px buttons: filter selects inside .form-row.align-items-end (#filter, #status, #beasiswa_id) and .dataTables_filter input /.dataTables_length select. These override the global 46px form-control size via id-scoped selectors (specificity), so a plain form select (these ids do not clash) stays 46px. DataTables inputs lack .form-control, so they also get explicit border/radius/bg. Do not bump these to 46px or the filter row's Terapkan/Reset buttons will no longer line up.

## Select2 single: clear (×) must clear the arrow box
Stisla's `components.css` renders the single-select arrow in a 40px-wide `.select2-selection__clear`-adjacent box (`.select2-selection--arrow b` sits centered ~17–25px from the right, box spans 1–41px) while core Select2 floats `.select2-selection__clear` hard right with NO margin — so × lands at ~12–20px and collides with the arrow glyph. Fix lives in `custom.css` (loaded LAST in the select2 chain `select2.min.css → style.css → components.css → custom.css`): `.select2-container--default .select2-selection--single .select2-selection__rendered { padding-right: 46px; }` — 40px arrow box + 6px gap. The `__rendered` line-height/padding-left come from `components.css` and must not be re-pinned here (they already give the 42px Stisla control height). Do not "fix" the ×/arrow spacing by editing `components.css` (vendor file) or by adding margins to `__clear` (it is `float: right` inside the same padding box). Known cosmetic gap left as-is per user: the select2 box stays 42px while every other input is 38px.

## Semua input, select, dan group text setinggi 38px
Height form seragam 38px di seluruh aplikasi. Atur lewat rule global `.form-control`/`.input-group-text`/select di custom.css (`height:auto; min-height & pin = calc(1.5em + 0.75rem + 2px); padding: .375rem .75rem; line-height:1.5`). TIDAK boleh memakai kelas `form-control-sm`/`input-group-sm` (memperkecil ukuran) atau CSS tinggi sendiri. Satu-satunya pengecualian: control asli milik DataTables di toolbar tabel (tapak `toolbar-data-tables`).
