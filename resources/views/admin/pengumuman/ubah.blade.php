@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Ubah Jadwal Pengumuman</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.pengumuman.index') }}">Pengumuman</a></div>
      <div class="breadcrumb-item">Ubah</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <form action="{{ route('admin.pengumuman.perbarui', $scholarship) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0"><i class="fas fa-bullhorn mr-2"></i>Informasi Beasiswa</h4>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-3">Nama Beasiswa</dt>
                <dd class="col-sm-9">{{ $scholarship->nama }}</dd>
                <dt class="col-sm-3">Kampus</dt>
                <dd class="col-sm-9">{{ $scholarship->kampus }}</dd>
                <dt class="col-sm-3">Status Saat Ini</dt>
                <dd class="col-sm-9">
                  @if($scholarship->isPengumumanAktif())
                    <span class="badge badge-success">Pengumuman sedang aktif</span>
                  @elseif($scholarship->hasPengumuman())
                    <span class="badge badge-secondary">Tidak aktif (di luar periode)</span>
                  @else
                    <span class="badge badge-secondary">Belum ada penerima</span>
                  @endif
                </dd>
              </dl>
            </div>
          </div>

          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Jadwal Pengumuman</h4>
            </div>
            <div class="card-body">
              <div class="alert alert-light border mb-3">
                <i class="fas fa-info-circle mr-1"></i> Halaman pengumuman akan tampil secara otomatis pada rentang
                tanggal ini (saat ada minimal 1 penerima). Kosongkan kedua tanggal untuk menonaktifkan pengumuman.
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="tanggal_pengumuman">Tanggal Mulai Pengumuman</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                    </div>
                    <input type="text"
                      class="form-control flatpickr bg-white @error('tanggal_pengumuman') is-invalid @enderror"
                      id="tanggal_pengumuman" name="tanggal_pengumuman"
                      value="{{ old('tanggal_pengumuman', $scholarship->tanggal_pengumuman?->format('Y-m-d')) }}"
                      placeholder="Pilih tanggal mulai">
                  </div>
                  @error('tanggal_pengumuman')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="tanggal_pengumuman_selesai">Tanggal Selesai Pengumuman</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                    </div>
                    <input type="text"
                      class="form-control flatpickr bg-white @error('tanggal_pengumuman_selesai') is-invalid @enderror"
                      id="tanggal_pengumuman_selesai" name="tanggal_pengumuman_selesai"
                      value="{{ old('tanggal_pengumuman_selesai', $scholarship->tanggal_pengumuman_selesai?->format('Y-m-d')) }}"
                      placeholder="Pilih tanggal selesai">
                  </div>
                  @error('tanggal_pengumuman_selesai')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-footer text-right">
              <a href="{{ route('admin.pengumuman.index') }}" class="btn btn-outline-secondary mr-2">
                <i class="fas fa-arrow-left mr-1"></i> Batal
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Simpan Jadwal
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
  flatpickr('.flatpickr', {
    dateFormat: 'Y-m-d'
  });
});
</script>
@endpush