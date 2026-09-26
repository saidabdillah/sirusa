@extends('layouts.app')

@php
  use App\Models\UserProfile;
  use App\Support\VerifikasiAntrean;

  $routeIndex = 'admin.'.UserProfile::verifRoutePrefixes()[$stage].'.index';
  $routeShow = 'admin.'.UserProfile::verifRoutePrefixes()[$stage].'.lihat';
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Dasbor {{ $stageLabel }}</h1>
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
    @include('dasbor.partials.stat', [
      'icon' => 'fa-inbox',
      'bg' => 'warning',
      'label' => 'Menunggu Tindakan',
      'value' => $ringkasan['menunggu'],
    ])
    @include('dasbor.partials.stat', [
      'icon' => 'fa-pen',
      'bg' => 'info',
      'label' => 'Perlu Perbaikan',
      'value' => $ringkasan['revisi'],
    ])
    @include('dasbor.partials.stat', [
      'icon' => 'fa-check-circle',
      'bg' => 'success',
      'label' => 'Disetujui',
      'value' => $ringkasan['setuju'],
    ])
    @include('dasbor.partials.stat', [
      'icon' => 'fa-times-circle',
      'bg' => 'danger',
      'label' => 'Ditolak',
      'value' => $ringkasan['tolak'],
    ])
  </div>

  <div class="row">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header">
          <h4>Menunggu Tindakan Anda</h4>
          <div class="card-header-action">
            <a href="{{ route($routeIndex, ['filter' => 'menunggu']) }}" class="btn btn-primary">Lihat Semua</a>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Program Studi</th>
                  <th>Kampus</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse($antrean as $user)
                  <tr>
                    <td>{{ $user->profile->nama_lengkap ?: $user->username }}</td>
                    <td>{{ $user->profile->prodi?->nama ?? '-' }}</td>
                    <td>{{ $user->profile->prodi?->fakultas?->kampus->nama ?? '-' }}</td>
                    <td class="text-right">
                      <a href="{{ route($routeShow, $user) }}" class="btn btn-sm btn-outline-primary">Periksa</a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted">Tidak ada yang menunggu pada tahap ini.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      @if($stage === 'kesra')
        <div class="card">
          <div class="card-header">
            <h4>Pendaftaran Menunggu Putusan</h4>
            <div class="card-header-action">
              <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-primary">Semua Pendaftar</a>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Mahasiswa</th>
                    <th>Beasiswa</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($pendaftaran->take(8) as $applicant)
                    <tr>
                      <td>{{ $applicant->user?->profile?->nama_lengkap ?: $applicant->user?->username }}</td>
                      <td>{{ $applicant->beasiswa?->nama }}</td>
                      <td class="text-right">
                        <a href="{{ route('admin.kesra.lihat', $applicant->user_id) }}" class="btn btn-sm btn-outline-primary">Putuskan</a>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="3" class="text-center text-muted">Tidak ada pendaftaran yang menunggu putusan.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @else
        <div class="card">
          <div class="card-header">
            <h4>Tahapan Berikutnya</h4>
          </div>
          <div class="card-body">
            <p class="text-muted mb-3">
              Urutan verifikasi: Capil &rarr; Kampus &rarr; Kesra. Mahasiswa baru muncul di tahap
              berikutnya setelah tahap ini disetujui.
            </p>
            @foreach(UserProfile::verifStageOrder() as $urutan)
              @php $antreanTahap = new VerifikasiAntrean($urutan); @endphp
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="{{ $urutan === $stage ? 'font-weight-bold' : 'text-muted' }}">{{ $antreanTahap->label() }}</span>
                @if($urutan === $stage)
                  <span class="badge badge-primary">Tahap Anda</span>
                @elseif(in_array($urutan, $stages, true))
                  <a href="{{ route($antreanTahap->routeName()) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                @else
                  <span class="badge badge-secondary">Tanpa akses</span>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>
</section>
@endsection
