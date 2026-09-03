@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Pengumuman Penerima</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('user.beasiswa.index') }}">Daftar Beasiswa</a></div>
        <div class="breadcrumb-item">Pengumuman</div>
      </div>
    </div>

    <div class="section-body">
      <div class="card">
        <div class="card-header">
          <h4>{{ $scholarship->nama }}</h4>
        </div>
        <div class="card-body">
          <div class="row mb-4">
            <div class="col-md-6">
              <strong>Penyedia:</strong><br>
              {{ $scholarship->kampus }}
            </div>
            <div class="col-md-6">
              <strong>Periode Pengumuman:</strong><br>
              {{ $scholarship->tanggal_pengumuman->translatedFormat('d F Y') }}
              s/d
              {{ $scholarship->tanggal_pengumuman_selesai->translatedFormat('d F Y') }}
            </div>
          </div>

          <h5 class="mb-3">Daftar Penerima Beasiswa</h5>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Lengkap</th>
                  <th>Program Studi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($penerima as $index => $applicant)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $applicant->user?->profile?->nama_lengkap ?? $applicant->user?->username ?? '-' }}</td>
                  <td>{{ $applicant->user?->profile?->prodi?->nama ?? $applicant->prodi ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                  <td colspan="3" class="text-center">Belum ada penerima beasiswa.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection
