@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Pendaftaran Saya</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Pendaftaran Saya</div>
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
              <table class="table table-striped" id="myApplicantTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Beasiswa</th>
                    <th>Penyedia</th>
                    <th>IPK</th>
                    <th>Status</th>
                    <th>Tanggal Daftar</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($applicants as $applicant)
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ $applicant->beasiswa->nama }}</td>
                      <td>{{ $applicant->beasiswa->kampus }}</td>
                      <td>{{ $applicant->ipk }}</td>
                      <td>
                        @if($applicant->status === 'verifikasi')
                          <span class="badge badge-warning">Verifikasi</span>
                        @elseif($applicant->status === 'diterima')
                          <span class="badge badge-info">Diterima Tahap 1</span>
                        @elseif($applicant->status === 'selesai')
                          <span class="badge badge-success">Selesai</span>
                        @elseif($applicant->status === 'revisi')
                          <span class="badge badge-secondary">Perlu Revisi</span>
                        @elseif($applicant->status === 'ditolak')
                          <span class="badge badge-danger">Ditolak</span>
                        @endif
                      </td>
                      <td>{{ $applicant->created_at->translatedFormat('d F Y H:i') }}</td>
                      <td>
                        <a href="{{ route('user.pendaftaran.lihat', $applicant) }}" class="btn btn-info btn-sm">
                          <i class="fas fa-eye"></i>
                        </a>
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
  $('#myApplicantTable').DataTable({
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
