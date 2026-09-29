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
              {{-- Ikon dan label diambil dari `Applicant`, bukan ditulis ulang
                   per status di sini. `@switch` per status di view adalah tempat
                   kedua label "Diterima"/"Disetujui" pernah berubah pisahi, dan
                   `@break` yang menempel setelah teks bisa gagal dikompilasi
                   lalu tercetak literal. Satu peta di model menutup keduanya. --}}
              <span class="badge badge-{{ $applicant->statusBadge() }} p-2">
                <i class="fas {{ $applicant->statusIcon() }}"></i> {{ $applicant->statusLabel() }}
              </span>
            </div>
            @if($applicant->diputuskan_at)
            <div class="text-left small text-muted">
              <div class="mb-0">
                <strong>Tanggal:</strong> {{ $applicant->diputuskan_at->translatedFormat('d F Y H:i') }}
                @if($applicant->decidedByName())
                  &middot; <strong>Diputuskan oleh:</strong> {{ $applicant->decidedByName() }}
                @endif
              </div>
            </div>
            @endif
            @if($applicant->catatan)
            <div class="text-left">
              <strong>Catatan Admin:</strong>
              <p class="mb-0">{!! nl2br(e($applicant->catatan)) !!}</p>
            </div>
            @endif
            @if($applicant->canBeCancelled())
              {{-- Konfirmasi pakai `.btn-confirm-toggle` yang sudah ditangani
                   global di `custom.js`, bukan `onclick="return confirm()"`.
                   `confirm()` native milik browser tidak seragam dengan
                   SweetAlert2 di halaman lain dan tampilannya berbeda
                   tiap browser. --}}
              <form action="{{ route('user.pendaftaran.batal', $applicant) }}" method="POST" data-ajax-form class="mt-3">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-outline-danger btn-block btn-sm btn-confirm-toggle"
                        data-confirm-title="Batalkan Pendaftaran?"
                        data-confirm-text="Pendaftaran Beasiswa {{ $applicant->beasiswa->nama }} akan dibatalkan dan Anda bisa mendaftar beasiswa lain. Lanjutkan?"
                        data-confirm-icon="warning"
                        data-confirm-color="#e74c3c"
                        data-confirm-button="Ya, Batalkan!"
                        data-loading-text="Membatalkan...">
                  <i class="fas fa-times"></i> Batalkan Pendaftaran
                </button>
              </form>
            @endif
          </div>
        </div>

        {{-- Kartu status per tahap dihapus. Yang ditampilkan hanya catatan
             verifikator, karena itu umpan balik yang harus ditindaklanjuti
             mahasiswa; status tahapnya sendiri tidak ditampilkan. --}}
        @if($profile->catatan_catpil || $profile->catatan_kampus || $profile->catatan_kesra)
        <div class="card">
          <div class="card-header">
            <h4>Catatan Verifikator</h4>
          </div>
          <div class="card-body">
            @if($profile->catatan_catpil)
            <small class="text-muted d-block mb-1"><strong>Catatan Catpil:</strong> {{ $profile->catatan_catpil }}</small>
            @endif
            @if($profile->catatan_kampus)
            <small class="text-muted d-block mb-1"><strong>Catatan Kampus:</strong> {{ $profile->catatan_kampus }}</small>
            @endif
            @if($profile->catatan_kesra)
            <small class="text-muted d-block"><strong>Catatan Kesra:</strong> {{ $profile->catatan_kesra }}</small>
            @endif
          </div>
        </div>
        @endif

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