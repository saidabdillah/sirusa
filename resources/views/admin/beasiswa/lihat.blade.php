@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Detail Beasiswa</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.beasiswa.index') }}">Beasiswa</a></div>
        <div class="breadcrumb-item">Detail</div>
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
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>{{ $scholarship->nama }}</h4>
              <div class="card-header-action">
                @if(auth()->user()->hasRole('admin'))
                  <a href="{{ route('admin.beasiswa.ubah', $scholarship) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> Ubah
                  </a>
                @endif
              </div>
            </div>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Kampus Tujuan:</strong><br>
                  {{ $scholarship->kampus }}
                </div>
                <div class="col-md-6">
                  <strong>Tingkat Gelar:</strong><br>
                  {{ $scholarship->tingkat_gelar }}
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Tunjangan:</strong><br>
                  <span class="badge badge-{{ $scholarship->cakupan === 'penuh' ? 'success' : 'warning' }}">
                    {{ ucfirst($scholarship->cakupan) }}
                  </span>
                </div>
                <div class="col-md-6">
                  <strong>Kuota:</strong><br>
                  {{ $scholarship->kuota }} orang
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Batas Waktu:</strong><br>
                  <span class="{{ $scholarship->isExpired() ? 'text-danger' : '' }}">
                    {{ $scholarship->batas_waktu?->format('d M Y') }}
                  </span>
                  @if($scholarship->isExpired())
                    <span class="badge badge-danger ml-2">Telah Berakhir</span>
                  @endif
                </div>
                <div class="col-md-6">
                  <strong>Status:</strong><br>
                  @if($scholarship->status === 'aktif')
                    <span class="badge badge-success">Aktif</span>
                  @else
                    <span class="badge badge-secondary">Non-aktif</span>
                  @endif
                </div>
              </div>
              <hr>
              <div class="mb-3">
                <strong>Deskripsi:</strong><br>
                {!! nl2br(e($scholarship->deskripsi)) !!}
              </div>
              @if($scholarship->persyaratan)
                <div class="mb-3">
                  <strong>Persyaratan:</strong><br>
                  {!! nl2br(e($scholarship->persyaratan)) !!}
                </div>
              @endif



            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h4>Pendaftar</h4>
            </div>
            <div class="card-body">
              <div class="text-center mb-3">
                <div class="display-4 font-weight-bold">{{ $applicants->total() }}</div>
                <div class="text-muted">Total Pendaftar</div>
              </div>
              <div class="list-group list-group-flush">
                @forelse($applicants as $applicant)
                  <a href="{{ route('admin.pendaftar.lihat', $applicant) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <div class="font-weight-bold">{{ $applicant->user->profile->nama_lengkap ?? '-' }}</div>
                        <small class="text-muted">{{ $applicant->fakultas ?? '-' }}</small>
                      </div>
                      <div>
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
                      </div>
                    </div>
                  </a>
                @empty
                  <div class="text-center text-muted py-3">Belum ada pendaftar</div>
                @endforelse
              </div>
              <div class="mt-3">
                {{ $applicants->links() }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection