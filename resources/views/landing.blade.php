@extends('layouts.public')

@section('title', 'SIRUSA — Sistem Informasi Beasiswa')

@section('content')
{{-- Navbar --}}
<nav class="navbar navbar-expand-lg navbar-light bg-white landing-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand font-weight-bold" href="{{ route('landing') }}">
      <i class="fas fa-graduation-cap text-primary mr-2"></i>SIRUSA
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#landingNav"
      aria-controls="landingNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="landingNav">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item">
          <a class="nav-link" href="{{ route('landing') }}"><i class="fas fa-home mr-1"></i>Beranda</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#beasiswa"><i class="fas fa-award mr-1"></i>Beasiswa</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#kampus"><i class="fas fa-university mr-1"></i>Kampus</a>
        </li>
      </ul>
      <ul class="navbar-nav align-items-lg-center">
        <li class="nav-item mr-lg-2 mb-2 mb-lg-0">
          <a class="btn btn-outline-primary btn-block" href="{{ route('login') }}">
            <i class="fas fa-sign-in-alt mr-1"></i>Masuk
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-primary btn-block" href="{{ route('register') }}">
            <i class="fas fa-user-plus mr-1"></i>Daftar
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

{{-- Hero --}}
<section class="landing-hero text-white py-5">
  <div class="container py-4">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <span class="badge badge-light mb-3 px-3 py-2"><i class="fas fa-award mr-1"></i>Beasiswa untuk Masa
          Depanmu</span>
        <h1 class="display-4 font-weight-bold mb-3">Temukan &amp; Daftar Beasiswa Impianmu</h1>
        <p class="lead mb-4">Gabung dengan ribuan mahasiswa dan dapatkan beasiswa dari berbagai kampus. Proses
          pendaftaran transparan, gratis, dan tanpa ribet.</p>
        <div class="d-flex flex-wrap">
          <a href="{{ route('register') }}" class="btn btn-light btn-lg mr-3 mb-2">
            <i class="fas fa-rocket mr-2"></i>Daftar Sekarang
          </a>
          <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg mb-2">
            <i class="fas fa-sign-in-alt mr-2"></i>Masuk
          </a>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block">
        <svg viewBox="0 0 400 340" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-100" aria-hidden="true">
          <circle cx="200" cy="170" r="150" fill="#ffffff" fill-opacity="0.12"/>
          <circle cx="200" cy="170" r="115" fill="#ffffff" fill-opacity="0.10"/>
          <ellipse cx="200" cy="285" rx="150" ry="18" fill="#0b2440" fill-opacity="0.35"/>
          <rect x="120" y="120" width="160" height="118" rx="14" fill="#ffffff"/>
          <rect x="120" y="132" width="160" height="10" rx="5" fill="#346CB0"/>
          <rect x="120" y="156" width="120" height="8" rx="4" fill="#d7e2f5"/>
          <rect x="120" y="172" width="140" height="8" rx="4" fill="#d7e2f5"/>
          <rect x="120" y="188" width="90" height="8" rx="4" fill="#d7e2f5"/>
          <rect x="120" y="210" width="70" height="18" rx="9" fill="#346CB0"/>
          <rect x="200" y="210" width="70" height="18" rx="9" fill="#eaf1fb" stroke="#346CB0" stroke-width="1.5"/>
          <path d="M190 92h20v28h-20z" fill="#5db0e6"/>
          <circle cx="305" cy="105" r="26" fill="#7cc4f2"/>
          <path d="M290 105h30M305 90v30" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
          <circle cx="120" cy="100" r="20" fill="#ffca3a"/>
          <path d="M120 94l4 8 8 1-6 6 1 8-7-4-7 4 1-8-6-6 8-1z" fill="#ffffff"/>
        </svg>
      </div>
    </div>
  </div>
</section>

{{-- Statistik --}}
<section class="py-4 bg-white">
  <div class="container">
    <div class="row">
      <div class="col-6 col-md mb-3">
        <div class="text-center">
          <div class="stat-count">{{ $totalBeasiswa }}</div>
          <div class="text-muted">Total Beasiswa</div>
        </div>
      </div>
      <div class="col-6 col-md mb-3">
        <div class="text-center">
          <div class="stat-count">{{ $beasiswaAktif }}</div>
          <div class="text-muted">Beasiswa Aktif</div>
        </div>
      </div>
      <div class="col-6 col-md mb-3">
        <div class="text-center">
          <div class="stat-count">{{ $totalPendaftar }}</div>
          <div class="text-muted">Total Pendaftar</div>
        </div>
      </div>
      <div class="col-6 col-md mb-3">
        <div class="text-center">
          <div class="stat-count">{{ $totalSelesai }}</div>
          <div class="text-muted">Penerima Beasiswa</div>
        </div>
      </div>
      <div class="col-6 col-md mb-3">
        <div class="text-center">
          <div class="stat-count">{{ $totalKampus }}</div>
          <div class="text-muted">Kampus Mitra</div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- Beasiswa Populer --}}
<section id="beasiswa" class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="font-weight-bold">Beasiswa Populer</h2>
      <p class="text-muted">Beasiswa terbaru yang sedang dibuka</p>
    </div>
    @if($beasiswaPopuler->isEmpty())
    <div class="alert alert-info text-center mb-0">Belum ada beasiswa aktif. Silakan kembali lagi nanti.</div>
    @else
    <div class="row">
      @foreach($beasiswaPopuler as $beasiswa)
      <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <h6 class="mb-0 font-weight-bold text-truncate">{{ $beasiswa->nama }}</h6>
          </div>
          <div class="card-body">
            <p class="mb-1"><i class="fas fa-university text-primary mr-1"></i>{{ $beasiswa->kampus }}</p>
            <p class="mb-1"><i class="fas fa-layer-group text-primary mr-1"></i>{{ $beasiswa->tingkat_gelar }}</p>
            <p class="mb-0"><i class="fas fa-calendar-alt text-primary mr-1"></i>Batas: {{ $beasiswa->batas_waktu ?
              $beasiswa->batas_waktu->translatedFormat('d M Y') : '-' }}</p>
          </div>
          <div class="card-footer bg-white text-center">
            <a href="{{ route('register') }}" class="btn btn-sm btn-primary btn-block">Apply Sekarang</a>
          </div>
        </div>
      </div>
      @endforeach
    </div>
    @endif
  </div>
</section>

{{-- Kampus Mitra --}}
<section id="kampus" class="py-5 bg-white">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="font-weight-bold">Kampus Mitra</h2>
      <p class="text-muted">Beasiswa tersedia untuk berbagai kampus</p>
    </div>
    @if($kampusMitra->isEmpty())
    <div class="alert alert-info text-center mb-0">Belum ada kampus mitra.</div>
    @else
    <div class="kampus-marquee rounded py-3 px-2">
      <div class="kampus-marquee__track" id="kampusTrack">
        @foreach($kampusMitra as $kampus)
        <span class="mx-4">{{ $kampus }}</span>
        @endforeach
      </div>
    </div>
    @endif
  </div>
</section>

{{-- CTA Akhir --}}
<section class="py-5 bg-light">
  <div class="container">
    <div class="landing-cta rounded text-white text-center p-5">
      <h2 class="font-weight-bold mb-3">Siap Mendapatkan Beasiswa?</h2>
      <p class="lead mb-4">Daftar sekarang, gratis dan prosesnya mudah.</p>
      <a href="{{ route('register') }}" class="btn btn-light btn-lg">
        <i class="fas fa-rocket mr-2"></i>Daftar Sekarang
      </a>
    </div>
  </div>
</section>

{{-- Footer --}}
<footer class="bg-dark text-white pt-5 pb-3">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-4">
        <h5 class="font-weight-bold">
          <i class="fas fa-graduation-cap text-primary mr-2"></i>SIRUSA
        </h5>
        <p class="text-white-50 mb-0">Sistem Informasi Beasiswa — menghubungkan mahasiswa dengan peluang beasiswa dari
          berbagai kampus.</p>
      </div>
      <div class="col-md-4 mb-4">
        <h5 class="font-weight-bold">Navigasi</h5>
        <ul class="list-unstyled">
          <li><a class="text-white-50" href="{{ route('landing') }}"><i class="fas fa-chevron-right mr-1"></i>Beranda</a></li>
          <li><a class="text-white-50" href="{{ route('login') }}"><i class="fas fa-chevron-right mr-1"></i>Masuk</a></li>
          <li><a class="text-white-50" href="{{ route('register') }}"><i class="fas fa-chevron-right mr-1"></i>Daftar</a></li>
        </ul>
      </div>
      <div class="col-md-4 mb-4">
        <h5 class="font-weight-bold">Kontak</h5>
        <ul class="list-unstyled text-white-50">
          <li><i class="fas fa-envelope mr-2"></i>info@sirusa.id</li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary">
    <p class="text-center text-white-50 mb-0">Hak Cipta &copy; {{ date('Y') }} SIRUSA &mdash; Sistem Informasi
      Beasiswa</p>
  </div>
</footer>
@endsection

@push('script')
@if(!$kampusMitra->isEmpty())
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var track = document.getElementById('kampusTrack');
    if (!track) {
      return;
    }
    var container = track.parentElement;
    var base = track.innerHTML;
    var copies = 1;

    while (track.scrollWidth < container.clientWidth * 2) {
      track.insertAdjacentHTML('beforeend', base);
      copies++;
    }

    if (copies % 2 !== 0) {
      track.insertAdjacentHTML('beforeend', base);
      copies++;
    }
  });
</script>
@endif
@endpush