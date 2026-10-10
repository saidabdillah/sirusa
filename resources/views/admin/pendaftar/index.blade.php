@extends('layouts.app')

@push('style')
<style>
  /* pendaftar-table-scroll-fix: DataTables menghitung lebar kolom dalam px sehingga
     tabel melebar beberapa px melebihi kontainer (.table-responsive) dan timbul
     scroll horizontal tipis. Paksa lebar tabel = 100% kontainer. */
  #pendaftarTable.dataTable {
    width: 100% !important;
  }
</style>
@endpush

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Daftar Pendaftar</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Pendaftar</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4>Daftar Pendaftar</h4>
            </div>
            <div class="card-body">
            <form method="GET" action="{{ route('admin.pendaftar.index') }}" class="mb-3">
              <div class="form-row align-items-end">
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="status">Status</label>
                  <select name="status" id="status" class="form-control">
                    <option value="">-- Semua Status --</option>
                    {{-- Label diambil dari peta status `Applicant`, bukan ditulis
                         ulang di sini, supaya dropdown tidak menampilkan label
                         basi saat istilah status berubah. --}}
                    @foreach(\App\Models\Applicant::STATUS_LABELS as $nilai => $label)
                      <option value="{{ $nilai }}" {{ request('status') === $nilai ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="beasiswa_id">Beasiswa</label>
                  <select name="beasiswa_id" id="beasiswa_id" class="form-control">
                    <option value="">-- Semua Beasiswa --</option>
                    @foreach($beasiswas as $b)
                      <option value="{{ $b->id }}" {{ request('beasiswa_id') == $b->id ? 'selected' : '' }}>{{ $b->nama }}</option>
                    @endforeach
                  </select>
                </div>
{{-- Hanya dua filter, jadi tombol sejajar dengan select (bukan baris
                     terpisah bawah). --}}
                <div class="col-auto mb-2 mb-md-0">
                  <button type="submit" class="btn btn-primary" data-loading-text="Mencari...">
                    <i class="fas fa-search"></i> Cari
                  </button>
                </div>
                <div class="col-auto mb-2 mb-md-0">
                  <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                  </a>
                </div>
              </div>
            </form>
            <div class="table-responsive">
              <table class="table table-striped" id="pendaftarTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Beasiswa</th>
                    <th>Fakultas</th>
                    <th>Prodi</th>
                    <th>IPK</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($applicants as $applicant)
                    @php $profile = $applicant->user->profile; @endphp
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ $profile->nama_lengkap ?? '-' }}</td>
                      <td>{{ $applicant->beasiswa->nama }}</td>
                      <td>{{ $applicant->fakultas ?? '-' }}</td>
                      <td>{{ $applicant->prodi ?? '-' }}</td>
                      <td>{{ $applicant->ipk }}</td>
                      <td>
                        <span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span>
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
  $('#pendaftarTable').DataTable({
    serverSide: true,
    processing: true,
    ajax: {
      url: "{{ route('admin.pendaftar.data') }}",
      data: function (d) {
        d.status = $('#status').val();
        d.beasiswa_id = $('#beasiswa_id').val();
      }
    },
    order: [],
    columnDefs: [
        { "orderable": false, "searchable": false, "targets": [0] }
    ],
    columns: [
        { data: 'no' },
        { data: 'nama' },
        { data: 'beasiswa' },
        { data: 'fakultas' },
        { data: 'prodi' },
        { data: 'ipk' },
        { data: 'status' }
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
