@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Dasbor</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active">Dasbor</div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
  @endif

  <div class="row">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header">
          <h4>Status Profil Saya</h4>
          @if($profile)
            <div class="card-header-action">
              <span class="text-muted">{{ $profile->nama_lengkap ?: auth()->user()->username }}</span>
            </div>
          @endif
          <div class="card-header-action ml-3">
            <a href="{{ route('profile') }}" class="btn btn-primary">Buka Profil</a>
          </div>
        </div>
        <div class="card-body">
          @if($missingFields)
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle"></i>
              <strong>Profil belum lengkap.</strong> Isi dulu: {{ implode(', ', $missingFields) }}.
            </div>
          @endif

          @if($profile)
            <div class="mb-3">
              <div class="text-muted">Status Verifikasi</div>
              @php
                $badge = match ($profile->verifStatus()) {
                    'terverifikasi' => 'success',
                    'ditolak' => 'danger',
                    default => 'warning',
                };
              @endphp
              <span class="badge badge-{{ $badge }} p-2">{{ ucfirst($profile->verifStatus()) }}</span>
            </div>

            <div class="mb-3">
              <div class="text-muted mb-2">Progres Verifikasi</div>
              @foreach($profile->verifStageLabels() as $stage => $label)
                @php
                  $status = $profile->{'verif_'.$stage};
                  $warna = match ($status) { 'setuju' => 'success', 'revisi', 'tolak' => 'danger', default => 'warning' };
                  $teks = match ($status) { 'setuju' => 'Disetujui', 'revisi' => 'Perlu Perbaikan', 'tolak' => 'Ditolak', default => 'Menunggu' };
                @endphp
                <div class="d-flex justify-content-between align-items-center py-1">
                  <span>{{ $label }}</span>
                  <span class="badge badge-{{ $warna }}">{{ $teks }}</span>
                </div>
                @if($profile->{'catatan_'.$stage})
                  <small class="text-muted d-block mb-2"><strong>Catatan:</strong> {{ $profile->{'catatan_'.$stage} }}</small>
                @endif
              @endforeach
            </div>
          @else
            <div class="text-muted">Anda belum membuat profil. <a href="{{ route('profile') }}">Buat profil sekarang</a>.</div>
          @endif
        </div>
      </div>

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
                    <td><span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span></td>
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

    <div class="col-lg-5">
      @if($activeApplication)
        <div class="card">
          <div class="card-header">
            <h4>Pendaftaran Berjalan</h4>
          </div>
          <div class="card-body">
            <div class="font-weight-bold">{{ $activeApplication->beasiswa?->nama }}</div>
            <p class="text-muted mb-2">
              @if($activeApplication->status === 'diterima')
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
      @endif

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

          @forelse($beasiswaTersedia as $beasiswa)
            <div class="d-flex align-items-center mb-3">
              <div class="mr-3">
                <div class="badge badge-{{ $beasiswa->tanggal_selesai?->diffInDays(now()) <= 7 ? 'danger' : 'primary' }}">
                  {{ $beasiswa->tanggal_selesai?->diffForHumans() }}
                </div>
              </div>
              <div class="flex-grow-1">
                <div class="font-weight-bold">{{ $beasiswa->nama }}</div>
                <div class="text-small text-muted">{{ $beasiswa->kampus }} &middot; sisa kuota {{ $beasiswa->sisaKuota() }}</div>
              </div>
              <a href="{{ route('user.beasiswa.lihat', $beasiswa) }}" class="btn btn-sm btn-outline-primary">Lihat</a>
            </div>
          @empty
            <div class="text-center text-muted">
              @if($kampusId)
                Belum ada beasiswa terbuka untuk kampus Anda.
              @endif
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
