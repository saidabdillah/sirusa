@extends('layouts.app')

@section('content')
<style>
  #kampusTable.dataTable {
    width: 100% !important;
  }
</style>
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
          @if(auth()->user()->hasMenuAccess('admin.kampus.index'))
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <a href="{{ route('admin.kampus.buat') }}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Tambah Kampus
            </a>
            <form action="{{ route('admin.kampus.massDestroy') }}" method="POST" id="form-mass-delete">
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
              <table class="table table-striped" id="kampusTable">
                <thead>
                  <tr>
                    @if(auth()->user()->hasMenuAccess('admin.kampus.index'))
                    <th style="width: 40px;"><input type="checkbox" id="checkAll"></th>
                    @endif
                    <th>No</th>
                    <th>Kampus</th>
                    <th>Jumlah Fakultas</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($kampus as $data)
                  <tr>
                    @if(auth()->user()->hasMenuAccess('admin.kampus.index'))
                    <td><input type="checkbox" class="row-check" value="{{ $data->id }}"></td>
                    @endif
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $data->nama_kampus }}</td>
                    <td>{{ $data->fakultas_count }}</td>
                    <td>
                      @include('admin.kampus._aksi', ['data' => $data])
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
    serverSide: true,
    processing: true,
    ajax: { url: "{{ route('admin.kampus.data') }}" },
    order: [],
    columnDefs: [
        { "orderable": false, "searchable": false, "targets": [0, 1, 4] }
    ],
    columns: [
        { data: 'checkbox' },
        { data: 'no' },
        { data: 'kampus' },
        { data: 'fakultas_count' },
        { data: 'aksi' }
    ],
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
      tableSelector: '#kampusTable',
      allSelector: '#checkAll',
      itemSelector: '.row-check',
      buttonSelector: '#btn-mass-delete',
      formSelector: '#form-mass-delete',
      entityLabel: 'kampus',
      confirmText: 'Seluruh fakultas dan program studi pada kampus terpilih juga akan terhapus.'
    });
  }
});
</script>
@endpush