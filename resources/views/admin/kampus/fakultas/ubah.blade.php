@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Ubah Fakultas - {{ $kampus->nama_kampus }}</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.index') }}">Kampus</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.fakultas.index', $kampus) }}">Fakultas</a></div>
        <div class="breadcrumb-item">Ubah</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>Form Ubah Fakultas</h4>
            </div>
            <form action="{{ route('admin.kampus.fakultas.perbarui', [$kampus, $fakultas]) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="card-body">
                <div class="form-group">
                  <label for="nama">Nama Fakultas <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $fakultas->nama) }}" required>
                  @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="card-footer text-right">
                <a href="{{ route('admin.kampus.fakultas.index', $kampus) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection