@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Detail Pendaftaran</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('user.pendaftaran.index') }}">Pendaftaran Saya</a></div>
      <div class="breadcrumb-item">Detail</div>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
  @endif

  <div class="section-body">
    <div class="row">
      {{-- LEFT COLUMN --}}
      <div class="col-lg-8">
        @include('partials.profil-detail', ['profile' => $profile, 'showDownloadDocs' => false])
      </div>

      {{-- RIGHT COLUMN --}}
      <div class="col-lg-4">
        {{-- Card: Status --}}
        <div class="card">
          <div class="card-header">
            <h4>Status Pendaftaran</h4>
          </div>
          <div class="card-body text-center">
            <div class="mb-3">
              <span class="badge badge-{{ $applicant->statusBadge() }} p-2">
                @switch($applicant->status)
                  @case('verifikasi')<i class="fas fa-clock"></i> Verifikasi@break
                  @case('diterima')<i class="fas fa-check-circle"></i> Diterima@break
                  @case('ditolak')<i class="fas fa-times-circle"></i> Ditolak@break
                  @default<i class="fas fa-ban"></i> {{ $applicant->statusLabel() }}
                @endswitch
              </span>
            </div>
            @if($applicant->catatan)
            <div class="text-left">
              <strong>Catatan Admin:</strong>
              <p class="mb-0">{!! nl2br(e($applicant->catatan)) !!}</p>
            </div>
            @endif
            @if($applicant->canBeCancelled())
              <form action="{{ route('user.pendaftaran.batal', $applicant) }}" method="POST" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-block btn-sm"
                        onclick="return confirm('Batalkan pendaftaran ini? Anda bisa mendaftar beasiswa lain setelahnya.')">
                  <i class="fas fa-times"></i> Batalkan Pendaftaran
                </button>
              </form>
            @endif
          </div>
        </div>

        {{-- Card: Verifikasi Profil --}}
        <div class="card">
          <div class="card-header">
            <h4>Verifikasi Profil</h4>
          </div>
          <div class="card-body">
            @foreach($profile->verifStageLabels() as $stage => $label)
            @php
              $status = $profile->{'verif_'.$stage};
              $badge = match ($status) { 'setuju' => 'success', 'revisi' => 'danger', 'tolak' => 'danger', default => 'warning' };
              $text = match ($status) { 'setuju' => 'Disetujui', 'revisi' => 'Perlu Perbaikan', 'tolak' => 'Ditolak', default => 'Menunggu' };
            @endphp
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>{{ $label }}</span>
              <span class="badge badge-{{ $badge }}">{{ $text }}</span>
            </div>
            @endforeach
            @if($profile->catatan_capil)
            <small class="text-muted d-block mt-2"><strong>Catatan Capil:</strong> {{ $profile->catatan_capil }}</small>
            @endif
            @if($profile->catatan_kampus)
            <small class="text-muted d-block"><strong>Catatan Kampus:</strong> {{ $profile->catatan_kampus }}</small>
            @endif
            @if($profile->catatan_kesra)
            <small class="text-muted d-block"><strong>Catatan Kesra:</strong> {{ $profile->catatan_kesra }}</small>
            @endif
          </div>
        </div>

        {{-- Card: Beasiswa --}}
        <div class="card">
          <div class="card-header">
            <h4>Beasiswa yang Dilamar</h4>
          </div>
          <div class="card-body text-center">
            <div class="mb-3">
              <i class="fas fa-award fa-2x text-primary mb-2"></i>
              <div class="font-weight-bold h5 mb-1">{{ $applicant->beasiswa->nama }}</div>
              <div class="text-muted">{{ $applicant->beasiswa->kampus }}</div>
            </div>
            <hr>
            <div class="row text-center">
              <div class="col-6">
                <div class="text-muted small">Tingkat Gelar</div>
                <div class="font-weight-bold">{{ $applicant->beasiswa->tingkat_gelar }}</div>
              </div>
              <div class="col-6">
                <div class="text-muted small">Periode</div>
                <div class="font-weight-bold">{{ $applicant->beasiswa->tanggal_selesai ?
                  $applicant->beasiswa->tanggal_mulai->translatedFormat('d/m/Y').' – '.$applicant->beasiswa->tanggal_selesai->translatedFormat('d/m/Y') : '-' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection