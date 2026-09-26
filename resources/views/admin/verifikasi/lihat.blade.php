@extends('layouts.app')

@php
  $routePrefix = $stage === 'capil' ? 'admin.capil' : ($stage === 'kampus' ? 'admin.kampusverif' : 'admin.kesra');
  $stageLabels = ['capil' => 'Capil', 'kampus' => 'Kampus', 'kesra' => 'Kesra'];
  $stageLabel = $stageLabels[$stage] ?? ucfirst($stage);
  $decision = $profile->verifStageDecision($stage);
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