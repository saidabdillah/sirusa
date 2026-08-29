@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Tambah Pengguna</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.pengguna.index') }}">Pengguna</a></div>
        <div class="breadcrumb-item">Tambah</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>Form Tambah Pengguna</h4>
            </div>
            <form action="{{ route('admin.pengguna.simpan') }}" method="POST">
              @csrf
              <div class="card-body">
                <div class="form-group">
                  <label for="username">Username <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required>
                  @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                  <label for="email">Email <span class="text-danger">*</span></label>
                  <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                  @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="password">Kata Sandi <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="password_confirmation">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="peran">Peran <span class="text-danger">*</span></label>
                    <select class="form-control @error('peran') is-invalid @enderror" id="peran" name="peran" required>
                      <option value="user" {{ old('peran') === 'user' ? 'selected' : '' }}>User</option>
                      <option value="admin" {{ old('peran') === 'admin' ? 'selected' : '' }}>Admin</option>
                      <option value="super_admin" {{ old('peran') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                    @error('peran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="status">Status <span class="text-danger">*</span></label>
                    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                      <option value="aktif" {{ old('status', 'aktif') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                      <option value="non-aktif" {{ old('status') === 'non-aktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>
              <div class="card-footer text-right">
                <a href="{{ route('admin.pengguna.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection
