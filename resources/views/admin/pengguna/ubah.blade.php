@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Edit Pengguna</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
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
                <div class="form-group" data-field="username">
                  <label for="username">Username</label>
                  <input type="text" class="form-control" id="username" value="{{ $user->username }}" readonly>
                  <small class="text-muted">Username tidak dapat diubah</small>
                </div>

                <div class="form-group">
                  <label for="peran">Peran <span class="text-danger">*</span></label>
                  @php $currentPeran = old('peran', $user->roles->first()?->name ?? ''); @endphp
                  <select class="form-control @error('peran') is-invalid @enderror" id="peran" name="peran">
                    <option value="" {{ $currentPeran === '' ? 'selected' : '' }}>-- Pilih Peran --</option>
                    <option value="super_admin" {{ $currentPeran === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="kesra" {{ $currentPeran === 'kesra' ? 'selected' : '' }}>Kesra</option>
                    <option value="kampus" {{ $currentPeran === 'kampus' ? 'selected' : '' }}>Kampus</option>
                    <option value="capil" {{ $currentPeran === 'capil' ? 'selected' : '' }}>Capil</option>
                    <option value="user" {{ $currentPeran === 'user' ? 'selected' : '' }}>User</option>
                  </select>
                  @error('peran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group d-none" id="akunKampus">
                  <label for="kampus_id">Kampus <span class="text-danger">*</span></label>
                  <select class="form-control @error('kampus_id') is-invalid @enderror" id="kampus_id" name="kampus_id">
                    <option value="">-- Pilih Kampus --</option>
                    @foreach ($kampusList as $kampusId => $kampusNama)
                      <option value="{{ $kampusId }}" {{ (string) old('kampus_id', $user->kampus_id) === (string) $kampusId ? 'selected' : '' }}>{{ $kampusNama }}</option>
                    @endforeach
                  </select>
                  @error('kampus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <small class="text-muted">Admin kampus hanya dapat melihat dan memverifikasi data pendaftar dari kampus yang dipilih.</small>
                </div>

                <div class="form-group">
                  <label for="status">Status <span class="text-danger">*</span></label>
                  <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                    <option value="aktif" {{ old('status', $user->status) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="non-aktif" {{ old('status', $user->status) === 'non-aktif' ? 'selected' : '' }}>Nonaktif</option>
                  </select>
                  @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="card-footer text-right">
                <a href="{{ route('admin.pengguna.index') }}" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i> Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
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
                <strong>Email:</strong><br>
                {{ $user->email ?? '-' }}
              </div>
              <div class="mb-3">
                <strong>NIK:</strong><br>
                {{ $user->profile?->nik ?? '-' }}
              </div>
              <div class="mb-3">
                <strong>Tanggal Dibuat:</strong><br>
                {{ $user->created_at->translatedFormat('d F Y H:i') }}
              </div>
              <div class="mb-3">
                <strong>Terakhir Diperbarui:</strong><br>
                {{ $user->updated_at->translatedFormat('d F Y H:i') }}
              </div>
              <div class="mb-0">
                <strong>Jumlah Pendaftaran:</strong><br>
                {{ $user->applicants->count() }} pendaftaran
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header">
              <h4>Catatan</h4>
            </div>
            <div class="card-body">
              <small class="text-muted d-block mb-2">
                <i class="fas fa-info-circle mr-1"></i> Email, NIK, NIM, dan kata sandi tidak dapat diubah di sini.
              </small>
              <small class="text-muted d-block mb-0">
                <i class="fas fa-key mr-1"></i> Gunakan aksi <em>Reset Password</em> di daftar pengguna untuk mengembalikan kata sandi ke <code>12345678</code>.
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection

@push('script')
<script>
  $(document).ready(function() {
    var peran = $('#peran');
    var akunKampus = $('#akunKampus');

    function toggleAkun() {
      akunKampus.toggleClass('d-none', peran.val() !== 'kampus');
    }

    peran.on('change', toggleAkun);
    toggleAkun();
  });
</script>
@endpush
