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
          @if(auth()->user()->hasMenuAccess('admin.kampus.index'))
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <a href="{{ route('admin.kampus.fakultas.buat', $kampus) }}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Tambah Fakultas
            </a>
            <form action="{{ route('admin.kampus.fakultas.massDestroy', $kampus) }}" method="POST" id="form-mass-delete">
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
              <table class="table table-striped" id="fakultasTable">
                <thead>
                  <tr>
                    @if(auth()->user()->hasMenuAccess('admin.kampus.index'))
                    <th style="width: 40px;"><input type="checkbox" id="checkAll"></th>
                    @endif
                    <th>No</th>
                    <th>Fakultas</th>
                    <th>Jumlah Prodi</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($fakultas as $data)
                  <tr>
                    @if(auth()->user()->hasMenuAccess('admin.kampus.index'))
                    <td><input type="checkbox" class="row-check" value="{{ $data->id }}"></td>
                    @endif
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $data->nama }}</td>
                    <td>{{ $data->prodi_count }}</td>
                    <td>
                      @include('admin.kampus.fakultas._aksi', ['kampus' => $kampus, 'data' => $data])
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
  $('#fakultasTable').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: "{{ route('admin.kampus.fakultas.data', $kampus) }}" },
    order: [],
    columnDefs: [
        { "orderable": false, "searchable": false, "targets": [0, 1, 4] }
    ],
    columns: [
        { data: 'checkbox' },
        { data: 'no' },
        { data: 'nama' },
        { data: 'prodi_count' },
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
      tableSelector: '#fakultasTable',
      allSelector: '#checkAll',
      itemSelector: '.row-check',
      buttonSelector: '#btn-mass-delete',
      formSelector: '#form-mass-delete',
      entityLabel: 'fakultas',
      confirmText: 'Seluruh program studi pada fakultas terpilih juga akan terhapus.'
    });
  }
});
</script>
@endpush