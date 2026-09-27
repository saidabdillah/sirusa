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
            {{-- NIK/NIM hanya relevan untuk mahasiswa. Akun staf tidak punya baris
                 `profil_pengguna`, jadi menampilkan keduanya hanya menghasilkan
                 dua baris "-" yang membingungkan. --}}
            <dl class="row mb-0">
              @if (auth()->user()->isMahasiswa())
              <dt class="col-sm-4">NIK</dt>
              <dd class="col-sm-8">{{ auth()->user()->profile?->nik ?? '-' }}</dd>
              <dt class="col-sm-4">NIM</dt>
              <dd class="col-sm-8">{{ auth()->user()->profile?->nim ?? '-' }}</dd>
              @endif
              <dt class="col-sm-4">Username</dt>
              <dd class="col-sm-8">{{ auth()->user()->username }}</dd>
              <dt class="col-sm-4">Email</dt>
              <dd class="col-sm-8">{{ auth()->user()->email ?? '-' }}</dd>
              <dt class="col-sm-4">Peran</dt>
              <dd class="col-sm-8">{{ \App\Models\User::ROLE_LABELS[auth()->user()->getRoleNames()->first()] ?? 'User' }}</dd>
            </dl>
            <div class="alert alert-info mt-3 mb-0">
              <i class="fas fa-info-circle mr-1"></i>
              Login menggunakan <strong>{{ auth()->user()->loginCredentialLabel() }}</strong> Anda. Jika lupa, hubungi admin untuk mereset kata sandi.
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h4>Ganti Kata Sandi</h4>
          </div>
          <form action="{{ route('settings.update') }}" method="POST" id="formSettings" data-ajax-form>
            @csrf
            @method('PUT')
            <div class="card-body">
              <div class="form-group">
                <label for="current_password">Kata Sandi Saat Ini <span class="text-danger">*</span></label>
                <input id="current_password" type="password" name="current_password" autocomplete="current-password"
                  class="form-control @error('current_password') is-invalid @enderror">
                @error('current_password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group">
                <label for="password">Kata Sandi Baru <span class="text-danger">*</span></label>
                <input id="password" type="password" name="password" autocomplete="new-password"
                  class="form-control @error('password') is-invalid @enderror">
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Minimal 8 karakter.</small>
              </div>
              <div class="form-group">
                <label for="password_confirmation">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                  class="form-control @error('password_confirmation') is-invalid @enderror">
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