@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Tambah Program Studi - {{ $fakultas->nama }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.index') }}">Kampus</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.fakultas.index', $kampus) }}">{{ $kampus->nama_kampus }}</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.prodi.index', [$kampus, $fakultas]) }}">Program Studi</a></div>
        <div class="breadcrumb-item">Tambah</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>Form Tambah Program Studi</h4>
            </div>
            <form action="{{ route('admin.kampus.prodi.simpan', [$kampus, $fakultas]) }}" method="POST">
              @csrf
              <div class="card-body">
                <label>Nama Program Studi <span class="text-danger">*</span></label>
                @error('nama')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                <div id="baris-prodi">
                  @php
                    $oldProdi = old('nama', ['']);
                  if (! is_array($oldProdi)) {
                      $oldProdi = [$oldProdi];
                  }
                  @endphp
                  @foreach($oldProdi as $index => $value)
                  <div class="form-group row">
                    <div class="col">
                      <input type="text"
                        class="form-control {{ $errors->has('nama.'.$index) ? 'is-invalid' : '' }}"
                        name="nama[]" value="{{ $value }}" placeholder="Contoh: Teknik Informatika">
                      @error('nama.'.$index)<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                <a href="{{ route('admin.kampus.prodi.index', [$kampus, $fakultas]) }}" class="btn btn-secondary mr-2">Batal</a>
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
    $('#baris-prodi .baris-hapus').prop('disabled', $('#baris-prodi .form-group').length <= 1);
  }

  $('#tambah-baris').on('click', function() {
    var $template = $('#baris-prodi .form-group').first().clone();
    $template.find('input').val('').removeClass('is-invalid');
    $template.find('.invalid-feedback').remove();
    $('#baris-prodi').append($template);
    $('#baris-prodi .form-group').last().find('input').focus();
    updateHapus();
  });

  $('#baris-prodi').on('click', '.baris-hapus', function() {
    if ($('#baris-prodi .form-group').length > 1) {
      $(this).closest('.form-group').remove();
      updateHapus();
    }
  });

  updateHapus();
});
</script>
@endpush