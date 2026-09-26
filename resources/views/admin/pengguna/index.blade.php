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
          @if(auth()->user()->canManageUsers())
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
                  @php
                    $roleName = $user->getRoleNames()->first();
                    // Baris super_admin tidak punya aksi sama sekali: hanya super_admin
                    // yang boleh menyuntingnya, dan aksi logout/statusnya justru ditolak.
                    $rowIsSuperAdmin = $roleName === 'super_admin';
                    $isSuperAdmin = auth()->user()->hasRole('super_admin');
                    $canEdit = ! $rowIsSuperAdmin && auth()->user()->canManageUsers();
                    $canUseSuperActions = $isSuperAdmin && ! $rowIsSuperAdmin;
                  @endphp
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                      <span class="badge {{ $roleName === 'super_admin' ? 'badge-danger' : 'badge-info' }}">
                        {{ $roleLabels[$roleName] ?? $roleName }}
                      </span>
                    </td>
                    <td>
                      @if($user->status === 'aktif')
                      <span class="badge badge-success">Aktif</span>
                      @else
                      <span class="badge badge-secondary">Nonaktif</span>
                      @endif
                    </td>
                    <td>
                      {{-- "Lihat" itu read-only, jadi aman untuk semua baris termasuk
                           super_admin; dropdown aksi tetap disembunyikan untuk
                           baris itu karena seluruh isinya adalah mutasi. --}}
                      <a class="btn btn-info btn-sm" href="{{ route('admin.pengguna.lihat', $user) }}">
                        <i class="fas fa-eye mr-1"></i> Lihat
                      </a>
                      @if(! $rowIsSuperAdmin)
                      <div class="btn-group">
                        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <i class="fas fa-cog"></i> Aksi
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                          @if($canEdit)
                          <a class="dropdown-item" href="{{ route('admin.pengguna.ubah', $user) }}">
                            <i class="fas fa-user-tag mr-1"></i> Ubah
                          </a>
                          @endif

                          @if($canUseSuperActions)
                          <div class="dropdown-divider"></div>

                          <form action="{{ route('admin.pengguna.toggle-status', $user) }}" method="POST" class="m-0">
                            @csrf
                            @method('PATCH')
                            @if($user->status === 'aktif')
                            <button type="button" class="dropdown-item btn-confirm-toggle"
                              data-confirm-title="Nonaktifkan Pengguna?"
                              data-confirm-text="Apakah Anda yakin ingin menonaktifkan '{{ $user->username }}'?"
                              data-confirm-icon="warning"
                              data-confirm-color="#e74c3c"
                              data-confirm-button="Ya, Nonaktifkan!">
                              <i class="fas fa-ban mr-1"></i> Nonaktifkan
                            </button>
                            @else
                            <button type="button" class="dropdown-item btn-confirm-toggle"
                              data-confirm-title="Aktifkan Pengguna?"
                              data-confirm-text="Apakah Anda yakin ingin mengaktifkan '{{ $user->username }}'?"
                              data-confirm-icon="question"
                              data-confirm-color="#47c363"
                              data-confirm-button="Ya, Aktifkan!">
                              <i class="fas fa-check mr-1"></i> Aktifkan
                            </button>
                            @endif
                          </form>

                          <form action="{{ route('admin.pengguna.reset-password', $user) }}" method="POST" class="m-0">
                            @csrf
                            <button type="button" class="dropdown-item btn-confirm-toggle"
                              data-confirm-title="Reset Password?"
                              data-confirm-text="Kata sandi '{{ $user->username }}' akan direset menjadi 12345678. Lanjutkan?"
                              data-confirm-icon="question"
                              data-confirm-color="#6777ef"
                              data-confirm-button="Ya, Reset!">
                              <i class="fas fa-key mr-1"></i> Reset Password
                            </button>
                          </form>

                          <div class="dropdown-divider"></div>

                          <form action="{{ route('admin.pengguna.hapus', $user) }}" method="POST" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="dropdown-item text-danger btn-delete"
                              data-confirm-title="Hapus Pengguna?"
                              data-confirm-text="Apakah Anda yakin ingin menghapus '{{ $user->username }}'? Tindakan ini tidak dapat dibatalkan."
                              data-confirm-icon="warning"
                              data-confirm-color="#e74c3c"
                              data-confirm-button="Ya, Hapus!">
                              <i class="fas fa-trash mr-1"></i> Hapus User
                            </button>
                          </form>
                          @endif
                        </div>
                      </div>
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

    const btnConfirmToggle = document.querySelectorAll('.btn-confirm-toggle');
    btnConfirmToggle.forEach(function(button) {
      button.addEventListener('click', function() {
        Swal.fire({
          title: button.getAttribute('data-confirm-title'),
          text: button.getAttribute('data-confirm-text'),
          icon: button.getAttribute('data-confirm-icon') || 'question',
          showCancelButton: true,
          confirmButtonColor: button.getAttribute('data-confirm-color') || '#6777ef',
          cancelButtonColor: '#6c757d',
          confirmButtonText: button.getAttribute('data-confirm-button') || 'Ya',
          cancelButtonText: 'Batal',
        }).then(function(result) {
          if (result.isConfirmed) {
            button.closest('form').submit();
          }
        });
      });
    });

    const btnDelete = document.querySelectorAll('.btn-delete');
    btnDelete.forEach(function(button) {
      button.addEventListener('click', function() {
        Swal.fire({
          title: button.getAttribute('data-confirm-title'),
          text: button.getAttribute('data-confirm-text'),
          icon: button.getAttribute('data-confirm-icon') || 'warning',
          showCancelButton: true,
          confirmButtonColor: button.getAttribute('data-confirm-color') || '#e74c3c',
          cancelButtonColor: '#6c757d',
          confirmButtonText: button.getAttribute('data-confirm-button') || 'Ya, Hapus!',
          cancelButtonText: 'Batal',
        }).then(function(result) {
          if (result.isConfirmed) {
            button.closest('form').submit();
          }
        });
      });
    });
  });
</script>
@endpush