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

    @unless($kampusId)
      <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        Beasiswa hanya bisa dilihat setelah Program Studi pada profil Anda terisi, karena setiap beasiswa diperuntukkan untuk satu kampus.
        <a href="{{ route('profile') }}" class="alert-link">Lengkapi profil sekarang</a>.
      </div>
    @endunless

    <div class="section-body">
      <div class="row">
        @forelse($scholarships as $scholarship)
          <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                  <h5 class="card-title mb-0">{{ $scholarship->nama }}</h5>
                  @if(isset($applications[$scholarship->id]) && $applications[$scholarship->id] !== 'dibatalkan')
                    @switch($applications[$scholarship->id])
                      @case('verifikasi')
                        <span class="badge badge-warning">Verifikasi</span>
                        @break
                      @case('diterima')
                        <span class="badge badge-success">Diterima</span>
                        @break
                      @default
                        <span class="badge badge-danger">Ditolak</span>
                    @endswitch
                  @elseif($scholarship->isExpired())
                    <span class="badge badge-danger">Berakhir</span>
                  @elseif($scholarship->sisaKuota() === 0)
                    <span class="badge badge-secondary">Kuota Penuh</span>
                  @else
                    <span class="badge badge-success">Aktif</span>
                  @endif
                </div>
                <p class="text-muted mb-2">{{ $scholarship->kampus }}</p>
                <p class="mb-2">
                  <i class="fas fa-layer-group text-muted"></i>
                  <small class="text-muted">{{ $scholarship->cakupanLabel() }}</small>
                </p>
                @if($scholarship->deskripsi)
                  <p class="mb-2">{{ \Illuminate\Support\Str::limit($scholarship->deskripsi, 120) }}</p>
                @endif
                <div class="row mb-2">
                  <div class="col-6">
                    <small class="text-muted">Gelar</small><br>
                    <strong>{{ $scholarship->tingkat_gelar }}</strong>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">IPK Minimal</small><br>
                    <strong>{{ number_format($scholarship->ipk_minimal, 2) }}</strong>
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-6">
                    <small class="text-muted">Semester Minimal</small><br>
                    <strong>{{ $scholarship->semester_minimal }}</strong>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Sisa Kuota</small><br>
                    <strong class="{{ $scholarship->sisaKuota() === 0 ? 'text-danger' : '' }}">
                      {{ $scholarship->sisaKuota() }} / {{ $scholarship->kuota }}
                    </strong>
                  </div>
                </div>
                <div class="row">
                  <div class="col-12">
                    <small class="text-muted">Periode Pendaftaran</small><br>
                    <strong class="{{ $scholarship->tanggal_selesai?->diffInDays(now()) <= 7 ? 'text-danger' : '' }}">
                      {{ $scholarship->tanggal_mulai?->translatedFormat('d M Y') }} – {{ $scholarship->tanggal_selesai?->translatedFormat('d M Y') }}
                    </strong>
                  </div>
                </div>
                <div class="mt-auto pt-3">
                  <a href="{{ route('user.beasiswa.lihat', $scholarship) }}" class="btn btn-info btn-sm btn-block">
                    <i class="fas fa-eye"></i> Lihat Detail
                  </a>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="card">
              <div class="card-body text-center">
                <div class="text-muted">
                  @if($kampusId)
                    Belum ada beasiswa tersedia untuk kampus Anda saat ini.
                  @else
                    Lengkapi Program Studi pada profil Anda untuk melihat daftar beasiswa.
                  @endif
                </div>
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
