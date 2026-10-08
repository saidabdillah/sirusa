@extends('layouts.app')

@php
  $routePrefix = 'admin.'.\App\Models\UserProfile::verifRoutePrefixes()[$stage];
  $stageLabels = ['catpil' => 'Catpil', 'kampus' => 'Kampus', 'kesra' => 'Kesra'];
  $stageLabel = $stageLabels[$stage] ?? ucfirst($stage);
  $decision = $profile->verifStageDecision($stage);

  // Nilai yang harus tampil di dropdown keputusan. `menunggu` tidak punya opsi
  // sendiri -- menarik keputusan ke daftar tunggu memang tidak disediakan --
  // jadi status itu diperlakukan sama dengan "belum ada keputusan": dropdown
  // kembali ke "— Pilih —" supaya admin tidak ikut menyetujui apa pun tanpa
  // sengaja memilih.
  $statusTerpilih = old('status', $decision['status']);

  if ($statusTerpilih === 'menunggu') {
      $statusTerpilih = '';
  }
  $verifNotes = array_filter([
    'Catpil' => $profile->catatan_catpil,
    'Kampus' => $profile->catatan_kampus,
    'Kesra' => $profile->catatan_kesra,
  ]);
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
            <h4>Verifikasi {{ $stageLabel }}</h4>
          </div>
          <div class="card-body">
            {{-- Ringkasan status per tahap dihapus dari halaman ini, sesuai
                 permintaan. Jejak audit tetap ada: catatan tiap verifikator
                 ditampilkan di bawah sebagai read-only, dan statusnya tetap
                 tersimpan di database untuk penyaringan antrean serta audit. --}}
            @if($verifNotes)
            <div class="border rounded p-3 mb-3">
              <div class="text-muted mb-2"><strong>Catatan verifikator</strong></div>
              @foreach($verifNotes as $noteLabel => $noteText)
              <div class="mb-1"><strong>{{ $noteLabel }}:</strong> {{ $noteText }}</div>
              @endforeach
            </div>
            @endif

            @if($decision['decided'])
            <div class="alert alert-info">
              <i class="fas fa-info-circle mr-1"></i>
              Keputusan saat ini: <strong>{{ $decision['label'] }}</strong>. Anda masih dapat
              mengubahnya menjadi Persetujuan, Perbaikan, Penolakan, atau menariknya kembali.
            </div>
            @endif
            <form action="{{ route($routePrefix.'.verifikasi', $user) }}" method="POST" id="formVerifikasi" data-ajax-form>
              @csrf
              @method('PUT')
              <div class="form-group">
                <label for="status">Keputusan <span class="text-danger">*</span></label>
                <select class="form-control @error('status') is-invalid @enderror" name="status" id="status">
                  <option value="" {{ $statusTerpilih === '' ? 'selected' : '' }}>&mdash; Pilih &mdash;</option>
                  <option value="setuju" {{ $statusTerpilih === 'setuju' ? 'selected' : '' }}>Setujui</option>
                  <option value="revisi" {{ $statusTerpilih === 'revisi' ? 'selected' : '' }}>Minta Perbaikan</option>
                  <option value="tolak" {{ $statusTerpilih === 'tolak' ? 'selected' : '' }}>Tolak</option>
                </select>
                @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Memilih selain "Setujui" akan mengembalikan verifikasi
                  pada tahap-tahap berikutnya ke daftar tunggu.</small>
              </div>
              <div class="form-group">
                <label for="catatan">
                  Catatan
                  {{-- Wajib hanya untuk "Minta Perbaikan". Dit-toggle dari JS saat
                       keputusan berubah, dan ditegakkan di backend lewat
                       `required_if:status,revisi` pada `VerifikasiProfilRequest`. --}}
                  <span class="text-danger d-none" id="catatan-wajib">*</span>
                </label>
                <textarea class="form-control @error('catatan') is-invalid @enderror" name="catatan" id="catatan"
                  rows="4" placeholder="Catatan (wajib jika minta perbaikan)">{{ old('catatan', $profile->{'catatan_'.$stage}) }}</textarea>
                @error('catatan')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              {{-- `type="button"` karena ada konfirmasi SweetAlert sebelum submit,
                   jadi handler AJAX di `custom.js` dipicu dari `trigger('submit')`
                   dan bukan dari tombolnya sendiri. Tanpa `data-loading-button`
                   tombol ini tidak akan pernah dapat spinner: `submitAjax()`
                   mencari `button[type="submit"]` dan tidak menemukannya. --}}
              <button type="button" class="btn btn-primary btn-block" id="btn-verifikasi"
                      data-loading-button data-loading-text="Menyimpan keputusan...">
                <i class="fas fa-save"></i> Simpan Keputusan
              </button>
            </form>
          </div>
        </div>

        {{-- Keputusan beasiswa diambil per pendaftaran, bukan dari status profil.
             Verifikasi Kesra hanya menyatakan identitasnya sudah benar. --}}
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
                      <form action="{{ route($routePrefix.'.pendaftaran.keputusan', [$user, $applicant]) }}" method="POST" data-ajax-form>
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-2">
                          @php
                            // Nilai yang harus disorot. Pendaftaran yang statusnya
                            // `verifikasi` BELUM punya keputusan, jadi jangan
                            // otomatis menyorot "Terima" -- kalau tidak, admin
                            // bisa menekan simpan tanpa memilih apa pun dan
                            // pendaftaran justru diterima.
                            $statusPendaftaran = old('pendaftaran_status', $applicant->status);
                          @endphp
                          <select class="form-control form-control-sm @error('pendaftaran_status') is-invalid @enderror"
                                  name="pendaftaran_status" {{ $profile->verif_kesra !== 'setuju' ? 'disabled' : '' }}>
                            <option value="" {{ $statusPendaftaran === 'verifikasi' ? 'selected' : '' }}>&mdash; Pilih &mdash;</option>
                            <option value="diterima" {{ $statusPendaftaran === 'diterima' ? 'selected' : '' }}>Terima</option>
                            <option value="ditolak" {{ $statusPendaftaran === 'ditolak' ? 'selected' : '' }}>Tolak</option>
                            @if($applicant->isDecided())
                              <option value="verifikasi" {{ $statusPendaftaran === 'verifikasi' ? 'selected' : '' }}>Tarik Kembali</option>
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
    // Hanya "Setujui" dan "Tolak" yang menutup kesempatan mahasiswa untuk
    // memperbaiki data, jadi hanya keduanya yang perlu konfirmasi.
    //
    // "Minta Perbaikan" dan "— Pilih —" sengaja TIDAK memakai dialog:
    //   - "— Pilih —" dikirim apa adanya supaya pesan "Status verifikasi harus
    //     dipilih." muncul inline di bawah select. Dengan dialog, user akan
    //     diminta mengonfirmasi sesuatu yang tidak ada: menarik keputusan ke
    //     daftar tunggu, padahal belum ada keputusan untuk ditarik.
    //   - "Minta Perbaikan" dikirim apa adanya supaya pesan "Catatan wajib
    //     diisi ketika memilih Minta Perbaikan." muncul inline di bawah
    //     textarea, bukan lewat dialog konfirmasi yang menggantung.
    //
    // Peta (bukan rantai ternary) supaya tidak ada lagi nilai yang "tidak
    // dikenali" diam-diam jatuh ke teks penarik keputusan.
    var KONFIRMASI = {
      setuju: {
        text: 'Yakin setuju?',
        tombol: 'Ya, Setujui!',
        warna: '#47c363'
      },
      tolak: {
        text: 'Yakin ditolak?',
        tombol: 'Ya, Tolak!',
        warna: '#e74c3c'
      }
    };

    // Catatan wajib hanya untuk "Minta Perbaikan". `required` di sini cuma
    // penanda semantik + untuk aksesibilitas; penegakan sebenarnya ada di
    // `VerifikasiProfilRequest` (`required_if:status,revisi`).
    function toggleCatatanWajib() {
      var wajib = $('#status').val() === 'revisi';

      $('#catatan').prop('required', wajib);
      $('#catatan-wajib').toggleClass('d-none', !wajib);
    }

    toggleCatatanWajib();
    $('#status').on('change', toggleCatatanWajib);

    $('#btn-verifikasi').on('click', function () {
      var $form = $(this.form);
      var dialog = KONFIRMASI[$('#status').val()];

      if (!dialog) {
        // `trigger('submit')` supaya handler AJAX di `custom.js` ikut jalan;
        // `submit()` native akan melewatinya dan memuat ulang halaman.
        $form.trigger('submit');
        return;
      }

      Swal.fire({
        title: 'Konfirmasi',
        text: dialog.text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: dialog.warna,
        cancelButtonColor: '#6c757d',
        confirmButtonText: dialog.tombol,
        cancelButtonText: 'Batal'
      }).then(function (result) {
        if (result.isConfirmed) {
          $form.trigger('submit');
        }
      });
    });
  });
</script>
@endpush