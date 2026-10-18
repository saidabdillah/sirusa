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
            <h4>Daftar Akun Mahasiswa</h4>
          </div>

          <div class="card-body">
            <form action="{{ route('register.store') }}" method="POST">
              @csrf

              <div class="form-group">
                <label for="nik">NIK (Nomor Induk Kependudukan) <span class="text-danger">*</span></label>
                <input id="nik" type="text" inputmode="numeric" maxlength="16"
                  class="form-control @error('nik') is-invalid @enderror" name="nik" value="{{ old('nik') }}"
                  autofocus>
                @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <label for="kampus_id">Kampus <span class="text-danger">*</span></label>
                <select class="form-control @error('kampus_id') is-invalid @enderror" id="kampus_id"
                  name="kampus_id">
                  <option value="">-- Pilih Kampus --</option>
                  @foreach($kampusList as $kampus)
                  <option value="{{ $kampus->id }}" {{ (string) old('kampus_id') === (string) $kampus->id ?
                    'selected' : '' }}>{{ $kampus->nama_kampus }}</option>
                  @endforeach
                </select>
                @error('kampus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <label for="password">Kata Sandi <span class="text-danger">*</span></label>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                  name="password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Minimal 8 karakter.</small>
              </div>

              <div class="form-group">
                <label for="password_confirmation">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                <input id="password_confirmation" type="password"
                  class="form-control @error('password_confirmation') is-invalid @enderror"
                  name="password_confirmation">
                @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Mendaftarkan...">
                  Daftar
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="mt-5 text-muted text-center">
          Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>.
        </div>
        <div class="simple-footer">
          Hak Cipta &copy; SIRUSA {{ date('Y') }}
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
