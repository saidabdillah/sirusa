@php
  $modalTarget = 'modal-tambah-menu';
  $isTarget = old('modal_target') === $modalTarget;
  $labelVal = $isTarget ? old('label') : '';
  $parentVal = $isTarget ? old('parent_id') : '';
  $iconVal = $isTarget ? old('icon', 'fas fa-list') : 'fas fa-list';
  $sectionVal = $isTarget ? old('section') : '';
  $routeVal = $isTarget ? old('route') : '';
  $scopeVal = $isTarget ? old('scope') : '';
  $urutanVal = ($isTarget && old('urutan') !== null) ? old('urutan') : 0;
  $aktifVal = $isTarget ? old('aktif', true) : true;
  $wajibVal = $isTarget ? old('wajib', false) : false;
@endphp
<div class="modal fade" id="modal-tambah-menu" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form action="{{ route('admin.menuKelola.simpan') }}" method="POST">
        @csrf
        <input type="hidden" name="modal_target" value="{{ $modalTarget }}">
        <div class="modal-header">
          <h5 class="modal-title">Form Tambah Menu</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="label-tambah">Label Menu <span class="text-danger">*</span></label>
              <input type="text" class="form-control {{ $isTarget && $errors->has('label') ? 'is-invalid' : '' }}" id="label-tambah" name="label" value="{{ $labelVal }}">
              @if($isTarget && $errors->has('label'))<div class="invalid-feedback">{{ $errors->first('label') }}</div>@endif
            </div>
            <div class="form-group col-md-6">
              <label for="parent_id-tambah">Induk Menu</label>
              <select class="form-control {{ $isTarget && $errors->has('parent_id') ? 'is-invalid' : '' }}" id="parent_id-tambah" name="parent_id">
                <option value="">— Menu Utama (tanpa induk) —</option>
                @foreach($parents as $parent)
                <option value="{{ $parent->id }}" {{ (string) $parentVal === (string) $parent->id ? 'selected' : '' }}>{{ $parent->label }}</option>
                @endforeach
              </select>
              @if($isTarget && $errors->has('parent_id'))<div class="invalid-feedback">{{ $errors->first('parent_id') }}</div>@endif
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="icon-tambah">Icon</label>
              <input type="text" class="form-control" id="icon-tambah" name="icon" value="{{ $iconVal }}" placeholder="fas fa-list">
            </div>
            <div class="form-group col-md-6">
              <label for="section-tambah">Section <span class="text-danger">*</span></label>
              <select class="form-control {{ $isTarget && $errors->has('section') ? 'is-invalid' : '' }}" id="section-tambah" name="section">
                @foreach($sections as $section)
                <option value="{{ $section }}" {{ $sectionVal === $section ? 'selected' : '' }}>{{ $section }}</option>
                @endforeach
              </select>
              @if($isTarget && $errors->has('section'))<div class="invalid-feedback">{{ $errors->first('section') }}</div>@endif
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="route-tambah">Route</label>
              <input type="text" class="form-control {{ $isTarget && $errors->has('route') ? 'is-invalid' : '' }}" id="route-tambah" name="route" value="{{ $routeVal }}" placeholder="contoh: admin.beasiswa.index">
              @if($isTarget && $errors->has('route'))<div class="invalid-feedback">{{ $errors->first('route') }}</div>@endif
              <small class="text-muted">Nama route harus terdaftar. Kosongkan jika ini menu induk berisi sub-menu.</small>
            </div>
            <div class="form-group col-md-6">
              <label for="scope-tambah">Scope</label>
              <input type="text" class="form-control {{ $isTarget && $errors->has('scope') ? 'is-invalid' : '' }}" id="scope-tambah" name="scope" value="{{ $scopeVal }}" placeholder="contoh: admin.beasiswa">
              @if($isTarget && $errors->has('scope'))<div class="invalid-feedback">{{ $errors->first('scope') }}</div>@endif
              <small class="text-muted">Prefix akses route, contoh <code>admin.beasiswa</code> mengganti akses <code>admin.beasiswa.*</code>.</small>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="urutan-tambah">Urutan <span class="text-danger">*</span></label>
              <input type="number" min="0" class="form-control {{ $isTarget && $errors->has('urutan') ? 'is-invalid' : '' }}" id="urutan-tambah" name="urutan" value="{{ $urutanVal }}">
              @if($isTarget && $errors->has('urutan'))<div class="invalid-feedback">{{ $errors->first('urutan') }}</div>@endif
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="aktif-tambah" name="aktif" value="1" {{ $aktifVal ? 'checked' : '' }}>
                <label class="custom-control-label" for="aktif-tambah">Aktif (tampil di sidebar)</label>
              </div>
            </div>
            <div class="form-group col-md-6">
              <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="wajib-tambah" name="wajib" value="1" {{ $wajibVal ? 'checked' : '' }}>
                <label class="custom-control-label" for="wajib-tambah">Wajib (selalu digrant ke semua role)</label>
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