@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Ubah Kampus</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.index') }}">Kampus</a></div>
        <div class="breadcrumb-item">Ubah</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>Form Ubah Kampus</h4>
            </div>
            <form action="{{ route('admin.kampus.perbarui', $kampus) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="card-body">
                <div class="form-group">
                  <label for="nama_kampus">Nama Kampus <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nama_kampus') is-invalid @enderror" id="nama_kampus" name="nama_kampus" value="{{ old('nama_kampus', $kampus->nama_kampus) }}" required>
                  @error('nama_kampus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="card-footer text-right">
                <a href="{{ route('admin.kampus.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection