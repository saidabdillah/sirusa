@extends('layouts.app')

@php
  use App\Models\UserProfile;

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

  {{-- Label, ikon, dan warnanya berasal dari `VerifikasiAntrean::ringkasanColumns()`
       supaya stat card ini dan tabel "Sebaran Antrean per Tahap" memakai definisi
       status yang sama. --}}
  <div class="row">
    @foreach($ringkasanColumns as $column)
      @include('dasbor.partials.stat', [
        'icon' => $column['icon'],
        'bg' => $column['bg'],
        'label' => $column['label'],
        'value' => $ringkasan[$column['key']] ?? 0,
      ])
    @endforeach
  </div>

  <div class="row">
    {{-- Kesra hanya punya satu daftar kerja: pendaftaran yang menunggu putusan.
         Dulu ada dua kartu di sini -- satu berdasarkan `verif_kesra` dan satu
         lagi berdasarkan `pendaftar.status` -- padahal keduanya menunjuk orang
         yang sama sekali, karena Kesra memverifikasi satu pendaftaran per
         mahasiswa. Yang berbasis profil sudah dihapus; `verif_kesra` kini hanya
         penanda bahwa suatu pendaftaran sudah diputuskan. --}}
    @if($stage === 'kesra')
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Pendaftaran Menunggu Putusan</h4>
            <div class="card-header-action">
              <a href="{{ route('admin.kesra.index') }}" class="btn btn-primary">Lihat Semua</a>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Mahasiswa</th>
                    <th>Program Studi</th>
                    <th>Kampus</th>
                    <th>Beasiswa</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($pendaftaran->take(8) as $applicant)
                    @php
                      $profile = $applicant->user?->profile;
                    @endphp
                    <tr>
                      <td>{{ $profile?->nama_lengkap ?: $applicant->user?->username }}</td>
                      <td>{{ $profile?->prodi?->nama ?? '-' }}</td>
                      <td>{{ $profile?->prodi?->fakultas?->kampus?->nama_kampus ?? '-' }}</td>
                      <td>{{ $applicant->beasiswa?->nama }}</td>
                      <td class="text-right">
                        <a href="{{ route('admin.kesra.lihat', $applicant->user_id) }}" class="btn btn-sm btn-outline-primary">Putuskan</a>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted">Tidak ada pendaftaran yang menunggu putusan.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @else
      <div class="col-12">
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
                    @foreach($antreanKolom as $column)
                    <th>{{ $column['label'] }}</th>
                    @endforeach
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($antrean as $user)
                    <tr>
                      <td>{{ $user->profile->nama_lengkap ?: $user->username }}</td>
                      @foreach($antreanKolom as $column)
                      <td>{{ $column['value']($user->profile) }}</td>
                      @endforeach
                      <td class="text-right">
                        <a href="{{ route($routeShow, $user) }}" class="btn btn-sm btn-outline-primary">Periksa</a>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="{{ count($antreanKolom) + 2 }}" class="text-center text-muted">Tidak ada yang menunggu pada tahap ini.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
</section>
@endsection
