@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Daftar Pendaftar</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Pendaftar</div>
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
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Beasiswa</th>
                    <th>Fakultas</th>
                    <th>Prodi</th>
                    <th>IPK</th>
                    <th>Status</th>
                    <th>Aksi</th>
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
                      <td>
                        <a href="{{ route('admin.pendaftar.lihat', $applicant) }}" class="btn btn-info btn-sm">
                          <i class="fas fa-eye"></i>
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            {{ $applicants->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
