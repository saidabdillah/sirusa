@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Detail Beasiswa</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.beasiswa.index') }}">Beasiswa</a></div>
      <div class="breadcrumb-item">Detail</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h4>{{ $scholarship->nama }}</h4>
            <div class="card-header-action">
              @if(auth()->user()->hasMenuAccess('admin.beasiswa.index'))
              <a href="{{ route('admin.beasiswa.ubah', $scholarship) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-edit"></i> Ubah
              </a>
              @endif
            </div>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Kampus:</strong><br>
                {{ $scholarship->kampus }}
              </div>
              <div class="col-md-6">
                <strong>Tingkat Gelar:</strong><br>
                {{ $scholarship->tingkat_gelar }}
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Kuota:</strong><br>
                {{ $scholarship->kuota }} orang
              </div>
              <div class="col-md-6">
                <strong>Status:</strong><br>
                @if($scholarship->status === 'aktif')
                <span class="badge badge-success">Aktif</span>
                @else
                <span class="badge badge-secondary">Non-aktif</span>
                @endif
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>IPK Minimal:</strong><br>
                {{ number_format($scholarship->ipk_minimal, 2) }}
              </div>
              <div class="col-md-6">
                <strong>Semester Minimal:</strong><br>
                {{ $scholarship->semester_minimal }}
              </div>
            </div>
            <div class="mb-3">
              <strong>Periode Pendaftaran:</strong><br>
              <span class="{{ $scholarship->isExpired() ? 'text-danger' : '' }}">
                {{ $scholarship->tanggal_mulai?->translatedFormat('d F Y') }} s.d.
                {{ $scholarship->tanggal_selesai?->translatedFormat('d F Y') }}
              </span>
              @if($scholarship->isExpired())
              <span class="badge badge-danger ml-2">Telah Berakhir</span>
              @endif
            </div>

            {{-- Cakupan beasiswa (fakultas & program studi yang dipilih saat
                 dibuat/diubah). Sumbernya relasi yang sudah di-eager-load
                 `BeasiswaController::show()`, jadi tidak ada query tambahan. --}}
            <div class="mb-3">
              <strong>Program Studi yang Bisa Mendaftar:</strong><br>
              @if($scholarship->fakultas->isEmpty())
                <span class="text-muted">
                  Semua Program Studi di {{ $scholarship->kampus }}. Tidak ada pembatasan program studi untuk beasiswa ini.
                </span>
              @else
                @foreach($scholarship->fakultas as $fakultas)
                  <div class="mt-2">
                    <i class="fas fa-university text-muted"></i> <strong>{{ $fakultas->nama }}</strong>
                    @if($fakultas->prodi->isEmpty())
                      <div class="text-muted ml-4"><small>Seluruh program studi pada fakultas ini</small></div>
                    @else
                      <ul class="mb-0 pl-4">
                        @foreach($fakultas->prodi as $prodi)
                          <li>{{ $prodi->nama }}</li>
                        @endforeach
                      </ul>
                    @endif
                  </div>
                @endforeach
              @endif
            </div>
            <hr>
            <div class="mb-3">
              <strong>Deskripsi:</strong><br>
              {!! nl2br(e($scholarship->deskripsi)) !!}
            </div>
            @if($scholarship->persyaratan)
            <div class="mb-3">
              <strong>Persyaratan:</strong><br>
              {!! nl2br(e($scholarship->persyaratan)) !!}
            </div>
            @endif
          </div>
        </div>
      </div>

<div class="col-lg-4 sticky-sidebar">
        <div class="card">
          <div class="card-header">
            <h4>Pendaftar</h4>
          </div>
          <div class="card-body">
            <div class="text-center mb-3">
              <div class="display-4 font-weight-bold">{{ $applicants->total() }}</div>
              <div class="text-muted">Total Pendaftar</div>
            </div>
            <div class="list-group list-group-flush">
              @forelse($applicants as $applicant)
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                  <div>
                    <div class="font-weight-bold">{{ $applicant->user->profile->nama_lengkap ?? '-' }}</div>
                    <small class="text-muted">{{ $applicant->fakultas ?? '-' }}</small>
                  </div>
                  <div>
                    @if($applicant->status === 'verifikasi')
                    <span class="badge badge-warning">Verifikasi</span>
                    @elseif($applicant->status === 'diterima')
                    <span class="badge badge-success">Diterima</span>
                    @elseif($applicant->status === 'ditolak')
                    <span class="badge badge-danger">Ditolak</span>
                    @endif
                  </div>
                </div>
              </div>
              @empty
              <div class="text-center text-muted py-3">Belum ada pendaftar</div>
              @endforelse
            </div>
            <div class="mt-3">
              {{ $applicants->links() }}
            </div>
          </div></div>
        </div>
      </div>
    </div>
</section>
@endsection
