@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Pengaturan</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Pengaturan</div>
    </div>
  </div>

  <div class="section-body">
    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif

    @if (session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif

    <div class="row">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h4>Informasi Akun</h4>
          </div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-4">NIK</dt>
              <dd class="col-sm-8">{{ auth()->user()->profile?->nik ?? '-' }}</dd>
              <dt class="col-sm-4">NIM</dt>
              <dd class="col-sm-8">{{ auth()->user()->profile?->nim ?? '-' }}</dd>
              <dt class="col-sm-4">Email</dt>
              <dd class="col-sm-8">{{ auth()->user()->email ?? '-' }}</dd>
              <dt class="col-sm-4">Username</dt>
              <dd class="col-sm-8">{{ auth()->user()->username }}</dd>
            </dl>
            <div class="alert alert-info mt-3 mb-0">
              <i class="fas fa-info-circle mr-1"></i> Login menggunakan NIK Anda. Kata sandi awal akun adalah NIM, lalu dapat Anda ganti di bawah.
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h4>Ganti Kata Sandi</h4>
          </div>
          <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
              <div class="form-group">
                <label>Kata Sandi Baru <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group">
                <label>Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror">
                @error('password_confirmation')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="card-footer text-right">
              <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i> Batal</a>
              <button type="submit" class="btn btn-primary">Simpan Kata Sandi</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection