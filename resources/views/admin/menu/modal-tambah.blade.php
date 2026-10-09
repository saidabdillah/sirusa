@php
  $modalTarget = 'modal-tambah-menu';
  $isTarget = old('modal_target') === $modalTarget;
  $labelVal = $isTarget ? old('label') : '';
  $parentVal = $isTarget ? old('parent_id') : '';
  $iconVal = $isTarget ? old('icon', 'fas fa-list') : 'fas fa-list';
  $sectionVal = $isTarget ? old('section') : '';
  // Default sengaja kosong, bukan 0. `urutan` wajib diisi di server, dan
  // prefilling "0" membuat admin menekan Simpan tanpa pernah memilih posisi
  // menu, padahal 0 berarti anak pertama -- keputusan yang tidak pernah
  // dibuat. Setelah validasi gagal, nilai yang diketik admin dikembalikan lewat
  // old() supaya tidak hilang.
  $urutanVal = $isTarget ? old('urutan', '') : '';
  // `aktif`/`wajib` sengaja TIDAK punya input: kedua kolom tetap ada di tabel
  // tapi sudah tidak diedit lewat form (keputusan pengguna, Okt 2026). `store()`
  // selalu menulis `aktif=true, wajib=false`, dan `update()` tidak menyentuh
  // keduanya sama sekali supaya nilai tersimpan tidak pernah tertimpa.
@endphp
<div class="modal fade" id="modal-tambah-menu" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form action="{{ route('admin.menukelola.simpan') }}" method="POST" data-ajax-form>
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
              {{-- Select2 `tags`: memilih opsi yang ada memakai id menunya,
                   mengetik nama baru lalu Enter membuat menu induk top-level
                   (lihat `MenuController::resolveParentId()`). Opsi kosong tetap
                   wajib ada supaya `allowClear` punya tujuan dan supaya select
                   tidak diam-diam memilih opsi pertama. --}}
              <select class="form-control select2-baru {{ $isTarget && $errors->has('parent_id') ? 'is-invalid' : '' }}" id="parent_id-tambah" name="parent_id" data-placeholder="— Menu Utama (tanpa induk) —">
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
              {{-- Select2 `tags` juga di sini: section baru tidak perlu ditambahkan
                   ke kode. `Menu::sections()` menurunkan daftarnya dari isi tabel
                   `menus`, jadi section yang diketik langsung tampil di sidebar. --}}
              <select class="form-control select2-baru {{ $isTarget && $errors->has('section') ? 'is-invalid' : '' }}" id="section-tambah" name="section" data-placeholder="— Pilih —">
                {{-- Wajib ada opsi kosong: `$sectionVal` selalu string kosong
                     saat modal ini dibuka bersih, dan `<select>` tanpa
                     `<option value="">` akan diam-diam memilih opsi pertama
                     ("Menu Utama") sehingga user mengira itu pilihannya.
                     Modal Ubah TIDAK memakai opsi kosong karena section wajib
                     diisi dan nilainya selalu tersimpan. --}}
                <option value="">— Pilih —</option>
                @foreach($sections as $section)
                <option value="{{ $section }}" {{ $sectionVal === $section ? 'selected' : '' }}>{{ $section }}</option>
                @endforeach
              </select>
              @if($isTarget && $errors->has('section'))<div class="invalid-feedback">{{ $errors->first('section') }}</div>@endif
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="urutan-tambah">Urutan <span class="text-danger">*</span></label>
              <input type="number" min="0" class="form-control {{ $isTarget && $errors->has('urutan') ? 'is-invalid' : '' }}" id="urutan-tambah" name="urutan" value="{{ $urutanVal }}">
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