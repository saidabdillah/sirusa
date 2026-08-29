@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Dasbor</h1>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
    @endif

    @if(auth()->user()->hasRole(['super_admin', 'admin']))
      {{-- ADMIN / SUPER ADMIN DASHBOARD --}}
      <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary">
              <i class="fas fa-users"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Total Pendaftar</h4>
              </div>
              <div class="card-body">
                {{ $totalApplicants }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning">
              <i class="fas fa-clock"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Verifikasi</h4>
              </div>
              <div class="card-body">
                {{ $pendingApplicants }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Diterima</h4>
              </div>
              <div class="card-body">
                {{ $acceptedApplicants }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success">
              <i class="fas fa-check-double"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Selesai</h4>
              </div>
              <div class="card-body">
                {{ $completedApplicants }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary">
              <i class="fas fa-award"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Total Beasiswa</h4>
              </div>
              <div class="card-body">
                {{ $totalScholarships }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Beasiswa Aktif</h4>
              </div>
              <div class="card-body">
                {{ $activeScholarships }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-info">
              <i class="fas fa-bullhorn"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Diumumkan</h4>
              </div>
              <div class="card-body">
                {{ $announcedScholarships }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Dibayarkan</h4>
              </div>
              <div class="card-body">
                {{ $paidScholarships }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-6 col-md-12 col-12">
          <div class="card">
            <div class="card-header">
              <h4>Status Pendaftar</h4>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span>Verifikasi</span>
                  <span>{{ $pendingApplicants }}</span>
                </div>
                <div class="progress" data-height="6">
                  <div class="progress-bar bg-warning" data-width="{{ $totalApplicants > 0 ? round($pendingApplicants / $totalApplicants * 100) : 0 }}%"></div>
                </div>
              </div>
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span>Diterima</span>
                  <span>{{ $acceptedApplicants }}</span>
                </div>
                <div class="progress" data-height="6">
                  <div class="progress-bar bg-info" data-width="{{ $totalApplicants > 0 ? round($acceptedApplicants / $totalApplicants * 100) : 0 }}%"></div>
                </div>
              </div>
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span>Perlu Revisi</span>
                  <span>{{ $revisionApplicants }}</span>
                </div>
                <div class="progress" data-height="6">
                  <div class="progress-bar bg-secondary" data-width="{{ $totalApplicants > 0 ? round($revisionApplicants / $totalApplicants * 100) : 0 }}%"></div>
                </div>
              </div>
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span>Ditolak</span>
                  <span>{{ $rejectedApplicants }}</span>
                </div>
                <div class="progress" data-height="6">
                  <div class="progress-bar bg-danger" data-width="{{ $totalApplicants > 0 ? round($rejectedApplicants / $totalApplicants * 100) : 0 }}%"></div>
                </div>
              </div>
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span>Selesai</span>
                  <span>{{ $completedApplicants }}</span>
                </div>
                <div class="progress" data-height="6">
                  <div class="progress-bar bg-success" data-width="{{ $totalApplicants > 0 ? round($completedApplicants / $totalApplicants * 100) : 0 }}%"></div>
                </div>
              </div>
              <div class="text-center mt-3">
                <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-primary">Lihat Semua Pendaftar</a>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6 col-md-12 col-12">
          <div class="card">
            <div class="card-header">
              <h4>Batas Waktu Mendatang</h4>
            </div>
            <div class="card-body">
              @forelse($upcomingDeadlines as $scholarship)
                <div class="d-flex align-items-center mb-3">
                  <div class="mr-3">
                    <div class="badge badge-{{ $scholarship->batas_waktu?->diffInDays(now()) <= 7 ? 'danger' : 'primary' }}">
                      {{ $scholarship->batas_waktu?->diffForHumans() }}
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <div class="font-weight-bold">{{ $scholarship->nama }}</div>
                    <div class="text-small text-muted">{{ $scholarship->kampus }}</div>
                  </div>
                </div>
              @empty
                <div class="text-center text-muted">Tidak ada batas waktu mendatang</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header">
              <h4>Pendaftar Terbaru</h4>
              <div class="card-header-action">
                <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-primary">Lihat Semua</a>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>Nama</th>
                      <th>Beasiswa</th>
                      <th>Fakultas</th>
                      <th>Status</th>
                      <th>Tanggal Daftar</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($recentApplicants as $applicant)
                      <tr>
                        <td>
                          <a href="{{ route('admin.pendaftar.lihat', $applicant) }}">{{ $applicant->user->profile->nama_lengkap ?? '-' }}</a>
                        </td>
                        <td>{{ $applicant->beasiswa->nama }}</td>
                        <td>{{ $applicant->fakultas ?? '-' }}</td>
                        <td>
                          @if($applicant->status === 'verifikasi')
                            <span class="badge badge-warning">Verifikasi</span>
                          @elseif($applicant->status === 'diterima')
                            <span class="badge badge-info">Diterima Tahap 1</span>
                          @elseif($applicant->status === 'verifikasi_akhir')
                            <span class="badge badge-primary">Verifikasi Akhir</span>
                          @elseif($applicant->status === 'selesai')
                            <span class="badge badge-success">Selesai</span>
                          @elseif($applicant->status === 'revisi')
                            <span class="badge badge-secondary">Perlu Revisi</span>
                          @elseif($applicant->status === 'ditolak')
                            <span class="badge badge-danger">Ditolak</span>
                          @endif
                        </td>
                        <td>{{ $applicant->created_at->format('d M Y') }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="5" class="text-center">Belum ada pendaftar</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    @else
      {{-- USER DASHBOARD --}}
      <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-primary">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Total Pendaftaran</h4>
              </div>
              <div class="card-body">
                {{ $totalApplications }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-warning">
              <i class="fas fa-clock"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Verifikasi</h4>
              </div>
              <div class="card-body">
                {{ $pendingApplications }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-success">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Diterima</h4>
              </div>
              <div class="card-body">
                {{ $acceptedApplications }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
          <div class="card card-statistic-1">
            <div class="card-icon bg-danger">
              <i class="fas fa-times-circle"></i>
            </div>
            <div class="card-wrap">
              <div class="card-header">
                <h4>Ditolak</h4>
              </div>
              <div class="card-body">
                {{ $rejectedApplications }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-8 col-md-12 col-12">
          <div class="card">
            <div class="card-header">
              <h4>Pendaftaran Terbaru</h4>
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
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($recentApplications as $applicant)
                      <tr>
                        <td>{{ $applicant->beasiswa->nama }}</td>
                        <td>
                          @if($applicant->status === 'verifikasi')
                            <span class="badge badge-warning">Verifikasi</span>
                          @elseif($applicant->status === 'diterima')
                            <span class="badge badge-info">Diterima Tahap 1</span>
                          @elseif($applicant->status === 'verifikasi_akhir')
                            <span class="badge badge-primary">Verifikasi Akhir</span>
                          @elseif($applicant->status === 'selesai')
                            <span class="badge badge-success">Selesai</span>
                          @elseif($applicant->status === 'revisi')
                            <span class="badge badge-secondary">Perlu Revisi</span>
                          @elseif($applicant->status === 'ditolak')
                            <span class="badge badge-danger">Ditolak</span>
                          @endif
                        </td>
                        <td>{{ $applicant->created_at->format('d M Y') }}</td>
                        <td>
                          <a href="{{ route('user.pendaftaran.lihat', $applicant) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                          </a>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="4" class="text-center">Belum ada pendaftaran. <a href="{{ route('user.beasiswa.index') }}">Daftar beasiswa sekarang!</a></td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-12 col-12">
          <div class="card">
            <div class="card-header">
              <h4>Beasiswa Tersedia</h4>
              <div class="card-header-action">
                <a href="{{ route('user.beasiswa.index') }}" class="btn btn-primary">Lihat Semua</a>
              </div>
            </div>
            <div class="card-body">
              @forelse($availableScholarships as $scholarship)
                <div class="d-flex align-items-center mb-3">
                  <div class="mr-3">
                    <div class="badge badge-{{ $scholarship->batas_waktu?->diffInDays(now()) <= 7 ? 'danger' : 'primary' }}">
                      {{ $scholarship->batas_waktu?->diffForHumans() }}
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <div class="font-weight-bold">{{ $scholarship->nama }}</div>
                    <div class="text-small text-muted">{{ $scholarship->kampus }}</div>
                  </div>
                  <a href="{{ route('user.beasiswa.lihat', $scholarship) }}" class="btn btn-sm btn-outline-primary">Daftar</a>
                </div>
              @empty
                <div class="text-center text-muted">Tidak ada beasiswa tersedia</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    @endif
</section>
@endsection
