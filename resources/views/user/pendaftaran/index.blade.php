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
          <div class="card-header">
            <h4>Riwayat Pendaftaran</h4>
          </div>
          <div class="card-body">
            @if($applicants->isEmpty())
              <div class="text-center text-muted mb-3">
                Anda belum punya pendaftaran beasiswa. <a href="{{ route('user.beasiswa.index') }}">Lihat daftar beasiswa</a>.
              </div>
            @endif

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
                        <span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span>
                        @if($applicant->isActive())
                          <button type="button" class="btn btn-outline-secondary btn-sm ml-1" data-toggle="modal" data-target="#batal-{{ $applicant->id }}">
                            <i class="fas fa-times"></i> Batalkan
                          </button>
                        @endif
                      </td>
                      <td>{{ $applicant->created_at->translatedFormat('d M Y H:i') }}</td>
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

  {{-- Modal diletakkan di luar tabel: DataTables memindahkan <tr> saat pagination,
       sehingga modal di dalam <tbody> akan ikut terduplikasi dan rusak. --}}
  @foreach($applicants->where('status', 'verifikasi') as $applicant)
    <div class="modal fade" id="batal-{{ $applicant->id }}" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Batalkan Pendaftaran</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            Batalkan pendaftaran Beasiswa <strong>{{ $applicant->beasiswa->nama }}</strong>?
            Pendaftaran ini akan berstatus <strong>Dibatalkan</strong> dan Anda bisa mendaftar beasiswa lain.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <form action="{{ route('user.pendaftaran.batal', $applicant) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger">Ya, Batalkan</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  @endforeach
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
