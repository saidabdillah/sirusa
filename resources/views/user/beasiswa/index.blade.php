@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Daftar Beasiswa</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Daftar Beasiswa</div>
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
        @forelse($scholarships as $scholarship)
          <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="card-title mb-0">{{ $scholarship->nama }}</h5>
                  @if(isset($applications[$scholarship->id]))
                    @if($applications[$scholarship->id] === 'verifikasi')
                      <span class="badge badge-warning">Verifikasi</span>
                    @elseif($applications[$scholarship->id] === 'diterima')
                      <span class="badge badge-success">Diterima</span>
                    @elseif($applications[$scholarship->id] === 'revisi')
                      <span class="badge badge-secondary">Revisi</span>
                    @elseif($applications[$scholarship->id] === 'ditolak')
                      <span class="badge badge-danger">Ditolak</span>
                    @endif
                  @elseif($scholarship->isExpired())
                    <span class="badge badge-danger">Berakhir</span>
                  @else
                    <span class="badge badge-success">Aktif</span>
                  @endif
                </div>
                <p class="text-muted mb-2">{{ $scholarship->kampus }}</p>
                <div class="row mb-2">
                  <div class="col-6">
                    <small class="text-muted">Gelar</small><br>
                    <strong>{{ $scholarship->tingkat_gelar }}</strong>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">IPK Minimal</small><br>
                    <strong>{{ number_format($scholarship->ipk_minimal, 2) }}</strong>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Semester Minimal</small><br>
                    <strong>{{ $scholarship->semester_minimal }}</strong>
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-6">
                    <small class="text-muted">Tunjangan</small><br>
                    <span class="badge badge-{{ $scholarship->cakupan === 'penuh' ? 'success' : 'warning' }}">
                      {{ ucfirst($scholarship->cakupan) }}
                    </span>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Batas Waktu</small><br>
                    <strong class="{{ $scholarship->batas_waktu?->diffInDays(now()) <= 7 ? 'text-danger' : '' }}">
                      {{ $scholarship->batas_waktu?->translatedFormat('d F Y') }}
                    </strong>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <a href="{{ route('user.beasiswa.lihat', $scholarship) }}" class="btn btn-info btn-sm btn-block">
                  <i class="fas fa-eye"></i> Lihat Detail
                </a>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="card">
              <div class="card-body text-center">
                <div class="text-muted">Belum ada beasiswa tersedia</div>
              </div>
            </div>
          </div>
        @endforelse
      </div>
      <div class="d-flex justify-content-center">
        {{ $scholarships->links() }}
      </div>
    </div>
</section>
@endsection