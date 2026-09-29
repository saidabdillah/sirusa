@extends('layouts.app')

@php
  $routePrefix = 'admin.'.\App\Models\UserProfile::verifRoutePrefixes()[$stage];
  $stageLabels = ['capil' => 'Capil', 'kampus' => 'Kampus', 'kesra' => 'Kesra'];
  $stageLabel = $stageLabels[$stage] ?? ucfirst($stage);
  $decision = $profile->verifStageDecision($stage);

  // Dropdown keputusan selalu dibuka di "— Pilih —", termasuk saat keputusan
  // lama masih tersimpan. Menjadikan nilai lama sebagai pilihan default
  // berisiko: admin bisa cuma membuka halaman lalu menekan simpan, dan keputusan
  // lama itu terkirim ulang sebagai keputusan baru — termasuk menimpa
  // `diputuskan_at` dan petugas penandatanganannya. Keputusan yang sudah ada
  // tetap ditampilkan read-only di kotak "Keputusan saat ini", jadi informasinya
  // tidak hilang, hanya tidak lagi terpilih di form.
  //
  // `old()` tetap dipakai supaya pilihan admin tidak hilang kalau validasi gagal
  // lalu form dimuat ulang.
  $statusTerpilih = old('status', '');
  $verifNotes = array_filter([
    'Capil' => $profile->catatan_capil,
    'Kampus' => $profile->catatan_kampus,
    'Kesra' => $profile->catatan_kesra,
  ]);
  // `diputuskanOleh` ikut di-eager-load karena blok keputusan yang sudah
  // tersimpan memanggilnya; tanpa ini satu query per pendaftaran yang sudah
  // diputuskan.
  $applications = $stage === 'kesra'
      ? $user->applicants()->with(['beasiswa', 'diputuskanOleh'])->latest()->get()
      : collect();
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>{{ $stage === 'kesra' ? 'Keputusan Beasiswa' : 'Verifikasi Profil' }} - {{ $stageLabel }}</h1>
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
        @if($stage === 'kesra')
          {{-- Tahap Kesra hanya punya SATU keputusan, dan itu keputusan
               pendaftaran beasiswa di bawah. Tidak ada lagi form verdict profil
               di halaman ini: kalau ada, satu pendaftaran bisa mendapat dua
               jawaban berbeda (profil disetujui tapi pendaftaran ditolak, atau
               sebaliknya). Verifikasi Kesra ditutup oleh aksi yang sama, di
               `KeputusanPendaftaranController::update()`. --}}
          <div class="card">
            <div class="card-header">
              <h4>Keputusan Beasiswa</h4>
            </div>
            <div class="card-body">
              @if($verifNotes)
              <div class="border rounded p-3 mb-3">
                <div class="text-muted mb-2"><strong>Catatan verifikator</strong></div>
                @foreach($verifNotes as $noteLabel => $noteText)
                <div class="mb-1"><strong>{{ $noteLabel }}:</strong> {{ $noteText }}</div>
                @endforeach
              </div>
              @endif

              @if($applications->isEmpty())
                <div class="text-muted">
                  Mahasiswa ini belum punya pendaftaran beasiswa yang bisa diputuskan.
                </div>
              @else
                @foreach($applications as $applicant)
                  @php
                    // Ringkasan data yang dibaca admin sebelum memutuskan. Satu
                    // array supaya urutan baris tidak ditentukan oleh urutan
                    // penulisan di tiap blok.
                    $dataPendaftaran = [
                      ['label' => 'Nama Pendaftar', 'value' => $profile->nama_lengkap ?? '-'],
                      ['label' => 'NIK', 'value' => $profile->nik ?? '-'],
                      ['label' => 'Kampus', 'value' => $profile->prodi?->fakultas?->kampus?->nama_kampus ?? '-'],
                      ['label' => 'Program Studi', 'value' => $profile->prodi?->nama ?? '-'],
                      ['label' => 'Beasiswa', 'value' => $applicant->beasiswa?->nama ?? '-'],
                      ['label' => 'UKT/SPP', 'value' => $profile->ukt ? 'Rp '.number_format($profile->ukt, 0, ',', '.') : '-'],
                      ['label' => 'IPK', 'value' => $applicant->ipk ?? $profile->ipk ?? '-'],
                      ['label' => 'Semester', 'value' => $applicant->semester ?? $profile->semester ?? '-'],
                    ];
                    $syaratBeasiswa = collect([
                      'Kuota '.$applicant->beasiswa?->kuota,
                      'IPK minimal '.number_format($applicant->beasiswa?->ipk_minimal ?? 0, 2),
                      'Semester minimal '.$applicant->beasiswa?->semester_minimal,
                    ])->filter()->implode(' · ');

                    // `pendaftar.catatan` dipakai ulang sebagai isi field form
                    // supaya revisi keputusan tidak menghapus catatan lama tanpa
                    // admin menyadarinya.
                    //
                    // Sama seperti dropdown profil, nilai pendaftaran yang sudah
                    // ada TIDAK dijadikan pilihan default. "Ubah Keputusan" yang
                    // tertulis di judul form hanya bermakna kalau admin benar-benar
                    // memilih nilai baru; dengan "Terima" yang sudah terpilih,
                    // satu klik simpan akan mengonfirmasi keputusan lama lagi dan
                    // menimpa `diputuskan_at` beserta petugasnya. Status lama
                    // sendiri sudah tampil sebagai badge di atas, jadi tidak
                    // hilang informasinya. `old()` tetap dipakai supaya pilihan
                    // tidak hilang saat validasi gagal.
                    $statusPendaftaran = old('pendaftaran_status', '');
                    $sudahDiputuskan = $applicant->isDecided();
                  @endphp

                  <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                      <div>
                        <strong>{{ $applicant->beasiswa?->nama }}</strong>
                        <div class="text-muted small">{{ $syaratBeasiswa }}</div>
                      </div>
                      <span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span>
                    </div>

                    <div class="text-muted small text-uppercase mb-1">Data Pendaftaran Beasiswa</div>
                    <div class="row small mb-3">
                      @foreach($dataPendaftaran as $field)
                        <div class="col-6">
                          <div class="text-muted">{{ $field['label'] }}</div>
                          <div class="font-weight-bold">{{ $field['value'] }}</div>
                        </div>
                      @endforeach
                    </div>

                    @foreach($applicant->snapshotDrifts($profile) as $drift)
                      <div class="alert alert-warning py-2 px-3 small mb-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Data sudah berubah sejak mendaftar &mdash; {{ $drift }}.
                        Putuskan berdasarkan kelayakan saat mendaftar.
                      </div>
                    @endforeach

                    @if($sudahDiputuskan)
                      {{-- Keputusan yang sudah ada ditampilkan sebagai read-only.
                           `diputuskan_at` dan `diputuskan_oleh` diisi saat
                           keputusan disimpan, bukan dibaca dari `updated_at`
                           yang ikut berubah saat baris lain disentuh. --}}
                      <div class="text-muted small text-uppercase mb-1">Keputusan Kesra</div>
                      <div class="border rounded p-2 mb-2 small">
                        <div class="mb-1">
                          Status:
                          <span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span>
                        </div>
                        <div class="mb-1"><strong>Catatan:</strong> {{ $applicant->catatan ?: '—' }}</div>
                        <div class="mb-1">
                          <strong>Tanggal:</strong>
                          {{ $applicant->diputuskan_at?->translatedFormat('d F Y H:i') ?? '—' }}
                        </div>
                        <div class="mb-0">
                          <strong>Diputuskan oleh:</strong> {{ $applicant->decidedByName() ?? '—' }}
                        </div>
                      </div>
                    @endif

                    @if($applicant->isCancelled())
                      {{-- `dibatalkan` bukan keputusan Kesra, itu pilihan mahasiswa,
                           jadi tidak punya tanggal/ruby siapa pun untuk ditulis di
                           sini dan formnya tidak dibuka. --}}
                      <div class="text-muted small mb-0">
                        <i class="fas fa-ban mr-1"></i> Dibatalkan mahasiswa, tidak bisa diputuskan lagi.
                      </div>
                    @else
                      <div class="text-muted small text-uppercase mb-1">
                        {{ $sudahDiputuskan ? 'Ubah Keputusan' : 'Keputusan Kesra' }}
                      </div>
                      <form action="{{ route($routePrefix.'.pendaftaran.keputusan', [$user, $applicant]) }}" method="POST" data-ajax-form>
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-2">
                        {{-- Pendaftaran yang belum diputuskan maupun yang sudah
                             punya keputusan sama-sama dibuka di "— Pilih —".
                             Kalau "Terima"/"Tolak" otomatis terpilih, admin bisa
                             menekan simpan tanpa memilih apa pun dan keputusan
                             lama justru terkonfirmasi ulang. --}}
                        <select class="form-control form-control-sm @error('pendaftaran_status') is-invalid @enderror"
                                name="pendaftaran_status">
                          <option value="" {{ $statusPendaftaran === '' ? 'selected' : '' }}>&mdash; Pilih &mdash;</option>
                            <option value="diterima" {{ $statusPendaftaran === 'diterima' ? 'selected' : '' }}>Terima</option>
                            <option value="ditolak" {{ $statusPendaftaran === 'ditolak' ? 'selected' : '' }}>Tolak</option>
                          </select>
                          @error('pendaftaran_status')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="form-group mb-2">
                          <input type="text" class="form-control form-control-sm @error('pendaftaran_catatan') is-invalid @enderror"
                                 name="pendaftaran_catatan" placeholder="Alasan (wajib jika ditolak)"
                                 value="{{ old('pendaftaran_catatan', $applicant->catatan) }}">
                          @error('pendaftaran_catatan')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                        <button type="submit" class="btn btn-success btn-sm btn-block"
                                data-loading-button data-loading-text="Menyimpan keputusan...">
                          <i class="fas fa-save"></i> Simpan Keputusan
                        </button>
                      </form>
                    @endif
                  </div>
                @endforeach
              @endif
            </div>
          </div>
        @else
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
                mengubahnya menjadi Persetujuan, Perbaikan, atau Penolakan.
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

          @push('script')
          <script>
            $(document).ready(function () {
              // Hanya "Setujui" dan "Tolak" yang menutup kesempatan mahasiswa
              // untuk memperbaiki data, jadi hanya keduanya yang perlu konfirmasi.
              //
              // "Minta Perbaikan" dan "— Pilih —" sengaja TIDAK memakai dialog:
              //   - "— Pilih —" dikirim apa adanya supaya pesan "Status verifikasi
              //     harus dipilih." muncul inline di bawah select. Dengan dialog,
              //     user akan diminta mengonfirmasi sesuatu yang tidak ada:
              //     keputusan belum ada, jadi tidak ada yang bisa dicabut.
              //   - "Minta Perbaikan" dikirim apa adanya supaya pesan "Catatan
              //     wajib diisi ketika memilih Minta Perbaikan." muncul inline
              //     di bawah textarea, bukan lewat dialog yang menggantung.
              //
              // Peta (bukan rantai ternary) supaya tidak ada lagi nilai yang
              // "tidak dikenali" diam-diam jatuh ke teks penarik keputusan.
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

              // Catatan wajib hanya untuk "Minta Perbaikan". `required` di sini
              // cuma penanda semantik + untuk aksesibilitas; penegakan sebenarnya
              // ada di `VerifikasiProfilRequest` (`required_if:status,revisi`).
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
                  // `trigger('submit')` supaya handler AJAX di `custom.js` ikut
                  // jalan; `submit()` native akan melewatinya dan memuat ulang.
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
        @endif
      </div>
    </div>
  </div>
</section>
@endsection