@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Kelola Role</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Role</div>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
  @endif

  <div class="section-body">
    <div class="col-12 p-0">
      <div class="card">
        <div class="card-header">
          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-tambah-role">
            <i class="fas fa-plus"></i> Tambah Role
          </button>
          <div class="card-header-action ml-3">
            <a href="{{ route('admin.menu.index') }}" class="btn btn-info">
              <i class="fas fa-th-list"></i> Atur Akses Menu
            </a>
          </div>
        </div>
        <div class="card-body">
          <div class="alert alert-primary mb-3">
            <i class="fas fa-info-circle mr-1"></i> Akses halaman &amp; tampilan sidebar ditentukan dari menu yang dicentangkan pada halaman <strong>Atur Akses Menu</strong>.
          </div>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Role</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($roles as $role)
                @php
                  $roleLabels = [
                      'super_admin' => 'Super Admin',
                      'kesra' => 'Kesra',
                      'kampus' => 'Kampus',
                      'capil' => 'Capil',
                      'user' => 'User',
                  ];
                @endphp
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>
                    <span class="badge {{ $role->name === 'super_admin' ? 'badge-danger' : 'badge-info' }}">
                      {{ $roleLabels[$role->name] ?? $role->name }}
                    </span>
                  </td>
                  <td>
                    @if($role->name !== 'super_admin')
                    <button type="button" class="btn btn-primary btn-sm mr-1 mb-1" title="Ubah"
                      data-toggle="modal" data-target="#modal-ubah-role-{{ $role->id }}">
                      <i class="fas fa-edit"></i>
                    </button>
                    <form action="{{ route('admin.role.hapus', $role) }}" method="POST" class="d-inline-block align-middle mr-1 mb-1 btn-delete-form">
                      @csrf
                      @method('DELETE')
                      <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus"
                        data-confirm-title="Hapus Role?"
                        data-confirm-text="Apakah Anda yakin ingin menghapus role '{{ $roleLabels[$role->name] ?? $role->name }}'?"
                        data-confirm-button="Ya, Hapus!">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Modal Tambah & Ubah Role (dirender di level body lewat @stack('modal')) --}}
  @push('modal')
  @php
    $modalTarget = 'modal-tambah-role';
    $isTarget = old('modal_target') === $modalTarget;
  @endphp
  <div class="modal fade" id="modal-tambah-role" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form action="{{ route('admin.role.simpan') }}" method="POST">
          @csrf
          <input type="hidden" name="modal_target" value="{{ $modalTarget }}">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Role</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="name-tambah">Nama Role <span class="text-danger">*</span></label>
              <input type="text" class="form-control {{ $isTarget && $errors->has('name') ? 'is-invalid' : '' }}" id="name-tambah" name="name" value="{{ $isTarget ? old('name') : '' }}">
              @if($isTarget && $errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Modal Ubah per role (non super_admin) --}}
  @foreach($roles as $role)
    @if($role->name !== 'super_admin')
      @include('admin.role.modal-ubah', ['role' => $role])
    @endif
  @endforeach
  @endpush
</section>
@endsection

@push('script')
<script>
  $(document).ready(function() {
    const openModal = @json(old('modal_target'));
    if (openModal && document.getElementById(openModal)) {
      $('#' + openModal).modal('show');
    }

    (function() {
      const btnDelete = document.querySelectorAll('.btn-delete');
      btnDelete.forEach(function(button) {
        button.addEventListener('click', function() {
          const form = button.closest('.btn-delete-form');
          const text = button.getAttribute('data-confirm-text');
          form.submit();
        });
      });
    })();
  });
</script>
@endpush