@php
  $modalTarget = 'modal-ubah-menu-'.$menu->id;
  $isTarget = old('modal_target') === $modalTarget;
  $labelVal = $isTarget ? old('label', $menu->label) : $menu->label;
  $parentVal = $isTarget ? old('parent_id', $menu->parent_id) : $menu->parent_id;
  $iconVal = $isTarget ? old('icon', $menu->icon) : $menu->icon;
  $sectionVal = $isTarget ? old('section', $menu->section) : $menu->section;
  $routeVal = $isTarget ? old('route', $menu->route) : $menu->route;
  $scopeVal = $isTarget ? old('scope', $menu->scope) : $menu->scope;
  $urutanVal = $isTarget ? old('urutan', $menu->urutan) : $menu->urutan;
  $aktifVal = $isTarget ? old('aktif', $menu->aktif) : $menu->aktif;
  $wajibVal = $isTarget ? old('wajib', $menu->wajib) : $menu->wajib;
@endphp
<div class="modal fade" id="modal-ubah-menu-{{ $menu->id }}" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form action="{{ route('admin.menuKelola.perbarui', $menu) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="modal_target" value="{{ $modalTarget }}">
        <div class="modal-header">
          <h5 class="modal-title">Ubah Menu: {{ $menu->label }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="label-{{ $menu->id }}">Label Menu <span class="text-danger">*</span></label>
              <input type="text" class="form-control {{ $isTarget && $errors->has('label') ? 'is-invalid' : '' }}" id="label-{{ $menu->id }}" name="label" value="{{ $labelVal }}">
              @if($isTarget && $errors->has('label'))<div class="invalid-feedback">{{ $errors->first('label') }}</div>@endif
            </div>
            <div class="form-group col-md-6">
              <label for="parent_id-{{ $menu->id }}">Induk Menu</label>
              <select class="form-control {{ $isTarget && $errors->has('parent_id') ? 'is-invalid' : '' }}" id="parent_id-{{ $menu->id }}" name="parent_id">
                <option value="">— Menu Utama (tanpa induk) —</option>
                @foreach($parents->where('id', '!=', $menu->id) as $parent)
                <option value="{{ $parent->id }}" {{ (string) $parentVal === (string) $parent->id ? 'selected' : '' }}>{{ $parent->label }}</option>
                @endforeach
              </select>
              @if($isTarget && $errors->has('parent_id'))<div class="invalid-feedback">{{ $errors->first('parent_id') }}</div>@endif
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="icon-{{ $menu->id }}">Icon</label>
              <input type="text" class="form-control" id="icon-{{ $menu->id }}" name="icon" value="{{ $iconVal }}" placeholder="fas fa-list">
            </div>
            <div class="form-group col-md-6">
              <label for="section-{{ $menu->id }}">Section <span class="text-danger">*</span></label>
              <select class="form-control {{ $isTarget && $errors->has('section') ? 'is-invalid' : '' }}" id="section-{{ $menu->id }}" name="section">
                @foreach($sections as $section)
                <option value="{{ $section }}" {{ $sectionVal === $section ? 'selected' : '' }}>{{ $section }}</option>
                @endforeach
              </select>
              @if($isTarget && $errors->has('section'))<div class="invalid-feedback">{{ $errors->first('section') }}</div>@endif
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="route-{{ $menu->id }}">Route</label>
              <input type="text" class="form-control {{ $isTarget && $errors->has('route') ? 'is-invalid' : '' }}" id="route-{{ $menu->id }}" name="route" value="{{ $routeVal }}" placeholder="contoh: admin.beasiswa.index">
              @if($isTarget && $errors->has('route'))<div class="invalid-feedback">{{ $errors->first('route') }}</div>@endif
              <small class="text-muted">Nama route harus terdaftar. Kosongkan jika ini menu induk berisi sub-menu.</small>
            </div>
            <div class="form-group col-md-6">
              <label for="scope-{{ $menu->id }}">Scope</label>
              <input type="text" class="form-control {{ $isTarget && $errors->has('scope') ? 'is-invalid' : '' }}" id="scope-{{ $menu->id }}" name="scope" value="{{ $scopeVal }}" placeholder="contoh: admin.beasiswa">
              @if($isTarget && $errors->has('scope'))<div class="invalid-feedback">{{ $errors->first('scope') }}</div>@endif
              <small class="text-muted">Prefix akses route, contoh <code>admin.beasiswa</code> mengganti akses <code>admin.beasiswa.*</code>.</small>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="urutan-{{ $menu->id }}">Urutan <span class="text-danger">*</span></label>
              <input type="number" min="0" class="form-control {{ $isTarget && $errors->has('urutan') ? 'is-invalid' : '' }}" id="urutan-{{ $menu->id }}" name="urutan" value="{{ $urutanVal }}">
              @if($isTarget && $errors->has('urutan'))<div class="invalid-feedback">{{ $errors->first('urutan') }}</div>@endif
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="aktif-{{ $menu->id }}" name="aktif" value="1" {{ $aktifVal ? 'checked' : '' }}>
                <label class="custom-control-label" for="aktif-{{ $menu->id }}">Aktif (tampil di sidebar)</label>
              </div>
            </div>
            <div class="form-group col-md-6">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="wajib-{{ $menu->id }}" name="wajib" value="1" {{ $wajibVal ? 'checked' : '' }}>
                <label class="custom-control-label" for="wajib-{{ $menu->id }}">Wajib (selalu digrant ke semua role)</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>