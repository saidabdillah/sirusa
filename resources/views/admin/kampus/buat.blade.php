@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Tambah Kampus</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.index') }}">Kampus</a></div>
        <div class="breadcrumb-item">Tambah</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>Form Tambah Kampus</h4>
            </div>
            <form action="{{ route('admin.kampus.simpan') }}" method="POST">
              @csrf
              <div class="card-body">
                <label>Nama Kampus <span class="text-danger">*</span></label>
                @error('nama_kampus')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                <div id="baris-kampus">
                  @php
                    $oldKampus = old('nama_kampus', ['']);
                  if (! is_array($oldKampus)) {
                      $oldKampus = [$oldKampus];
                  }
                  @endphp
                  @foreach($oldKampus as $index => $value)
                  <div class="form-group row">
                    <div class="col">
                      <input type="text"
                        class="form-control {{ $errors->has('nama_kampus.'.$index) ? 'is-invalid' : '' }}"
                        name="nama_kampus[]" value="{{ $value }}" placeholder="Contoh: Universitas Lambung Mangkurat">
                      @error('nama_kampus.'.$index)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-auto align-self-center">
                      <button type="button" class="btn btn-outline-danger baris-hapus" tabindex="-1" title="Hapus baris">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                  @endforeach
                </div>
                <button type="button" class="btn btn-outline-primary btn-block" id="tambah-baris">
                  <i class="fas fa-plus mr-1"></i> Tambah Baris
                </button>
              </div>
              <div class="card-footer text-right">
                <a href="{{ route('admin.kampus.index') }}" class="btn btn-secondary mr-2">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection

@push('script')
<script>
$(document).ready(function() {
  function updateHapus() {
    $('#baris-kampus .baris-hapus').prop('disabled', $('#baris-kampus .form-group').length <= 1);
  }

  $('#tambah-baris').on('click', function() {
    var $template = $('#baris-kampus .form-group').first().clone();
    $template.find('input').val('').removeClass('is-invalid');
    $template.find('.invalid-feedback').remove();
    $('#baris-kampus').append($template);
    $('#baris-kampus .form-group').last().find('input').focus();
    updateHapus();
  });

  $('#baris-kampus').on('click', '.baris-hapus', function() {
    if ($('#baris-kampus .form-group').length > 1) {
      $(this).closest('.form-group').remove();
      updateHapus();
    }
  });

  updateHapus();
});
</script>
@endpush