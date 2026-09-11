@extends('layouts.app')

@section('skeleton')
@include('layouts.partials.skeleton.shell', [
    'content' => view('layouts.partials.skeleton.table', [
        'rows' => min($penerima->count(), 10),
        'cols' => 7,
        'count' => $penerima->count(),
    ])->render(),
])
@endsection

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Pengumuman Penerima</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('user.beasiswa.index') }}">Daftar Beasiswa</a></div>
        <div class="breadcrumb-item">Pengumuman</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header">
          <h4>{{ $scholarship->nama }}</h4>
          <div class="card-header-action">
            <a href="{{ route('pengumuman.export-pdf', $scholarship) }}" target="_blank" rel="noopener" class="btn btn-danger btn-sm">
              <i class="fas fa-file-pdf"></i> Preview PDF
            </a>
          </div>
        </div>
        <div class="card-body">
          <div class="row mb-4">
            <div class="col-md-6">
              <strong>Penyedia:</strong><br>
              {{ $scholarship->kampus }}
            </div>
            <div class="col-md-6">
              <strong>Periode Pengumuman:</strong><br>
              {{ $scholarship->tanggal_pengumuman->translatedFormat('d F Y') }}
              s/d
              {{ $scholarship->tanggal_pengumuman_selesai->translatedFormat('d F Y') }}
            </div>
          </div>

          <h5 class="mb-3">Daftar Penerima Beasiswa</h5>
          <div class="table-responsive">
            <table class="table table-striped" id="penerimaTable">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Lengkap</th>
                  <th>NIM</th>
                  <th>Jenis Kelamin</th>
                  <th>Kampus</th>
                  <th>Fakultas</th>
                  <th>Program Studi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($penerima as $index => $applicant)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $applicant->user?->profile?->nama_lengkap ?? $applicant->user?->username ?? '-' }}</td>
                  <td>{{ $applicant->user?->profile?->nim ?? '-' }}</td>
                  <td>{{ $applicant->user?->profile?->jenis_kelamin ?? '-' }}</td>
                  <td>{{ $applicant->user?->profile?->prodi?->fakultas?->kampus?->nama_kampus ?? $scholarship->kampus ?? '-' }}</td>
                  <td>{{ $applicant->user?->profile?->prodi?->fakultas?->nama ?? $applicant->fakultas ?? '-' }}</td>
                  <td>{{ $applicant->user?->profile?->prodi?->nama ?? $applicant->prodi ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                  <td colspan="7" class="text-center">Belum ada penerima beasiswa.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection

@push('script')
<script>
$(document).ready(function() {
  $('#penerimaTable').DataTable({
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
