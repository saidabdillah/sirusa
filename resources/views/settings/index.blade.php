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
            <h4>Ganti Email</h4>
          </div>
          <form action="{{ route('settings.email.otp.send') }}" method="POST">
            @csrf
            <div class="card-body">
              <div class="form-group">
                <label>Email Baru</label>
                <input type="email" name="email" value="{{ old('email') }}"
                  class="form-control @error('email') is-invalid @enderror">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="text-muted">Kode verifikasi akan dikirim ke email baru Anda.</small>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-primary">Kirim Kode Verifikasi</button>
            </div>
          </form>
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
                <label>Kata Sandi Baru</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group">
                <label>Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror">
                @error('password_confirmation')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-primary">Simpan Kata Sandi</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
