@php
  $modalTarget = 'modal-ubah-menu-'.$menu->id;
  $isTarget = old('modal_target') === $modalTarget;
  $labelVal = $isTarget ? old('label', $menu->label) : $menu->label;
  $parentVal = $isTarget ? old('parent_id', $menu->parent_id) : $menu->parent_id;
  $iconVal = $isTarget ? old('icon', $menu->icon) : $menu->icon;
  $sectionVal = $isTarget ? old('section', $menu->section) : $menu->section;
  $urutanVal = $isTarget ? old('urutan', $menu->urutan) : $menu->urutan;
  // `route`/`scope` OPSIONAL di semua menu -- boleh dikosongkan; jika diisi,
  // `route` bebas teks (input TANPA datalist), tidak harus sudah terdaftar.
  $routeVal = $isTarget ? old('route', $menu->route) : $menu->route;
  $scopeVal = $isTarget ? old('scope', $menu->scope) : $menu->scope;
  // `aktif`/`wajib` sengaja TIDAK punya input di modal ini: `update()` tidak
  // menyentuh kedua kolom itu sama sekali, jadi status lama menu tetap utuh.
@endphp
<div class="modal fade" id="modal-ubah-menu-{{ $menu->id }}" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form action="{{ route('admin.menukelola.perbarui', $menu) }}" method="POST" data-ajax-form>
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
              {{-- Select2 `tags`: opsi yang ada memakai id, mengetik nama baru
                   lalu Enter membuat menu induk top-level. Opsi sendiri ($menu)
                   sengaja tidak ikut ditawarkan, jadi menu tidak bisa jadi
                   induk dirinya sendiri -- validasi di `UpdateMenuRequest`
                   tetap menjaganya untuk jalur request yang tidak lewat form. --}}
              <select class="form-control select2-baru {{ $isTarget && $errors->has('parent_id') ? 'is-invalid' : '' }}" id="parent_id-{{ $menu->id }}" name="parent_id" data-placeholder="— Menu Utama (tanpa induk) —">
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
              {{-- Select2 `tags`: section baru boleh diketik langsung, dan
                   `Menu::sections()` otomatis memasukkannya ke sidebar. --}}
              <select class="form-control select2-baru {{ $isTarget && $errors->has('section') ? 'is-invalid' : '' }}" id="section-{{ $menu->id }}" name="section" data-placeholder="— Pilih —">
                @foreach($sections as $section)
                <option value="{{ $section }}" {{ $sectionVal === $section ? 'selected' : '' }}>{{ $section }}</option>
                @endforeach
              </select>
              @if($isTarget && $errors->has('section'))<div class="invalid-feedback">{{ $errors->first('section') }}</div>@endif
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="route-{{ $menu->id }}">Nama Route</label>
              <input type="text" class="form-control {{ $isTarget && $errors->has('route') ? 'is-invalid' : '' }}" id="route-{{ $menu->id }}" name="route" value="{{ $routeVal }}" placeholder="contoh: admin.beasiswa.index">
              @if($isTarget && $errors->has('route'))<div class="invalid-feedback">{{ $errors->first('route') }}</div>@endif
            </div>
            <div class="form-group col-md-6">
              <label for="scope-{{ $menu->id }}">Scope</label>
              <input type="text" class="form-control {{ $isTarget && $errors->has('scope') ? 'is-invalid' : '' }}" id="scope-{{ $menu->id }}" name="scope" value="{{ $scopeVal }}" placeholder="contoh: admin.beasiswa">
              @if($isTarget && $errors->has('scope'))<div class="invalid-feedback">{{ $errors->first('scope') }}</div>@endif
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="urutan-{{ $menu->id }}">Urutan <span class="text-danger">*</span></label>
              <input type="number" min="0" class="form-control {{ $isTarget && $errors->has('urutan') ? 'is-invalid' : '' }}" id="urutan-{{ $menu->id }}" name="urutan" value="{{ $urutanVal }}">
              @if($isTarget && $errors->has('urutan'))<div class="invalid-feedback">{{ $errors->first('urutan') }}</div>@endif
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