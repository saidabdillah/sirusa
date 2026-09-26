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
    @include('dasbor.partials.stat', [
      'icon' => 'fa-award',
      'bg' => 'primary',
      'label' => 'Total Beasiswa',
      'value' => $totalBeasiswa,
      'hint' => $beasiswaAktif.' masih aktif',
    ])
    @include('dasbor.partials.stat', [
      'icon' => 'fa-file-alt',
      'bg' => 'warning',
      'label' => 'Pendaftaran Menunggu',
      'value' => $pendaftarBaru,
      'hint' => 'Belum diputuskan Kesra',
    ])
    @include('dasbor.partials.stat', [
      'icon' => 'fa-user-check',
      'bg' => 'success',
      'label' => 'Diterima',
      'value' => $funnel['diterima'],
    ])
    @include('dasbor.partials.stat', [
      'icon' => 'fa-user-times',
      'bg' => 'danger',
      'label' => 'Ditolak',
      'value' => $funnel['ditolak'],
    ])
  </div>

  <div class="row">
    <div class="col-lg-7">
      @include('dasbor.partials.tahapan', ['stages' => $stages, 'perStage' => $perStage])
    </div>

    <div class="col-lg-5">
      @include('dasbor.partials.funnel', ['funnel' => $funnel])
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <h4>Batas Waktu Pendaftaran</h4>
          <div class="card-header-action">
            <a href="{{ route('admin.beasiswa.index') }}" class="btn btn-primary">Kelola Beasiswa</a>
          </div>
        </div>
        <div class="card-body">
          @forelse($deadline as $beasiswa)
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
            </div>
          @empty
            <div class="text-center text-muted">Tidak ada batas waktu mendatang</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
