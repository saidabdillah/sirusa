@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Edit Beasiswa</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.beasiswa.index') }}">Beasiswa</a></div>
        <div class="breadcrumb-item">Edit</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-12">
          <form action="{{ route('admin.beasiswa.perbarui', $scholarship) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Section: Informasi Utama --}}
            <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Informasi Utama</h4>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="nama">Nama Beasiswa <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $scholarship->nama) }}" required>
                  @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                  <div class="form-group col-md-8">
                    <label for="kampus">Kampus Tujuan <span class="text-danger">*</span></label>
                    <select class="form-control select2-search @error('kampus') is-invalid @enderror" id="kampus" name="kampus" data-url="{{ route('api.kampus.search') }}" required>
                      @if($scholarship->kampus)
                        <option value="{{ $scholarship->kampus }}" selected>{{ $scholarship->kampus }}</option>
                      @endif
                    </select>
                    @error('kampus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Ketik nama kampus untuk mencari</small>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="tingkat_gelar">Tingkat Gelar <span class="text-danger">*</span></label>
                    <select class="form-control @error('tingkat_gelar') is-invalid @enderror" id="tingkat_gelar" name="tingkat_gelar" required>
                      <option value="S1" {{ old('tingkat_gelar', $scholarship->tingkat_gelar) === 'S1' ? 'selected' : '' }}>S1</option>
                      <option value="S2" {{ old('tingkat_gelar', $scholarship->tingkat_gelar) === 'S2' ? 'selected' : '' }}>S2</option>
                      <option value="S3" {{ old('tingkat_gelar', $scholarship->tingkat_gelar) === 'S3' ? 'selected' : '' }}>S3</option>
                    </select>
                    @error('tingkat_gelar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-md-3">
                    <label for="kuota">Kuota <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-users"></i></span>
                      </div>
                      <input type="number" class="form-control @error('kuota') is-invalid @enderror" id="kuota" name="kuota" value="{{ old('kuota', $scholarship->kuota) }}" min="0" required>
                    </div>
                    @error('kuota')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-3">
                    <label for="cakupan">Tunjangan <span class="text-danger">*</span></label>
                    <select class="form-control @error('cakupan') is-invalid @enderror" id="cakupan" name="cakupan" required>
                      <option value="penuh" {{ old('cakupan', $scholarship->cakupan) === 'penuh' ? 'selected' : '' }}>Penuh</option>
                      <option value="sebagian" {{ old('cakupan', $scholarship->cakupan) === 'sebagian' ? 'selected' : '' }}>Sebagian</option>
                    </select>
                    @error('cakupan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-3">
                    <label for="batas_waktu">Batas Waktu <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                      </div>
                      <input type="text" class="form-control flatpickr bg-white @error('batas_waktu') is-invalid @enderror" id="batas_waktu" name="batas_waktu" value="{{ old('batas_waktu', $scholarship->batas_waktu?->format('Y-m-d')) }}" placeholder="Pilih tanggal" required>
                    </div>
                    @error('batas_waktu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-3">
                    <label for="status">Status <span class="text-danger">*</span></label>
                    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                      <option value="aktif" {{ old('status', $scholarship->status) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                      <option value="non-aktif" {{ old('status', $scholarship->status) === 'non-aktif' ? 'selected' : '' }}>Non-aktif</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>
            </div>

            {{-- Section: Deskripsi & Persyaratan --}}
            <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Deskripsi & Persyaratan</h4>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="deskripsi">Deskripsi <span class="text-danger">*</span></label>
                  <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi" rows="4" required>{{ old('deskripsi', $scholarship->deskripsi) }}</textarea>
                  @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group mb-0">
                  <label for="persyaratan">Persyaratan</label>
                  <textarea class="form-control @error('persyaratan') is-invalid @enderror" id="persyaratan" name="persyaratan" rows="4">{{ old('persyaratan', $scholarship->persyaratan) }}</textarea>
                  @error('persyaratan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <small class="text-muted">Pisahkan setiap persyaratan dengan enter</small>
                </div>
              </div>
            </div>

            {{-- Section: Fakultas & Program Studi --}}
            <div class="card mb-4">
              <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-graduation-cap mr-2"></i>Fakultas & Program Studi</h4>
              </div>
              <div class="card-body">
                <div class="alert alert-info mb-3">
                  <i class="fas fa-info-circle mr-1"></i> Tentukan fakultas dan program studi yang tersedia untuk beasiswa ini. Pendaftar akan memilih dari daftar yang Anda tentukan.
                </div>

                <div id="fakultas-container">
                  @foreach($scholarship->fakultas as $fi => $fakultas)
                    <div class="fakultas-entry mb-3" data-index="{{ $fi }}">
                      <div class="card border-left-primary">
                        <div class="card-header py-2">
                          <div class="d-flex align-items-center w-100">
                            <div class="d-flex align-items-center">
                              <span class="badge badge-primary rounded-circle mr-2" style="width: 28px; height: 28px; font-size: 14px;">{{ $fi + 1 }}</span>
                              <strong>Fakultas #{{ $fi + 1 }}</strong>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm btn-remove-fakultas ml-auto" title="Hapus Fakultas">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </div>
                        <div class="card-body">
                          <div class="form-group">
                            <label>Nama Fakultas <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fakultas[{{ $fi }}][nama]" value="{{ $fakultas->nama }}" required placeholder="Contoh: Fakultas Teknik">
                          </div>
                          <label>Program Studi <span class="text-danger">*</span></label>
                          <div class="prodi-list">
                            @foreach($fakultas->prodi as $pi => $prodi)
                              <div class="input-group mb-2 prodi-entry">
                                <div class="input-group-prepend">
                                  <span class="input-group-text">{{ $pi + 1 }}.</span>
                                </div>
                                <input type="text" class="form-control" name="fakultas[{{ $fi }}][prodi][{{ $pi }}][nama]" value="{{ $prodi->nama }}" required placeholder="Nama Program Studi">
                                <div class="input-group-append">
                                  <button type="button" class="btn btn-outline-danger btn-remove-prodi" title="Hapus Prodi"><i class="fas fa-times"></i></button>
                                </div>
                              </div>
                            @endforeach
                          </div>
                          <button type="button" class="btn btn-dashed btn-sm btn-add-prodi mt-2">
                            <i class="fas fa-plus mr-1"></i> Tambah Program Studi
                          </button>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>

                <button type="button" class="btn btn-dashed btn-primary btn-block mt-2" id="btn-add-fakultas">
                  <i class="fas fa-plus mr-1"></i> Tambah Fakultas
                </button>
              </div>
            </div>

            {{-- Footer Buttons --}}
            <div class="card">
              <div class="card-footer text-right">
                <a href="{{ route('admin.beasiswa.index') }}" class="btn btn-outline-secondary mr-2">
                  <i class="fas fa-arrow-left mr-1"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save mr-1"></i> Perbarui Beasiswa
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
</section>
@endsection

@push('script')
<style>
  .btn-dashed {
    border: 2px dashed #ccc;
    background: transparent;
    color: #666;
    transition: all 0.2s;
  }
  .btn-dashed:hover {
    border-color: #4e73df;
    color: #4e73df;
    background: rgba(78, 115, 223, 0.05);
  }
  .btn-dashed.btn-primary {
    border-color: #4e73df;
    color: #4e73df;
  }
  .btn-dashed.btn-primary:hover {
    background: #4e73df;
    color: #fff;
  }
  .border-left-primary {
    border-left: 4px solid #4e73df !important;
  }
  .fakultas-entry .card-header .btn {
    line-height: 1;
  }
</style>

<script>
$(document).ready(function() {
  $('.select2-search').each(function() {
    var url = $(this).data('url');
    $(this).select2({
      ajax: {
        url: url,
        dataType: 'json',
        delay: 300,
        data: function(params) {
          return { q: params.term };
        },
        processResults: function(data) {
          return {
            results: data.map(function(item) {
              return { id: item.id, text: item.text };
            })
          };
        }
      },
      placeholder: 'Cari kampus...',
      allowClear: true
    });
  });

  var fakultasIndex = {{ count($scholarship->fakultas) }};

  function updateFakultasNumbers() {
    $('#fakultas-container .fakultas-entry').each(function(i) {
      $(this).attr('data-index', i);
      $(this).find('.badge').text(i + 1);
      $(this).find('strong').text('Fakultas #' + (i + 1));
    });
  }

  function updateProdiNumbers($fakultasEntry) {
    $fakultasEntry.find('.prodi-entry').each(function(i) {
      var fIdx = $(this).closest('.fakultas-entry').data('index');
      $(this).find('input').attr('name', 'fakultas[' + fIdx + '][prodi][' + i + '][nama]');
      $(this).find('.input-group-text').text((i + 1) + '.');
    });
  }

  $('#btn-add-fakultas').on('click', function() {
    var idx = fakultasIndex++;
    var num = $('#fakultas-container .fakultas-entry').length + 1;
    var html = '<div class="fakultas-entry mb-3" data-index="' + idx + '">' +
      '<div class="card border-left-primary">' +
      '<div class="card-header py-2">' +
      '<div class="d-flex align-items-center justify-content-between">' +
      '<div class="d-flex align-items-center">' +
      '<span class="badge badge-primary rounded-circle mr-2" style="width: 28px; height: 28px; font-size: 14px;">' + num + '</span>' +
      '<strong>Fakultas #' + num + '</strong>' +
      '</div>' +
      '<button type="button" class="btn btn-danger btn-sm btn-remove-fakultas" title="Hapus Fakultas"><i class="fas fa-trash"></i></button>' +
      '</div></div>' +
      '<div class="card-body">' +
      '<div class="form-group">' +
      '<label>Nama Fakultas <span class="text-danger">*</span></label>' +
      '<input type="text" class="form-control" name="fakultas[' + idx + '][nama]" required placeholder="Contoh: Fakultas Teknik"></div>' +
      '<label>Program Studi <span class="text-danger">*</span></label>' +
      '<div class="prodi-list">' +
      '<div class="input-group mb-2 prodi-entry">' +
      '<div class="input-group-prepend"><span class="input-group-text">1.</span></div>' +
      '<input type="text" class="form-control" name="fakultas[' + idx + '][prodi][0][nama]" required placeholder="Nama Program Studi">' +
      '<div class="input-group-append"><button type="button" class="btn btn-outline-danger btn-remove-prodi" title="Hapus Prodi"><i class="fas fa-times"></i></button></div>' +
      '</div></div>' +
      '<button type="button" class="btn btn-dashed btn-sm btn-add-prodi mt-2"><i class="fas fa-plus mr-1"></i> Tambah Program Studi</button>' +
      '</div></div></div>';
    $('#fakultas-container').append(html);
  });

  $(document).on('click', '.btn-remove-fakultas', function() {
    $(this).closest('.fakultas-entry').remove();
    updateFakultasNumbers();
  });

  $(document).on('click', '.btn-add-prodi', function() {
    var $entry = $(this).closest('.fakultas-entry');
    var fIdx = $entry.data('index');
    var prodiCount = $entry.find('.prodi-entry').length;
    var html = '<div class="input-group mb-2 prodi-entry">' +
      '<div class="input-group-prepend"><span class="input-group-text">' + (prodiCount + 1) + '.</span></div>' +
      '<input type="text" class="form-control" name="fakultas[' + fIdx + '][prodi][' + prodiCount + '][nama]" required placeholder="Nama Program Studi">' +
      '<div class="input-group-append"><button type="button" class="btn btn-outline-danger btn-remove-prodi" title="Hapus Prodi"><i class="fas fa-times"></i></button></div></div>';
    $entry.find('.prodi-list').append(html);
  });

  $(document).on('click', '.btn-remove-prodi', function() {
    var $list = $(this).closest('.prodi-list');
    $(this).closest('.prodi-entry').remove();
    updateProdiNumbers($list.closest('.fakultas-entry'));
  });

  flatpickr('.flatpickr', {
    dateFormat: 'Y-m-d'
  });
});
</script>
@endpush
