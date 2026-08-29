@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Detail Beasiswa</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('user.beasiswa.index') }}">Daftar Beasiswa</a></div>
        <div class="breadcrumb-item">Detail</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>{{ $scholarship->nama }}</h4>
            </div>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Kampus Tujuan:</strong><br>
                  {{ $scholarship->kampus }}
                </div>
                <div class="col-md-6">
                  <strong>Sisa Kuota:</strong><br>
                  @php
                    $diterima = $scholarship->pendaftar()->whereIn('status', ['diterima', 'selesai'])->count();
                    $sisa = $scholarship->kuota - $diterima;
                  @endphp
                  <span class="{{ $sisa <= 0 ? 'text-danger' : '' }}">{{ max($sisa, 0) }} orang</span>
                </div>
              </div>
              <div class="row mb-3">
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
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Batas Waktu:</strong><br>
                  <span class="{{ $scholarship->batas_waktu?->diffInDays(now()) <= 7 ? 'text-danger' : '' }}">
                    {{ $scholarship->batas_waktu?->format('d M Y') }}
                  </span>
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
              <h4>Daftar Sekarang</h4>
            </div>
            <div class="card-body text-center">
              @if($scholarship->isExpired())
                <div class="alert alert-danger">
                  <i class="fas fa-exclamation-triangle"></i><br>
                  Batas waktu pendaftaran telah berakhir
                </div>
              @elseif($scholarship->status === 'non-aktif')
                <div class="alert alert-warning">
                  <i class="fas fa-pause-circle"></i><br>
                  Beasiswa ini sedang tidak aktif
                </div>
              @elseif($application)
                @if($application->status === 'verifikasi')
                  <div class="mb-3">
                    <div class="text-muted">Batas waktu pendaftaran</div>
                    <div class="font-weight-bold {{ $scholarship->batas_waktu?->diffInDays(now()) <= 7 ? 'text-danger' : '' }}">
                      {{ $scholarship->batas_waktu?->diffForHumans() }}
                    </div>
                  </div>
                  <button type="button" class="btn btn-secondary btn-lg btn-block" disabled>
                    <i class="fas fa-clock"></i> Menunggu Verifikasi
                  </button>
                @elseif($application->status === 'diterima')
                  <div class="alert alert-info">
                    <i class="fas fa-check-circle"></i><br>
                    <strong>Pendaftaran Tahap 1 Diterima!</strong><br>
                    Silakan lengkapi berkas Tahap 2.
                  </div>
                  <a href="{{ route('user.pendaftaran.melengkapi', $application) }}" class="btn btn-info btn-lg btn-block">
                    <i class="fas fa-upload"></i> Unggah Berkas Tahap 2
                  </a>
                @elseif($application->status === 'selesai')
                  <div class="alert alert-success">
                    <i class="fas fa-check-double"></i><br>
                    <strong>Selamat! Pendaftaran Anda Diterima.</strong><br>
                    Beasiswa ini telah berhasil Anda peroleh.
                  </div>
                @elseif($application->status === 'revisi')
                  <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i><br>
                    <strong>Pendaftaran Perlu Revisi.</strong><br>
                    Silakan perbaiki data Anda.
                  </div>
                  <a href="{{ route('user.pendaftaran.lengkapi', $application) }}" class="btn btn-warning btn-lg btn-block">
                    <i class="fas fa-edit"></i> Revisi Pendaftaran
                  </a>
                @elseif($application->status === 'ditolak')
                  <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i><br>
                    <strong>Pendaftaran Ditolak.</strong><br>
                    Silakan hubungi admin untuk informasi lebih lanjut.
                  </div>
                @endif
              @elseif(! $profileComplete)
                <div class="alert alert-warning">
                  <i class="fas fa-exclamation-triangle"></i><br>
                  <strong>Profil belum lengkap.</strong><br>
                  Silakan lengkapi profil Anda terlebih dahulu sebelum mendaftar beasiswa.
                </div>
                <a href="{{ route('profile') }}" class="btn btn-warning btn-lg btn-block">
                  <i class="fas fa-user-edit"></i> Lengkapi Profil
                </a>
              @else
                <div class="mb-3">
                  <div class="text-muted">Batas waktu pendaftaran</div>
                  <div class="font-weight-bold {{ $scholarship->batas_waktu?->diffInDays(now()) <= 7 ? 'text-danger' : '' }}">
                    {{ $scholarship->batas_waktu?->diffForHumans() }}
                  </div>
                </div>
                <a href="{{ route('user.pendaftaran.buat', ['beasiswa_id' => $scholarship->id]) }}" class="btn btn-primary btn-lg btn-block">
                  <i class="fas fa-paper-plane"></i> Daftar Sekarang
                </a>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection

@push('script')
  @if($application && $application->status === 'verifikasi')
    <script>
      Swal.fire({
        icon: 'info',
        title: 'Sudah Terdaftar',
        text: 'Anda sudah mendaftar beasiswa ini. Menunggu verifikasi.',
        confirmButtonText: 'Mengerti'
      });
    </script>
  @elseif($application && $application->status === 'diterima')
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Pendaftaran Diterima!',
        text: 'Selamat! Pendaftaran Anda diterima tahap 1. Silakan lengkapi berkas tahap 2.',
        confirmButtonText: 'Mengerti'
      });
    </script>
  @elseif($application && $application->status === 'selesai')
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Selamat!',
        text: 'Pendaftaran Anda telah diterima sepenuhnya.',
        confirmButtonText: 'Mengerti'
      });
    </script>
  @elseif($application && $application->status === 'revisi')
    <script>
      Swal.fire({
        icon: 'warning',
        title: 'Perlu Revisi',
        text: 'Pendaftaran Anda perlu diperbaiki. Silakan revisi data Anda.',
        confirmButtonText: 'Mengerti'
      });
    </script>
  @elseif($application && $application->status === 'ditolak')
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Pendaftaran Ditolak',
        text: 'Pendaftaran Anda tidak diterima. Silakan hubungi admin untuk informasi lebih lanjut.',
        confirmButtonText: 'Mengerti'
      });
    </script>
  @endif
@endpush