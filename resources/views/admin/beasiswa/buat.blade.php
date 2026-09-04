@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Tambah Beasiswa</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.beasiswa.index') }}">Beasiswa</a></div>
      <div class="breadcrumb-item">Tambah</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <form action="{{ route('admin.beasiswa.simpan') }}" method="POST">
          @csrf

          {{-- Section: Informasi Utama --}}
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Informasi Utama</h4>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label for="nama">Nama Beasiswa <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama"
                  value="{{ old('nama') }}" placeholder="Contoh: Beasiswa Pendidikan Kab. Balangan">
                @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-row">
                <div class="form-group col-md-8">
                  <label for="kampus_id">Kampus Tujuan <span class="text-danger">*</span></label>
                  <select class="form-control @error('kampus_id') is-invalid @enderror" id="kampus_id" name="kampus_id">
                    <option value="">Pilih Kampus</option>
                    @foreach($kampusList as $kampus)
                      <option value="{{ $kampus->id }}" {{ old('kampus_id', $kampusList->first()?->id ?? '') == $kampus->id ? 'selected' : '' }}>{{ $kampus->nama_kampus }}</option>
                    @endforeach
                  </select>
                  @error('kampus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <small class="text-muted">Pilih kampus tujuan beasiswa dari daftar yang dikelola di menu Kampus</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="tingkat_gelar">Tingkat Gelar <span class="text-danger">*</span></label>
                  <select class="form-control @error('tingkat_gelar') is-invalid @enderror" id="tingkat_gelar"
                    name="tingkat_gelar">
                    <option value="">Pilih Gelar</option>
                    <option value="S1" {{ old('tingkat_gelar')==='S1' ? 'selected' : '' }}>S1</option>
                    <option value="S2" {{ old('tingkat_gelar')==='S2' ? 'selected' : '' }}>S2</option>
                    <option value="S3" {{ old('tingkat_gelar')==='S3' ? 'selected' : '' }}>S3</option>
                  </select>
                  @error('tingkat_gelar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-3">
                  <label for="kuota">Kuota <span class="text-danger">*</span></label>
                  <div class="input-group @error('kuota') is-invalid @enderror">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-users"></i></span>
                    </div>
                    <input type="number" class="form-control @error('kuota') is-invalid @enderror" id="kuota"
                      name="kuota" value="{{ old('kuota') }}" min="0" placeholder="0">
                  </div>
                  @error('kuota')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="cakupan">Tunjangan <span class="text-danger">*</span></label>
                  <select class="form-control @error('cakupan') is-invalid @enderror" id="cakupan" name="cakupan">
                    <option value="">Pilih Tunjangan</option>
                    <option value="penuh" {{ old('cakupan')==='penuh' ? 'selected' : '' }}>Penuh</option>
                    <option value="sebagian" {{ old('cakupan')==='sebagian' ? 'selected' : '' }}>Sebagian</option>
                  </select>
                  @error('cakupan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="batas_waktu">Batas Waktu <span class="text-danger">*</span></label>
                  <div class="input-group @error('batas_waktu') is-invalid @enderror">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                    </div>
                    <input type="text"
                      class="form-control flatpickr bg-white @error('batas_waktu') is-invalid @enderror"
                      id="batas_waktu" name="batas_waktu" value="{{ old('batas_waktu') }}" placeholder="Pilih tanggal">
                  </div>
                  @error('batas_waktu')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="ipk_minimal">IPK Minimal <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" min="0" max="4" class="form-control @error('ipk_minimal') is-invalid @enderror"
                    id="ipk_minimal" name="ipk_minimal" value="{{ old('ipk_minimal') }}" placeholder="cth: 3.00">
                  @error('ipk_minimal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="semester_minimal">Semester Minimal <span class="text-danger">*</span></label>
                  <input type="number" min="1" max="14" class="form-control @error('semester_minimal') is-invalid @enderror"
                    id="semester_minimal" name="semester_minimal" value="{{ old('semester_minimal') }}" placeholder="cth: 3">
                  @error('semester_minimal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="status">Status <span class="text-danger">*</span></label>
                  <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                    <option value="aktif" {{ old('status', 'aktif' )==='aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="non-aktif" {{ old('status')==='non-aktif' ? 'selected' : '' }}>Non-aktif</option>
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
                <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi"
                  rows="4" placeholder="Jelaskan tentang beasiswa ini...">{{ old('deskripsi') }}</textarea>
                @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group mb-0">
                <label for="persyaratan">Persyaratan <span class="text-danger">*</span></label>
                <textarea class="form-control @error('persyaratan') is-invalid @enderror" id="persyaratan"
                  name="persyaratan" rows="4"
                  placeholder="Tulis persyaratan beasiswa...">{{ old('persyaratan') }}</textarea>
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
                <i class="fas fa-info-circle mr-1"></i> Centang program studi yang berhak mengikuti beasiswa ini
                (minimal 1). Daftar mengikuti kampus yang dipilih.
              </div>

              <div id="prodi-tree">
                @forelse($kampusList as $kampus)
                  <div class="kampus-tree d-none" data-kampus-id="{{ $kampus->id }}">
                    @foreach($kampus->fakultas as $fakultas)
                      <div class="mb-3">
                        <strong class="d-block mb-2"><i class="fas fa-university mr-1"></i>{{ $fakultas->nama }}</strong>
                        <div class="row">
                          @foreach($fakultas->prodi as $prodi)
                            <div class="col-md-4 mb-1">
                              <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input prodi-check" id="prodi-{{ $prodi->id }}"
                                  name="prodi_ids[]" value="{{ $prodi->id }}" @checked(in_array($prodi->id, old('prodi_ids', [])))>
                                <label class="custom-control-label" for="prodi-{{ $prodi->id }}">{{ $prodi->nama }}</label>
                              </div>
                            </div>
                          @endforeach
                        </div>
                      </div>
                    @endforeach
                    @if($kampus->fakultas->isEmpty())
                      <p class="text-muted mb-0">Belum ada fakultas pada kampus ini. Tambahkan melalui menu
                        <a href="{{ route('admin.kampus.index') }}">Kampus</a>.</p>
                    @endif
                  </div>
                @empty
                  <p class="text-muted mb-0">Belum ada kampus terdaftar. Tambahkan melalui menu
                    <a href="{{ route('admin.kampus.index') }}">Kampus</a>.</p>
                @endforelse
              </div>

              @error('prodi_ids')
                <div class="alert alert-danger mt-2 mb-0">{{ $message }}</div>
              @enderror
              <small class="text-muted mt-1 d-block" id="prodi-hint"></small>
            </div>
          </div>

          {{-- Footer Buttons --}}
          <div class="card">
            <div class="card-footer text-right">
              <a href="{{ route('admin.beasiswa.index') }}" class="btn btn-outline-secondary mr-2">
                <i class="fas fa-arrow-left mr-1"></i> Batal
              </a>
              <button type="submit" class="btn btn-primary" id="btn-simpan">
                <i class="fas fa-save mr-1"></i> Simpan Beasiswa
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
<script>
  $(document).ready(function() {
    function showKampusTree() {
      var selected = $('#kampus_id').val();
      $('#prodi-tree .kampus-tree').each(function() {
        $(this).toggleClass('d-none', String($(this).data('kampus-id')) !== String(selected));
      });
      updateProdiHint();
    }

    function updateProdiHint() {
      var count = $('#prodi-tree .kampus-tree:not(.d-none) .prodi-check:checked').length;
      $('#prodi-hint').text(count === 0 ? 'Belum ada program studi yang dipilih. Minimal pilih 1.' : count + ' program studi dipilih.');
    }

    $('#kampus_id').on('change', showKampusTree);
    $('#prodi-tree').on('change', '.prodi-check', updateProdiHint);

    showKampusTree();

    flatpickr('.flatpickr', {
      dateFormat: 'Y-m-d'
    });
  });
</script>
@endpush