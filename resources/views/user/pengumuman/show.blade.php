@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Pengumuman Beasiswa</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('user.beasiswa.index') }}">Daftar Beasiswa</a></div>
      <div class="breadcrumb-item">Pengumuman</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ $scholarship->nama }} ({{ $scholarship->kampus }})</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-info mb-4">
              <i class="fas fa-bullhorn mr-2"></i>
              <strong>Periode Pengumuman:</strong> {{ $scholarship->tanggal_pengumuman->format('d F Y') }} s.d. {{ $scholarship->tanggal_pengumuman_selesai->format('d F Y') }}
            </div>

            <div class="table-responsive">
              <table class="table table-striped" id="pengumumanTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Fakultas</th>
                    <th>Program Studi</th>
                    <th>IPK</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($penerima as $p)
                    @php $profile = $p->user->profile; @endphp
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ $profile->nama_lengkap ?? '-' }}</td>
                      <td>{{ $p->fakultas ?? '-' }}</td>
                      <td>{{ $p->prodi ?? '-' }}</td>
                      <td>{{ $p->ipk }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted py-3">Belum ada penerima beasiswa</td>
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