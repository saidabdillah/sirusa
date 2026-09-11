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
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <a href="{{ route('admin.kampus.prodi.buat', [$kampus, $fakultas]) }}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Tambah Program Studi
            </a>
            <form action="{{ route('admin.kampus.prodi.massDestroy', [$kampus, $fakultas]) }}" method="POST" id="form-mass-delete">
              @csrf
              @method('DELETE')
              <button type="button" class="btn btn-danger" id="btn-mass-delete" disabled>
                <i class="fas fa-trash"></i> Hapus Terpilih
              </button>
            </form>
          </div>
          @endif
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped" id="prodiTable">
                <thead>
                  <tr>
                    @if(auth()->user()->hasRole('admin'))
                    <th style="width: 40px;"><input type="checkbox" id="checkAll"></th>
                    @endif
                    <th>No</th>
                    <th>Program Studi</th>
                    @if(auth()->user()->hasRole('admin'))
                    <th>Aksi</th>
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @foreach($prodi as $data)
                  <tr>
                    @if(auth()->user()->hasRole('admin'))
                    <td><input type="checkbox" class="row-check" value="{{ $data->id }}"></td>
                    @endif
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $data->nama }}</td>
                    @if(auth()->user()->hasRole('admin'))
                    <td>
                      <a href="{{ route('admin.kampus.prodi.ubah', [$kampus, $fakultas, $data]) }}" class="btn btn-primary btn-sm mr-1 mb-1" title="Ubah">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form action="{{ route('admin.kampus.prodi.hapus', [$kampus, $fakultas, $data]) }}" method="POST" class="d-inline mr-1 mb-1 btn-delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus"
                          data-confirm-title="Hapus Program Studi?"
                          data-confirm-text="Apakah Anda yakin ingin menghapus program studi '{{ $data->nama }}'? Tindakan ini tidak dapat dibatalkan.">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </td>
                    @endif
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
  $('#prodiTable').DataTable({
    order: [],
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

  if (typeof initMassDelete === 'function') {
    initMassDelete({
      tableSelector: '#prodiTable',
      allSelector: '#checkAll',
      itemSelector: '.row-check',
      buttonSelector: '#btn-mass-delete',
      formSelector: '#form-mass-delete',
      entityLabel: 'program studi',
      confirmText: 'Data terpilih akan dihapus permanen.'
    });
  }
});
</script>
@endpush