@extends('layouts.app')

@php
  $routePrefix = 'admin.'.\App\Models\UserProfile::verifRoutePrefixes()[$stage];
  $stageLabels = ['catpil' => 'Catpil', 'kampus' => 'Kampus', 'kesra' => 'Kesra'];
  $stageLabel = $stageLabels[$stage] ?? ucfirst($stage);
  $decision = $profile->verifStageDecision($stage);

  // Kunci Kampus: begitu Kesra memutuskan, baris Kampus tetap tampil di antrean
  // (lihat `UserProfile::inVerifQueue()`) tapi halamannya read-only. `false`
  // untuk Catpil (selalu bisa dikerjakan) dan untuk tahap Kesra yang punya
  // percabangan sendiri di bawah.
  $terkunci = $stage === 'kampus' && ! $profile->canVerifStage('kampus');

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
    'Catpil' => $profile->catatan_catpil,
    'Kampus' => $profile->catatan_kampus,
    'Kesra' => $profile->catatan_kesra,
  ]);
  // `diputuskanOleh` ikut di-eager-load karena blok keputusan yang sudah
  // tersimpan memanggilnya; tanpa ini satu query per pendaftaran yang sudah
  // diputuskan.
  $applications = $stage === 'kesra'
      ? $user->applicants()->with(['beasiswa.kampus', 'diputuskanOleh'])->latest()->get()
      : collect();
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>{{ $stage === 'kesra' ? 'Detail Beasiswa' : 'Verifikasi Profil' }} - {{ $stageLabel }}</h1>
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
              <h4>Keputusan Kesra</h4>
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
                    $beasiswa = $applicant->beasiswa;

                    // Kartu ini menampilkan detail BEASISWA yang dilamar, bukan
                    // mengulang data pendaftar: data diri, kampus, dan dokumen
                    // sudah lengkap di kolom kiri (`partials.profil-detail`),
                    // jadi peninjauan di sini fokus ke ketentuan beasiswanya.
                    $detailBeasiswa = [
                      ['label' => 'Kampus', 'value' => $beasiswa?->kampus?->nama_kampus ?? $beasiswa?->kampus ?? '-'],
                      ['label' => 'Kuota', 'value' => $beasiswa?->kuota ?? '-'],
                      ['label' => 'IPK Minimal', 'value' => $beasiswa ? number_format((float) $beasiswa->ipk_minimal, 2) : '-'],
                      ['label' => 'Semester Minimal', 'value' => $beasiswa?->semester_minimal ?? '-'],
                      ['label' => 'Tingkat Gelar', 'value' => $beasiswa?->tingkat_gelar ?? '-'],
                      ['label' => 'Status Beasiswa', 'value' => $beasiswa ? ($beasiswa->status === 'aktif' ? 'Aktif' : 'Non-Aktif') : '-'],
                      ['label' => 'Tanggal Mulai', 'value' => $beasiswa?->tanggal_mulai?->translatedFormat('d F Y') ?? '-'],
                      ['label' => 'Tanggal Selesai', 'value' => $beasiswa?->tanggal_selesai?->translatedFormat('d F Y') ?? '-'],
                    ];

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
                      </div>
                      <span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span>
                    </div>

                    <div class="text-muted small text-uppercase mb-1">Detail Beasiswa</div>
                    <div class="row small mb-3">
                      @foreach($detailBeasiswa as $field)
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
                        {{-- Catatan tidak dikumpulkan lagi dari tahap Kesra
                             (permintaan pengguna), jadi tak ada baris catatan
                             di ringkasan keputusan. --}}
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
                      <label for="pendaftaran_status" class="text-muted small text-uppercase mb-1 d-block">
                        {{ $sudahDiputuskan ? 'Ubah Keputusan' : 'Keputusan Kesra' }}
                        <span class="text-danger">*</span>
                      </label>
                      <form action="{{ route($routePrefix.'.pendaftaran.keputusan', [$user, $applicant]) }}" method="POST" data-ajax-form>
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-2">
                        {{-- Pendaftaran yang belum diputuskan maupun yang sudah
                             punya keputusan sama-sama dibuka di "— Pilih —".
                             Kalau "Terima"/"Tolak" otomatis terpilih, admin bisa
                             menekan simpan tanpa memilih apa pun dan keputusan
                             lama justru terkonfirmasi ulang. --}}
                        <select class="form-control @error('pendaftaran_status') is-invalid @enderror"
                                name="pendaftaran_status" id="pendaftaran_status">
                          <option value="" {{ $statusPendaftaran === '' ? 'selected' : '' }}>&mdash; Pilih &mdash;</option>
                            <option value="diterima" {{ $statusPendaftaran === 'diterima' ? 'selected' : '' }}>Terima</option>
                            <option value="ditolak" {{ $statusPendaftaran === 'ditolak' ? 'selected' : '' }}>Tolak</option>
                          </select>
                          @error('pendaftaran_status')
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

              @if($terkunci)
              {{-- Kunci Kampus: Kesra sudah memutuskan, jadi keputusan Kampus
                   read-only. Form dan tombol simpan tidak ditampilkan sama
                   sekali supaya tidak ada jalur yang mengubah keputusan lama. --}}
              <div class="alert alert-warning">
                <i class="fas fa-lock mr-1"></i>
                Verifikasi Kampus terkunci karena Kesra sudah memutuskan pendaftaran
                mahasiswa ini. Keputusan Kampus tidak bisa diubah lagi lewat halaman ini.
                @if($decision['decided'])
                Keputusan Kampus saat ini: <strong>{{ $decision['label'] }}</strong>.
                @endif
              </div>
              @else
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
              @endif
            </div>
          </div>

          @unless($terkunci)
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
          @endunless
        @endif
      </div>
    </div>
  </div>
</section>
@endsection