@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Tambah Pengguna</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
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
                  <label for="peran">Peran <span class="text-danger">*</span></label>
                  <select class="form-control @error('peran') is-invalid @enderror" id="peran" name="peran">
                    <option value="user" {{ old('peran', 'user') === 'user' ? 'selected' : '' }}>User</option>
                    <option value="super_admin" {{ old('peran') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="kesra" {{ old('peran') === 'kesra' ? 'selected' : '' }}>Kesra</option>
                    <option value="kampus" {{ old('peran') === 'kampus' ? 'selected' : '' }}>Kampus</option>
                    <option value="capil" {{ old('peran') === 'capil' ? 'selected' : '' }}>Capil</option>
                  </select>
                  @error('peran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                  <div class="form-group col-md-6" data-field="username">
                    <label for="username">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}">
                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="email">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>

                <div id="akunMahasiswa">
                  <div class="form-group">
                    <label for="nik">NIK <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nik') is-invalid @enderror" id="nik" name="nik" value="{{ old('nik') }}" placeholder="16 digit NIK">
                    @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <small class="text-muted d-block mb-0">
                    <i class="fas fa-info-circle mr-1"></i> Kata sandi awal akun mahasiswa otomatis <strong>12345678</strong>.
                    Username diisi otomatis bila dikosongkan.
                  </small>
                </div>

                <div id="akunStaf" class="mt-3">
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label for="password">Kata Sandi <span class="text-danger">*</span></label>
                      <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                      @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-6">
                      <label for="password_confirmation">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                      <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation">
                      @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <small class="text-muted">Username adalah kredensial masuk akun admin.</small>
                </div>

                <div class="form-group d-none mt-3" id="akunKampus">
                  <label for="kampus_id">Kampus <span class="text-danger">*</span></label>
                  <select class="form-control @error('kampus_id') is-invalid @enderror" id="kampus_id" name="kampus_id">
                    <option value="">-- Pilih Kampus --</option>
                    @foreach ($kampusList as $kampusId => $kampusNama)
                      <option value="{{ $kampusId }}" {{ (string) old('kampus_id') === (string) $kampusId ? 'selected' : '' }}>{{ $kampusNama }}</option>
                    @endforeach
                  </select>
                  @error('kampus_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <small class="text-muted">Admin kampus hanya dapat melihat dan memverifikasi data pendaftar dari kampus yang dipilih.</small>
                </div>

                <div class="form-group">
                  <label for="status">Status <span class="text-danger">*</span></label>
                  <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                    <option value="aktif" {{ old('status', 'aktif') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="non-aktif" {{ old('status') === 'non-aktif' ? 'selected' : '' }}>Nonaktif</option>
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
                <strong>Kata Sandi Akun</strong><br>
                <small class="text-muted">Akun mahasiswa otomatis memakai kata sandi <code>12345678</code>; akun staf memakai kata sandi yang diisi pada form. Kata sandi dapat direset lewat aksi <em>Reset Password</em> di daftar pengguna.</small>
              </div>
              <div class="mb-3">
                <strong>Username Mahasiswa</strong><br>
                <small class="text-muted">Kosongkan untuk membuatnya otomatis (format <code>usr</code> + 8 karakter acak). Mahasiswa masuk dengan NIK.</small>
              </div>
              <div class="mb-0">
                <strong>Admin Kampus</strong><br>
                <small class="text-muted">Peran Kampus wajib memilih kampus agar data pendaftar dan verifikasi kampus ter-scope.</small>
              </div>
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
    var akunMahasiswa = $('#akunMahasiswa');
    var akunStaf = $('#akunStaf');
    var akunKampus = $('#akunKampus');
    var usernameField = $('[data-field="username"]');

    function toggleAkun() {
      var isUser = peran.val() === 'user';
      var isKampus = peran.val() === 'kampus';

      // Akun mahasiswa: NIK (kata sandi otomatis, username opsional)
      akunMahasiswa.toggleClass('d-none', !isUser);
      // Akun staf: kata sandi + konfirmasi
      akunStaf.toggleClass('d-none', isUser);
      // Admin kampus: pilihan kampus
      akunKampus.toggleClass('d-none', !isKampus);

      // Username mahasiswa diisi otomatis bila dikosongkan
      usernameField.toggleClass('d-none', isUser);
    }

    peran.on('change', toggleAkun);
    toggleAkun();
  });
</script>
@endpush
