@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Jadwal Pengumuman</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Pengumuman</div>
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
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped" id="pengumumanTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama Beasiswa</th>
                    <th>Kampus</th>
                    <th>Periode Pengumuman</th>
                    <th>Status</th>
                    <th>Penerima</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($scholarships as $scholarship)
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ $scholarship->nama }}</td>
                      <td>{{ $scholarship->kampus }}</td>
                      <td>
                        @if($scholarship->tanggal_pengumuman && $scholarship->tanggal_pengumuman_selesai)
                          {{ $scholarship->tanggal_pengumuman->translatedFormat('d F Y') }}
                          s/d
                          {{ $scholarship->tanggal_pengumuman_selesai->translatedFormat('d F Y') }}
                        @else
                          <span class="text-muted">Belum diatur</span>
                        @endif
                      </td>
                      <td>
                        @if($scholarship->isPengumumanAktif())
                          <span class="badge badge-success">Aktif</span>
                        @elseif($scholarship->tanggal_pengumuman && $scholarship->hasPengumuman())
                          <span class="badge badge-secondary">Selesai</span>
                        @elseif($scholarship->tanggal_pengumuman)
                          <span class="badge badge-warning">Belum Mulai</span>
                        @else
                          <span class="badge badge-secondary">Tanpa Jadwal</span>
                        @endif
                      </td>
                      <td>{{ $scholarship->penerima_count }}</td>
                      <td>
                        <div class="d-flex gap-1">
                          <a href="{{ route('admin.pengumuman.ubah', $scholarship) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Ubah
                          </a>
                          @if($scholarship->hasPengumuman())
                            <a href="{{ route('pengumuman.show', $scholarship) }}" target="_blank" class="btn btn-info btn-sm">
                              <i class="fas fa-external-link-alt"></i> Publik
                            </a>
                          @endif
                          @if($scholarship->tanggal_pengumuman)
                            <form action="{{ route('admin.pengumuman.hapus', $scholarship) }}" method="POST" class="d-inline btn-delete-form">
                              @csrf
                              @method('DELETE')
                              <button type="button" class="btn btn-danger btn-sm btn-delete">
                                <i class="fas fa-trash"></i> Hapus
                              </button>
                            </form>
                          @endif
                        </div>
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
  $('#pengumumanTable').DataTable({
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