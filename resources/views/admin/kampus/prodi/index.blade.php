@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Program Studi - {{ $fakultas->nama }}</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.index') }}">Kampus</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.kampus.fakultas.index', $kampus) }}">{{ $kampus->nama_kampus }}</a></div>
      <div class="breadcrumb-item">Program Studi</div>
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
            <a href="{{ route('admin.kampus.prodi.buat', [$kampus, $fakultas]) }}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Tambah Program Studi
            </a>
          </div>
          @endif
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped" id="prodiTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Program Studi</th>
                    @if(auth()->user()->hasRole('admin'))
                    <th>Aksi</th>
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @forelse($prodi as $data)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $data->nama }}</td>
                    @if(auth()->user()->hasRole('admin'))
                    <td>
                      <a href="{{ route('admin.kampus.prodi.ubah', [$kampus, $fakultas, $data]) }}" class="btn btn-warning btn-sm" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form action="{{ route('admin.kampus.prodi.hapus', [$kampus, $fakultas, $data]) }}" method="POST" class="d-inline"
                        id="delete-form-{{ $data->id }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger btn-sm" title="Hapus"
                          onclick="confirmDelete({{ $data->id }}, '{{ $data->nama }}')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </td>
                    @endif
                  </tr>
                  @empty
                  <tr>
                    <td colspan="3" class="text-center">Belum ada program studi. Klik "Tambah Program Studi" untuk menambahkan.</td>
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
  $('#prodiTable').DataTable({
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

function confirmDelete(id, namaProdi) {
  Swal.fire({
    title: 'Hapus Program Studi?',
    text: "Apakah Anda yakin ingin menghapus program studi '" + namaProdi + "'? Tindakan ini tidak dapat dibatalkan.",
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