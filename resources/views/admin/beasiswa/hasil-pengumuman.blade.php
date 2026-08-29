@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Kelola Hasil Pengumuman</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.beasiswa.index') }}">Beasiswa</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.beasiswa.lihat', $scholarship) }}">{{ $scholarship->nama }}</a></div>
      <div class="breadcrumb-item">Hasil Pengumuman</div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
  @endif

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>{{ $scholarship->nama }} ({{ $scholarship->kampus }})</h4>
            <div class="card-header-action">
              <a href="{{ route('admin.beasiswa.lihat', $scholarship) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
              </a>
            </div>
          </div>
          <div class="card-body">
            @if($scholarship->isPengumumanAktif())
              <div class="alert alert-success mb-3">
                <i class="fas fa-bullhorn"></i> Pengumuman sedang berlangsung ({{ $scholarship->tanggal_pengumuman->format('d F Y') }} s.d. {{ $scholarship->tanggal_pengumuman_selesai->format('d F Y') }})
              </div>
            @elseif($scholarship->isDiumumkan())
              <div class="alert alert-warning mb-3">
                <i class="fas fa-clock"></i> Pengumuman telah selesai pada {{ $scholarship->tanggal_pengumuman_selesai->format('d F Y') }}
              </div>
            @else
              <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle"></i> Beasiswa belum diumumkan
              </div>
            @endif

            <form method="POST" action="{{ route('admin.beasiswa.hasil-pengumuman.update', $scholarship) }}">
              @csrf
              @method('PUT')

              <div class="table-responsive">
                <table class="table table-striped" id="hasilTable">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>Nama</th>
                      <th>Fakultas</th>
                      <th>Prodi</th>
                      <th>IPK</th>
                      <th>Status</th>
                      <th>Hasil Pengumuman</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($applicants as $applicant)
                      @php $profile = $applicant->user->profile; @endphp
                      <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $profile->nama_lengkap ?? '-' }}</td>
                        <td>{{ $applicant->fakultas ?? '-' }}</td>
                        <td>{{ $applicant->prodi ?? '-' }}</td>
                        <td>{{ $applicant->ipk }}</td>
                        <td>
                          @if($applicant->status === 'diterima')
                            <span class="badge badge-info">Diterima</span>
                          @else
                            <span class="badge badge-secondary">{{ $applicant->getStatusLabelAttribute() }}</span>
                          @endif
                        </td>
                        <td>
                          @if($applicant->status === 'diterima')
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="hasil[{{ $applicant->id }}]" value="diterima" {{ $applicant->hasil_pengumuman === 'diterima' ? 'checked' : '' }} required>
                              <label class="form-check-label">Ya</label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="hasil[{{ $applicant->id }}]" value="tidak_diterima" {{ $applicant->hasil_pengumuman === 'tidak_diterima' ? 'checked' : '' }}>
                              <label class="form-check-label">Tidak</label>
                            </div>
                          @else
                            <span class="text-muted">-</span>
                          @endif
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="7" class="text-center text-muted py-3">Belum ada pendaftar dengan status Diterima</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              @if($applicants->where('status', 'diterima')->count() > 0)
                <div class="text-right mt-3">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                  </button>
                </div>
              @endif
            </form>
          </div
        </div>
      </div>
    </div>
  </div>
</section>
@endsection