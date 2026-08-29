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
            <h4>Lupa kata sandi</h4>
          </div>

          <div class="card-body">
            <p class="text-muted">Kami akan mengirimkan kode OTP ke email Anda untuk verifikasi.</p>
            <form method="POST" action="{{ route('password.otp.send') }}">
              @csrf
              <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" tabindex="1" autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                  Kirim Kode OTP
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