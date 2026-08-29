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
            <h4>Daftar</h4>
          </div>

          <div class="card-body">
            <form action="{{ route('register.store') }}" method="POST">
              @csrf

              <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" tabindex="1" autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="row">
                <div class="form-group col-6">
                  <label for="password" class="d-block">Kata Sandi</label>
                  <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" tabindex="2">
                  @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-6">
                  <label for="password_confirmation" class="d-block">Konfirmasi Kata Sandi</label>
                  <input id="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" tabindex="3">
                  @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-group">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" name="agree" class="custom-control-input @error('agree') is-invalid @enderror" id="agree" tabindex="4" {{ old('agree') ? 'checked' : '' }}>
                  <label class="custom-control-label" for="agree">Saya menyetujui syarat dan ketentuan</label>
                  @error('agree')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="5">
                  Daftar
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