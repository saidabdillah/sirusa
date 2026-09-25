@php
  $modalTarget = 'modal-ubah-role-'.$role->id;
  $isTarget = old('modal_target') === $modalTarget;
  $nameVal = $isTarget ? old('name', $role->name) : $role->name;
@endphp
<div class="modal fade" id="modal-ubah-role-{{ $role->id }}" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('admin.role.perbarui', $role) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="modal_target" value="{{ $modalTarget }}">
        <div class="modal-header">
          <h5 class="modal-title">Ubah Role: {{ $role->name }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="name-{{ $role->id }}">Nama Role <span class="text-danger">*</span></label>
            <input type="text" class="form-control {{ $isTarget && $errors->has('name') ? 'is-invalid' : '' }}" id="name-{{ $role->id }}" name="name" value="{{ $nameVal }}">
            @if($isTarget && $errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>