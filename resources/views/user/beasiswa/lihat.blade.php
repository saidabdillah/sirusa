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
                  <strong>Kampus:</strong><br>
                  {{ $scholarship->kampus }}
                </div>
                <div class="col-md-6">
                  <strong>Sisa Kuota:</strong><br>
                  {{ $scholarship->sisaKuota() }} orang
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Tingkat Gelar:</strong><br>
                  {{ $scholarship->tingkat_gelar }}
                </div>
                <div class="col-md-6">
                  <strong>IPK Minimal:</strong><br>
                  {{ number_format($scholarship->ipk_minimal, 2) }}
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Semester Minimal:</strong><br>
                  {{ $scholarship->semester_minimal }}
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-12">
                  <strong>Periode Pendaftaran:</strong><br>
                  <span class="{{ $scholarship->tanggal_selesai?->diffInDays(now()) <= 7 ? 'text-danger' : '' }}">
                    {{ $scholarship->tanggal_mulai?->translatedFormat('d F Y') }} s.d. {{ $scholarship->tanggal_selesai?->translatedFormat('d F Y') }}
                  </span>
                  @if($scholarship->isExpired())
                  <span class="badge badge-danger ml-2">Telah Berakhir</span>
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
              @elseif($application && ! $application->isCancelled())
                @if($application->status === 'verifikasi')
                  <div class="mb-3">
                    <div class="text-muted">Periode pendaftaran</div>
                    <div class="font-weight-bold {{ $scholarship->tanggal_selesai?->diffInDays(now()) <= 7 ? 'text-danger' : '' }}">
                      {{ $scholarship->tanggal_mulai?->translatedFormat('d M Y') }} s.d. {{ $scholarship->tanggal_selesai?->translatedFormat('d M Y') }}
                    </div>
                  </div>
                  <button type="button" class="btn btn-secondary btn-lg btn-block" disabled>
                    <i class="fas fa-clock"></i> Menunggu Verifikasi
                  </button>
                @elseif($application->status === 'diterima')
                  <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i><br>
                    <strong>Selamat! Anda Diterima.</strong><br>
                    Pendaftaran Anda telah disetujui dan Anda berhak menerima beasiswa ini.
                  </div>
                @elseif($application->status === 'ditolak')
                  <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i><br>
                    <strong>Pendaftaran Ditolak.</strong><br>
                    {{ $application->catatan ? 'Alasan: '.$application->catatan : 'Silakan hubungi admin untuk informasi lebih lanjut.' }}
                  </div>
                  <a href="{{ route('user.beasiswa.index') }}" class="btn btn-primary btn-lg btn-block">
                    <i class="fas fa-search"></i> Cari Beasiswa Lain
                  </a>
                @endif
              @elseif($blocking)
                <div class="alert alert-warning">
                  <i class="fas fa-exclamation-circle"></i><br>
                  @if($blocking->status === 'diterima')
                    <strong>Anda sudah diterima.</strong><br>
                    Setiap mahasiswa hanya boleh menerima satu beasiswa, dan Anda sudah diterima pada {{ $blocking->beasiswa?->nama }}.
                  @else
                    <strong>Sudah ada pendaftaran aktif.</strong><br>
                    Anda sedang memproses pendaftaran pada {{ $blocking->beasiswa?->nama }}. Setiap mahasiswa hanya boleh satu beasiswa.
                  @endif
                </div>
                <a href="{{ route('user.pendaftaran.index') }}" class="btn btn-info btn-lg btn-block">
                  <i class="fas fa-list"></i> Lihat Pendaftaran Saya
                </a>
              @elseif(! $profileComplete)
                <div class="alert alert-warning">
                  <i class="fas fa-exclamation-triangle"></i><br>
                  <strong>Profil belum lengkap.</strong><br>
                  Silakan lengkapi profil Anda terlebih dahulu sebelum mendaftar beasiswa.
                </div>
                <a href="{{ route('profile') }}" class="btn btn-warning btn-lg btn-block">
                  <i class="fas fa-user-edit"></i> Lengkapi Profil
                </a>
              @elseif(! $profileVerified)
                <div class="alert alert-warning">
                  <i class="fas fa-shield-alt"></i><br>
                  <strong>Profil belum terverifikasi.</strong><br>
                  Anda dapat mendaftar setelah data profil diverifikasi oleh pihak terkait.
                </div>
                <a href="{{ route('profile') }}" class="btn btn-info btn-lg btn-block">
                  <i class="fas fa-user-check"></i> Lihat Status Verifikasi
                </a>
              @elseif($eligibilityError)
                <div class="alert alert-warning">
                  <i class="fas fa-exclamation-triangle"></i><br>
                  {{ $eligibilityError }}
                </div>
              @else
                <div class="mb-3">
                  <div class="text-muted">Periode pendaftaran</div>
                  <div class="font-weight-bold {{ $scholarship->tanggal_selesai?->diffInDays(now()) <= 7 ? 'text-danger' : '' }}">
                    {{ $scholarship->tanggal_mulai?->translatedFormat('d M Y') }} s.d. {{ $scholarship->tanggal_selesai?->translatedFormat('d M Y') }}
                  </div>
                </div>
                <a href="{{ route('user.pendaftaran.buat', ['beasiswa_id' => $scholarship->id]) }}" class="btn btn-primary btn-lg btn-block">
                  <i class="fas fa-paper-plane"></i> Ajukan Sekarang
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
        text: 'Selamat! Pendaftaran Anda telah diterima.',
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