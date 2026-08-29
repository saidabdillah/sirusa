@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Edit Pengguna</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.pengguna.index') }}">Pengguna</a></div>
        <div class="breadcrumb-item">Edit</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>Ubah Data Pengguna</h4>
            </div>
            <form action="{{ route('admin.pengguna.perbarui', $user) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="card-body">
                <div class="form-group">
                  <label for="username">Username</label>
                  <input type="text" class="form-control" id="username" value="{{ $user->username }}" readonly>
                  <small class="text-muted">Username tidak dapat diubah</small>
                </div>

                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" class="form-control" id="email" value="{{ $user->email }}" readonly>
                  <small class="text-muted">Email tidak dapat diubah</small>
                </div>

                <div class="form-row">
                  @if(auth()->user()->hasRole('super_admin'))
                    <div class="form-group col-md-6">
                      <label for="peran">Peran <span class="text-danger">*</span></label>
                      <select class="form-control @error('peran') is-invalid @enderror" id="peran" name="peran" required>
                        <option value="user" {{ old('peran', $user->getRoleNames()->first()) === 'user' ? 'selected' : '' }}>User</option>
                        <option value="admin" {{ old('peran', $user->getRoleNames()->first()) === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="super_admin" {{ old('peran', $user->getRoleNames()->first()) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                      </select>
                      @error('peran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  @endif
                  <div class="form-group {{ auth()->user()->hasRole('super_admin') ? 'col-md-6' : 'col-md-12' }}">
                    <label for="status">Status <span class="text-danger">*</span></label>
                    <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                      <option value="aktif" {{ old('status', $user->status) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                      <option value="non-aktif" {{ old('status', $user->status) === 'non-aktif' ? 'selected' : '' }}>Nonaktif</option>
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

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h4>Informasi</h4>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <strong>Tanggal Dibuat:</strong><br>
                {{ $user->created_at->format('d M Y H:i') }}
              </div>
              <div class="mb-3">
                <strong>Terakhir Diperbarui:</strong><br>
                {{ $user->updated_at->format('d M Y H:i') }}
              </div>
              <div class="mb-3">
                <strong>Jumlah Pendaftaran:</strong><br>
                {{ $user->applicants->count() }} pendaftaran
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection
