@extends('layouts.app')

@section('content')
<section class="section d-flex align-items-center justify-content-center" style="min-height: 100vh;">
  <div class="container">
    <div class="row">
      <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">
        <div class="login-brand">
          <img src="{{ asset('assets/img/stisla-fill.svg') }}" alt="logo" width="100"
            class="shadow-light rounded-circle">
        </div>

        <div class="card card-primary">
          <div class="card-header">
            <h4>Masuk</h4>
          </div>

          <div class="card-body">

            <form action="{{ route('login.store') }}" method="POST">
              @csrf
              <div class="form-group">
                <label for="login">NIK atau Username <span class="text-danger">*</span></label>
                <input id="login" type="text" class="form-control @error('login') is-invalid @enderror" name="login" value="{{ old('login') }}" tabindex="1" autofocus>
                @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <div class="d-block">
                  <label for="password" class="control-label">Kata Sandi <span class="text-danger">*</span></label>
                </div>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" tabindex="2">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" name="remember" class="custom-control-input" tabindex="3" id="remember-me">
                  <label class="custom-control-label" for="remember-me">Ingat Saya</label>
                </div>
              </div>

              <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4" data-loading-text="Sedang masuk...">
                  Masuk
                </button>
              </div>
            </form>

          </div>
        </div>
        <div class="mt-5 text-muted text-center">
          Belum punya akun? Silakan hubungi admin agar didaftarkan.
        </div>
        <div class="simple-footer">
          Hak Cipta &copy; SIRUSA {{ date('Y') }}
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
