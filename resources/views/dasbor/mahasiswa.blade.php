@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Dasbor</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active">Dasbor</div>
    </div>
  </div>

@if($profile)
    {{-- Pengingat kelengkapan profil. Ditaruh di dasbor (bukan hanya di
         halaman Profil) supaya mahasiswa tahu di mana harus mengisi, karena
         halaman itu satu-satunya tempat data dan dokumen diunggah. --}}
    @unless($profileComplete)
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-triangle mr-1"></i>Data profil Anda belum lengkap.</strong><br>
        Lengkapi data dan unggah dokumen pendukung di halaman
        <a href="{{ route('profile') }}" class="alert-link">Profil</a> supaya Anda bisa mendaftar beasiswa.
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span>&times;</span></button>
      </div>
    @endunless

    {{-- Pengingat paket semester untuk PENERIMA beasiswa. Menetap selama masih
         ada paket yang belum lengkap atau diminta perbaikan -- bukan tombol
         untuk ingatan mahasiswa, tetapi syarat kelanjutan beasiswanya. --}}
    @if(! empty($paketSemester))
      @foreach($paketSemester as $paket)
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-triangle mr-1"></i>KRS &amp; KHS Semester {{ $paket['semester'] }} ({{ $paket['beasiswa'] }})</strong><br>
        @if($paket['status'] === 'belum_lengkap')
        {{ implode(', ', $paket['kurang']) }} belum diunggah.
        @else
        Paket semester ini diminta perbaikan oleh Kampus.
        @endif
        @if(! empty($paket['catatan']))
        <br>Catatan: {{ $paket['catatan'] }}
        @endif
        <br>Lengkapi di halaman
        <a href="{{ route('profile') }}" class="alert-link">Profil</a>.
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span>&times;</span></button>
      </div>
      @endforeach
    @endif

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Status Verifikasi</h4>
          </div>
          <div class="card-body">
{{-- Satu blok per tahap. Catpil/Kampus dari `verifStageDecision()`, --}}
            {{-- Kesra dari `$kesraDecision` (turunan baris `pendaftar`) supaya --}}
            {{-- label di dasbor dan di halaman verifikasi tidak bisa berbeda. --}}
            <div class="row">
@foreach($profile->verifStageLabels() as $stage => $stageLabel)
                @php $decision = $stage === 'kesra' ? $kesraDecision : $profile->verifStageDecision($stage); @endphp
                <div class="col-md-4 mb-3 mb-md-0">
                  <div class="text-muted small">{{ $stageLabel }}</div>
                  <span class="badge badge-{{ $decision['badge'] }} mt-1">{{ $decision['label'] }}</span>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="row">
    {{-- Tanpa kartu "Pendaftaran Berjalan" kolom kanan akan kosong, jadi tabel
         "Pendaftaran Saya" melebar penuh saat tidak ada pendaftaran berjalan. --}}
    <div class="{{ $activeApplication ? 'col-lg-7' : 'col-12' }}">
      <div class="card">
        <div class="card-header">
          <h4>Pendaftaran Saya</h4>
          <div class="card-header-action">
            <a href="{{ route('user.pendaftaran.index') }}" class="btn btn-primary">Lihat Semua</a>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>Beasiswa</th>
                  <th>Status</th>
                  <th>Tanggal Daftar</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($applications as $applicant)
                  <tr>
                    <td>{{ $applicant->beasiswa?->nama }}</td>
                    {{-- Versi status yang boleh dilihat mahasiswa; hasil Kesra
                         baru muncul setelah diumumkan serentak. --}}
                    <td><span class="badge badge-{{ $applicant->visibleStatusBadge() }}">{{ $applicant->visibleStatusLabel() }}</span></td>
                    <td>{{ $applicant->created_at->translatedFormat('d M Y') }}</td>
                    <td class="text-right">
                      <a href="{{ route('user.pendaftaran.lihat', $applicant) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted">Belum ada pendaftaran.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    @if($activeApplication)
      <div class="col-lg-5">
        <div class="card">
          <div class="card-header">
            <h4>Pendaftaran Berjalan</h4>
          </div>
          <div class="card-body">
            <div class="font-weight-bold">{{ $activeApplication->beasiswa?->nama }}</div>
            <p class="text-muted mb-2">
              @if($activeApplication->status === 'diterima' && $activeApplication->isAnnounced())
                Selamat, pendaftaran Anda diterima.
              @else
                Pendaftaran sedang diproses. Anda hanya boleh satu pendaftaran aktif.
              @endif
            </p>
            <a href="{{ route('user.pendaftaran.lihat', $activeApplication) }}" class="btn btn-primary btn-block">
              Lihat Detail
            </a>
          </div>
        </div>
      </div>
    @endif
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4>Beasiswa Tersedia</h4>
          <div class="card-header-action">
            <a href="{{ route('user.beasiswa.index') }}" class="btn btn-primary">Lihat Semua</a>
          </div>
        </div>
        <div class="card-body">
          @unless($kampusId)
            <div class="alert alert-warning mb-3">
              Program Studi pada profil Anda belum terisi, jadi daftar beasiswa belum bisa ditampilkan.
            </div>
          @endunless

          {{-- Kartu selebar layar, jadi daftarnya disusun sebagai grid (bukan satu
               blok vertikal). Tombol "Lihat" tetap di dalam card-body sesuai
               konvensi kartu grid. --}}
          <div class="row">
            @forelse($beasiswaTersedia as $beasiswa)
              <div class="col-md-6 col-xl-4 mb-3">
                <div class="card h-100">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="badge badge-{{ $beasiswa->tanggal_selesai?->diffInDays(now()) <= 7 ? 'danger' : 'primary' }}">
                        {{ $beasiswa->tanggal_selesai?->diffForHumans() }}
                      </span>
                      <a href="{{ route('user.beasiswa.lihat', $beasiswa) }}" class="btn btn-sm btn-outline-primary">Lihat</a>
                    </div>
                    <div class="font-weight-bold">{{ $beasiswa->nama }}</div>
                    <div class="text-small text-muted">{{ $beasiswa->kampus }} &middot; sisa kuota {{ $beasiswa->sisaKuota() }}</div>
                  </div>
                </div>
              </div>
            @empty
              <div class="col-12">
                <div class="text-center text-muted">
                  @if($kampusId)
                    Belum ada beasiswa terbuka untuk kampus Anda.
                  @endif
                </div>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
