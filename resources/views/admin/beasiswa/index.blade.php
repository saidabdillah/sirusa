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
              @if(auth()->user()->hasMenuAccess('admin.beasiswa.index'))
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
                     <th>IPK Min</th>
                     <th>Semester Min</th>
                     <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($scholarships as $scholarship)
                      <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $scholarship->nama }}</td>
                        <td>{{ $scholarship->kampus }}</td>
                        <td>{{ $scholarship->kuota }}</td>
                        <td>{{ $scholarship->tingkat_gelar }}</td>
                        <td>
                          <span class="{{ $scholarship->isExpired() ? 'text-danger' : '' }}">
                            {{ $scholarship->tanggal_mulai?->translatedFormat('d M Y') }}
                            - {{ $scholarship->tanggal_selesai?->translatedFormat('d M Y') }}
                          </span>
                        </td>
                        <td>{{ number_format($scholarship->ipk_minimal, 2) }}</td>
                        <td>{{ $scholarship->semester_minimal }}</td>
                        <td>
                          @if($scholarship->status === 'aktif')
                            <span class="badge badge-success">Aktif</span>
                          @else
                            <span class="badge badge-secondary">Non-aktif</span>
                          @endif
                        </td>
                        <td>
                          @include('admin.beasiswa._aksi', ['data' => $scholarship])
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
    serverSide: true,
    processing: true,
    ajax: { url: "{{ route('admin.beasiswa.data') }}" },
    order: [],
    columnDefs: [
        { "orderable": false, "searchable": false, "targets": [0, 4, 5, 6, 7, 8, 9] }
    ],
    columns: [
        { data: 'no' },
        { data: 'nama' },
        { data: 'kampus' },
        { data: 'kuota' },
        { data: 'gelar' },
        { data: 'batas' },
        { data: 'ipk' },
        { data: 'semester' },
        { data: 'status' },
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
});
</script>
@endpush