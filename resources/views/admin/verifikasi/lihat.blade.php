@extends('layouts.app')

@php
  $routePrefix = 'admin.'.\App\Models\UserProfile::verifRoutePrefixes()[$stage];
  $stageLabels = ['capil' => 'Capil', 'kampus' => 'Kampus', 'kesra' => 'Kesra'];
  $stageLabel = $stageLabels[$stage] ?? ucfirst($stage);
  $decision = $profile->verifStageDecision($stage);
  $applications = $stage === 'kesra' ? $user->applicants()->with('beasiswa')->latest()->get() : collect();
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Verifikasi Profil - {{ $stageLabel }}</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active"><a href="{{ route($routePrefix.'.index') }}">Verifikasi {{ $stageLabel }}</a></div>
      <div class="breadcrumb-item">{{ $profile->nama_lengkap }}</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-lg-8">
        @include('partials.profil-detail', ['profile' => $profile, 'stage' => $stage])
      </div>

      <div class="col-lg-4 sticky-sidebar">
        <div class="card">
          <div class="card-header">
            <h4>Status Verifikasi</h4>
          </div>
          <div class="card-body">
            @foreach($profile->verifStageLabels() as $s => $label)
            @php
              $status = $profile->{'verif_'.$s};
              $badge = match ($status) { 'setuju' => 'success', 'revisi' => 'danger', 'tolak' => 'danger', default => 'warning' };
              $text = match ($status) { 'setuju' => 'Disetujui', 'revisi' => 'Perlu Perbaikan', 'tolak' => 'Ditolak', default => 'Menunggu' };
            @endphp
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>{{ $label }}</span>
              <span class="badge badge-{{ $badge }}">{{ $text }}</span>
            </div>
            @if($profile->{'catatan_'.$s})
            <small class="text-muted d-block mb-2"><strong>Catatan {{ $stageLabels[$s] }}:</strong> {{ $profile->{'catatan_'.$s} }}</small>
            @endif
            @endforeach
            @if($profile->verifStatus() === 'terverifikasi')
            <div class="alert alert-success mb-0 mt-2"><i class="fas fa-check-circle"></i> Profil telah terverifikasi lengkap.</div>
            @endif
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h4>Verifikasi {{ $stageLabel }}</h4>
          </div>
          <div class="card-body">
            @if($decision['decided'])
            <div class="alert alert-info">
              <i class="fas fa-info-circle mr-1"></i>
              Keputusan saat ini: <strong>{{ $decision['label'] }}</strong>. Anda masih dapat
              mengubahnya menjadi Persetujuan, Perbaikan, Penolakan, atau menariknya kembali.
            </div>
            @endif
            <form action="{{ route($routePrefix.'.verifikasi', $user) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="form-group">
                <label for="status">Keputusan <span class="text-danger">*</span></label>
                <select class="form-control @error('status') is-invalid @enderror" name="status" id="status">
                  <option value="" {{ ! old('status') ? 'selected' : '' }}>-- pilih --</option>
                  <option value="setuju" {{ old('status', $decision['status']) === 'setuju' ? 'selected' : '' }}>Setujui</option>
                  <option value="revisi" {{ old('status', $decision['status']) === 'revisi' ? 'selected' : '' }}>Minta Perbaikan</option>
                  <option value="tolak" {{ old('status', $decision['status']) === 'tolak' ? 'selected' : '' }}>Tolak</option>
                  @if($decision['decided'])
                  <option value="menunggu" {{ old('status', $decision['status']) === 'menunggu' ? 'selected' : '' }}>Tarik Kembali (kembalikan ke Menunggu)</option>
                  @endif
                </select>
                @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Memilih selain "Setujui" akan mengembalikan verifikasi
                  pada tahap-tahap berikutnya ke daftar tunggu.</small>
              </div>
              <div class="form-group">
                <label for="catatan">Catatan</label>
                <textarea class="form-control @error('catatan') is-invalid @enderror" name="catatan" id="catatan"
                  rows="4" placeholder="Catatan (wajib jika minta perbaikan)">{{ old('catatan', $profile->{'catatan_'.$stage}) }}</textarea>
                @error('catatan')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <button type="button" class="btn btn-primary btn-block" id="btn-verifikasi">
                <i class="fas fa-save"></i> Simpan Keputusan
              </button>
            </form>
          </div>
        </div>

        {{-- Keputusan beasiswa diambil per pendaftaran, bukan dari status profil di
             atas. Verifikasi Kesra hanya menyatakan identitasnya sudah benar. --}}
        @if($stage === 'kesra')
          <div class="card">
            <div class="card-header">
              <h4>Pendaftaran Beasiswa</h4>
            </div>
            <div class="card-body">
              @if($applications->isEmpty())
                <div class="text-muted">
                  {{ $profile->verif_kesra === 'setuju'
                      ? 'Mahasiswa ini sudah terverifikasi tapi tidak mendaftar beasiswa.'
                      : 'Keputusan beasiswa baru bisa diambil setelah profil disetujui pada tahap Kesra.' }}
                </div>
              @else
                @if($profile->verif_kesra !== 'setuju')
                  <div class="alert alert-warning">
                    <i class="fas fa-info-circle mr-1"></i>
                    Setujui verifikasi profil di atas terlebih dahulu sebelum memutuskan pendaftaran.
                  </div>
                @endif

                @foreach($applications as $applicant)
                  <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                      <div>
                        <strong>{{ $applicant->beasiswa?->nama }}</strong>
                        <div class="text-muted small">
                          Kuota {{ $applicant->beasiswa?->kuota }} &middot; IPK minimal {{ number_format($applicant->beasiswa?->ipk_minimal ?? 0, 2) }}
                          &middot; Semester minimal {{ $applicant->beasiswa?->semester_minimal }}
                        </div>
                      </div>
                      <span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span>
                    </div>

                    <div class="row small text-muted mb-2">
                      <div class="col-6">IPK saat daftar: <strong>{{ $applicant->ipk ?? '-' }}</strong></div>
                      <div class="col-6">Semester saat daftar: <strong>{{ $applicant->semester ?? '-' }}</strong></div>
                      <div class="col-12">Prodi: <strong>{{ $applicant->prodi ?? '-' }}</strong></div>
                    </div>

                    @foreach($applicant->snapshotDrifts($profile) as $drift)
                      <div class="alert alert-warning py-2 px-3 small mb-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Data sudah berubah sejak mendaftar &mdash; {{ $drift }}.
                        Putuskan berdasarkan kelayakan saat mendaftar.
                      </div>
                    @endforeach

                    @if($applicant->isCancelled())
                      <div class="text-muted small mb-0">
                        <i class="fas fa-ban mr-1"></i> Dibatalkan mahasiswa, tidak bisa diputuskan lagi.
                      </div>
                    @else
                      <form action="{{ route($routePrefix.'.pendaftaran.keputusan', [$user, $applicant]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-2">
                          <select class="form-control form-control-sm @error('pendaftaran_status') is-invalid @enderror"
                                  name="pendaftaran_status" {{ $profile->verif_kesra !== 'setuju' ? 'disabled' : '' }}>
                            <option value="diterima" {{ old('pendaftaran_status') === 'diterima' ? 'selected' : '' }}>Terima</option>
                            <option value="ditolak" {{ old('pendaftaran_status') === 'ditolak' ? 'selected' : '' }}>Tolak</option>
                            @if($applicant->isDecided())
                              <option value="verifikasi" {{ old('pendaftaran_status') === 'verifikasi' ? 'selected' : '' }}>Tarik Kembali</option>
                            @endif
                          </select>
                          @error('pendaftaran_status')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="form-group mb-2">
                          <input type="text" class="form-control form-control-sm @error('pendaftaran_catatan') is-invalid @enderror"
                                 name="pendaftaran_catatan" placeholder="Alasan (wajib jika ditolak)"
                                 value="{{ old('pendaftaran_catatan', $applicant->catatan) }}"
                                 {{ $profile->verif_kesra !== 'setuju' ? 'disabled' : '' }}>
                          @error('pendaftaran_catatan')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                        <button type="submit" class="btn btn-success btn-sm btn-block" {{ $profile->verif_kesra !== 'setuju' ? 'disabled' : '' }}>
                          <i class="fas fa-save"></i> Simpan Keputusan Pendaftaran
                        </button>
                      </form>
                    @endif
                  </div>
                @endforeach
              @endif
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</section>
@endsection

@push('script')
<script>
  $(document).ready(function () {
    $('#btn-verifikasi').on('click', function () {
      var status = $('#status').val();
      var text = status === 'setuju'
        ? 'Setujui data profil ini?'
        : (status === 'revisi'
          ? 'Minta perbaikan data profil ini?'
          : (status === 'tolak'
            ? 'Tolak data profil ini?'
            : 'Tarik kembali keputusan ini ke daftar tunggu?'));
      Swal.fire({
        title: 'Konfirmasi',
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: status === 'setuju' || status === 'menunggu' ? '#47c363' : '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: status === 'setuju' ? 'Ya, Setujui!' : (status === 'revisi' ? 'Ya, Minta Perbaikan!' : (status === 'tolak' ? 'Ya, Tolak!' : 'Ya, Tarik Kembali!')),
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          showSubmitLoading('Menyimpan...');
          this.form.submit();
        }
      });
    });
  });
</script>
@endpush