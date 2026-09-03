@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Daftar Kampus</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Kampus</div>
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
          @if(auth()->user()->hasRole('admin'))
          <div class="card-header">
            <a href="{{ route('admin.kampus.buat') }}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Tambah Kampus
            </a>
          </div>
          @endif
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped" id="kampusTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Kampus</th>
                    <th>Jumlah Fakultas</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($kampus as $data)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $data->nama_kampus }}</td>
                    <td>{{ $data->fakultas_count }}</td>
                    <td>
                      <a href="{{ route('admin.kampus.fakultas.index', $data) }}" class="btn btn-info btn-sm" title="Kelola Fakultas">
                        <i class="fas fa-building"></i>
                      </a>
                      @if(auth()->user()->hasRole('admin'))
                      <a href="{{ route('admin.kampus.ubah', $data) }}" class="btn btn-warning btn-sm" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form action="{{ route('admin.kampus.hapus', $data) }}" method="POST" class="d-inline"
                        id="delete-form-{{ $data->id }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger btn-sm" title="Hapus"
                          onclick="confirmDelete({{ $data->id }}, '{{ $data->nama_kampus }}')">
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
  $('#kampusTable').DataTable({
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

function confirmDelete(id, namaKampus) {
  Swal.fire({
    title: 'Hapus Kampus?',
    text: "Apakah Anda yakin ingin menghapus '" + namaKampus + "' beserta seluruh fakultas dan program studinya? Tindakan ini tidak dapat dibatalkan.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e74c3c',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('delete-form-' + id).submit();
    }
  });
}
</script>
@endpush