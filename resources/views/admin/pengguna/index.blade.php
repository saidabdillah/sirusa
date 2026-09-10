@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Daftar Pengguna</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Pengguna</div>
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
    <div class="row">
      <div class="col-12">
        <div class="card">
          @if(auth()->user()->hasRole('super_admin'))
          <div class="card-header">
            <a href="{{ route('admin.pengguna.buat') }}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Tambah Pengguna
            </a>
          </div>
          @endif
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped" id="userTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($users as $user)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                      @foreach($user->roles as $role)
                      @if($role->name === 'super_admin')
                      <span class="badge badge-danger">Super Admin</span>
                      @elseif($role->name === 'admin')
                      <span class="badge badge-warning">Admin</span>
                      @else
                      <span class="badge badge-info">User</span>
                      @endif
                      @endforeach
                    </td>
                    <td>
                      @if($user->status === 'aktif')
                      <span class="badge badge-success">Aktif</span>
                      @else
                      <span class="badge badge-secondary">Nonaktif</span>
                      @endif
                    </td>
                    <td>
                      @if(auth()->user()->hasRole('super_admin'))
                      <a href="{{ route('admin.pengguna.ubah', $user) }}" class="btn btn-warning btn-sm mr-1" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      @endif
                      @if(! $user->hasRole('super_admin'))
                      <form action="{{ route('admin.pengguna.toggle-status', $user) }}" method="POST" class="d-inline mr-1">
                        @csrf
                        @method('PATCH')
                        @if($user->status === 'aktif')
                        <button type="button" class="btn btn-danger btn-sm btn-confirm-toggle" title="Nonaktifkan"
                          data-confirm-title="Nonaktifkan Pengguna?"
                          data-confirm-text="Apakah Anda yakin ingin menonaktifkan '{{ $user->username }}'?"
                          data-confirm-icon="warning"
                          data-confirm-color="#e74c3c"
                          data-confirm-button="Ya, Nonaktifkan!"
                          data-loading-text="Menonaktifkan...">
                          <i class="fas fa-ban"></i>
                        </button>
                        @else
                        <button type="button" class="btn btn-success btn-sm btn-confirm-toggle" title="Aktifkan"
                          data-confirm-title="Aktifkan Pengguna?"
                          data-confirm-text="Apakah Anda yakin ingin mengaktifkan '{{ $user->username }}'?"
                          data-confirm-icon="question"
                          data-confirm-color="#47c363"
                          data-confirm-button="Ya, Aktifkan!"
                          data-loading-text="Mengaktifkan...">
                          <i class="fas fa-check"></i>
                        </button>
                        @endif
                      </form>
                      @endif
                      @if(auth()->user()->hasRole('super_admin') && ! $user->hasRole('super_admin'))
                      <form action="{{ route('admin.pengguna.hapus', $user) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus"
                          data-confirm-title="Hapus Pengguna?"
                          data-confirm-text="Apakah Anda yakin ingin menghapus '{{ $user->username }}'? Tindakan ini tidak dapat dibatalkan."
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
  </div>
</section>
@endsection

@push('script')
<script>
  $(document).ready(function() {
  $('#userTable').DataTable({
    language: {
      search: "Cari:",
      lengthMenu: "Tampilkan _MENU_ data",
      info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
      infoEmpty: "Tidak ada data",
      infoFiltered: "(disaring dari _MAX_ total data)",
      zeroRecords: "Tidak ada data yang cocok",
      paginate: {
        first: "Pertama",
        last: "Terakhir",
        next: "Selanjutnya",
        previous: "Sebelumnya"
      }
    }
  });
});
</script>
@endpush