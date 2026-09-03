@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Fakultas - {{ $kampus->nama_kampus }}</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.index') }}">Kampus</a></div>
      <div class="breadcrumb-item">Fakultas</div>
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
            <a href="{{ route('admin.kampus.fakultas.buat', $kampus) }}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Tambah Fakultas
            </a>
          </div>
          @endif
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped" id="fakultasTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Fakultas</th>
                    <th>Jumlah Prodi</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($fakultas as $data)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $data->nama }}</td>
                    <td>{{ $data->prodi_count }}</td>
                    <td>
                      <a href="{{ route('admin.kampus.prodi.index', [$kampus, $data]) }}" class="btn btn-info btn-sm" title="Kelola Program Studi">
                        <i class="fas fa-graduation-cap"></i>
                      </a>
                      @if(auth()->user()->hasRole('admin'))
                      <a href="{{ route('admin.kampus.fakultas.ubah', [$kampus, $data]) }}" class="btn btn-warning btn-sm" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form action="{{ route('admin.kampus.fakultas.hapus', [$kampus, $data]) }}" method="POST" class="d-inline"
                        id="delete-form-{{ $data->id }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger btn-sm" title="Hapus"
                          onclick="confirmDelete({{ $data->id }}, '{{ $data->nama }}')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                      @endif
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="4" class="text-center">Belum ada fakultas. Klik "Tambah Fakultas" untuk menambahkan.</td>
                  </tr>
                  @endforelse
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
  $('#fakultasTable').DataTable({
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

function confirmDelete(id, namaFakultas) {
  Swal.fire({
    title: 'Hapus Fakultas?',
    text: "Apakah Anda yakin ingin menghapus fakultas '" + namaFakultas + "' beserta seluruh program studinya? Tindakan ini tidak dapat dibatalkan.",
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