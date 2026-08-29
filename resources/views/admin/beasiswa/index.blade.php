@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Daftar Beasiswa</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Beasiswa</div>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif

    <div class="section-body">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              @if(auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.beasiswa.buat') }}" class="btn btn-primary">
                  <i class="fas fa-plus"></i> Tambah Beasiswa
                </a>
              @endif
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped" id="scholarshipTable">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>Nama Beasiswa</th>
                      <th>Kampus</th>
                      <th>Kuota</th>
                      <th>Gelar</th>
                      <th>Batas Waktu</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($scholarships as $scholarship)
                      <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                          <a href="{{ route('admin.beasiswa.lihat', $scholarship) }}">{{ $scholarship->nama }}</a>
                        </td>
                        <td>{{ $scholarship->kampus }}</td>
                        <td>{{ $scholarship->kuota }}</td>
                        <td>{{ $scholarship->tingkat_gelar }}</td>
                        <td>
                          <span class="{{ $scholarship->isExpired() ? 'text-danger' : '' }}">
                            {{ $scholarship->batas_waktu?->format('d M Y') }}
                          </span>
                        </td>
                        <td>
                          @if($scholarship->status === 'aktif')
                            <span class="badge badge-success">Aktif</span>
                          @else
                            <span class="badge badge-secondary">Non-aktif</span>
                          @endif
                        </td>
                        <td>
                          @if(auth()->user()->hasRole('admin'))
                            <div class="d-flex gap-1">
                              <a href="{{ route('admin.beasiswa.ubah', $scholarship) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Ubah
                              </a>
                              <form action="{{ route('admin.beasiswa.hapus', $scholarship) }}" method="POST" class="d-inline btn-delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm btn-delete">
                                  <i class="fas fa-trash"></i> Hapus
                                </button>
                              </form>
                            </div>
                          @else
                            <a href="{{ route('admin.beasiswa.lihat', $scholarship) }}" class="btn btn-info btn-sm">
                              <i class="fas fa-eye"></i> Detail
                            </a>
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
  $('#scholarshipTable').DataTable({
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