@extends('layouts.app')

@section('content')
<section class="section d-flex align-items-center justify-content-center" style="min-height: 100vh;">
  <div class="container">
    <div class="row">
      <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">
        <div class="login-brand">
          <img src="{{ asset('assets/img/stisla-fill.svg') }}" alt="logo" width="100" class="shadow-light rounded-circle">
        </div>

        <div class="card card-primary">
          <div class="card-header">
            <h4>Buat Kata Sandi Baru</h4>
          </div>

          <div class="card-body">
            <p class="text-muted">
              Kode OTP valid. Silakan masukkan kata sandi baru Anda.
            </p>

            @if (session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('password.reset.store') }}">
              @csrf

              <div class="form-group">
                <label for="password">Kata Sandi Baru</label>
                <input id="password" type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password"
                       tabindex="1"
                       autofocus
                       autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group mt-3">
                <label for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                <input id="password_confirmation" type="password"
                       class="form-control @error('password_confirmation') is-invalid @enderror"
                       name="password_confirmation"
                       tabindex="2"
                       autocomplete="new-password">
                @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="3">
                  Simpan Kata Sandi Baru
                </button>
              </div>
            </form>
          </div>
        </div>
        <div class="simple-footer">
          Hak Cipta &copy; SIRUSA 2026
        </div>
      </div>
    </div>
  </div>
</section>
@endsection