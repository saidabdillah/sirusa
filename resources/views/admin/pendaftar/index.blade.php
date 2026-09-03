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
            <form method="GET" action="{{ route('admin.pendaftar.index') }}" class="mb-3">
              <div class="form-row align-items-end">
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="status">Status</label>
                  <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="verifikasi" {{ request('status') === 'verifikasi' ? 'selected' : '' }}>Verifikasi</option>
                    <option value="diterima" {{ request('status') === 'diterima' ? 'selected' : '' }}>Diterima</option>
                    <option value="revisi" {{ request('status') === 'revisi' ? 'selected' : '' }}>Revisi</option>
                    <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                  </select>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="beasiswa_id">Beasiswa</label>
                  <select name="beasiswa_id" id="beasiswa_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Beasiswa --</option>
                    @foreach($beasiswas as $b)
                      <option value="{{ $b->id }}" {{ request('beasiswa_id') == $b->id ? 'selected' : '' }}>{{ $b->nama }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  @if(request()->filled('status') || request()->filled('beasiswa_id'))
                    <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-secondary btn-block">
                      <i class="fas fa-redo"></i> Reset
                    </a>
                  @endif
                  @if($applicants->total() > 0)
                    <a href="{{ route('admin.pendaftar.export', request()->only(['status', 'beasiswa_id'])) }}" class="btn btn-success btn-block mt-2">
                      <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                  @endif
                </div>
              </div>
            </form>
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
                          <span class="badge badge-success">Diterima</span>
                        @elseif($applicant->status === 'revisi')
                          <span class="badge badge-secondary">Revisi</span>
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