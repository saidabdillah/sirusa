@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Edit Beasiswa</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('admin.beasiswa.index') }}">Beasiswa</a></div>
        <div class="breadcrumb-item">Edit</div>
      </div>
    </div>

    <div class="section-body">

      <div class="row">
        <div class="col-lg-8">
          <form action="{{ route('admin.beasiswa.perbarui', $scholarship) }}" method="POST" id="formBeasiswa" data-ajax-form>
            @csrf
            @method('PUT')

            {{-- Section: Informasi Utama --}}
            <div class="card mb-4">
              <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Informasi Utama</h4>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="nama">Nama Beasiswa <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $scholarship->nama) }}">
                  @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Kampus tidak lagi dipilih PER BEASISWA (lihat form tambah):
                     cakupan ditentukan daftar prodi yang dicentang di bawah. --}}
                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label for="tingkat_gelar">Jenjang <span class="text-danger">*</span></label>
                    {{-- Pilihan dari sumber yang sama dengan profil
                         (JenjangPendidikan), bukan daftar tulis tangan. --}}
                    <select class="form-control @error('tingkat_gelar') is-invalid @enderror" id="tingkat_gelar" name="tingkat_gelar">
                      <option value="">&mdash; Pilih Jenjang &mdash;</option>
                      @foreach(\App\Support\JenjangPendidikan::JENJANG as $jenjang)
                      <option value="{{ $jenjang }}" {{ old('tingkat_gelar', $scholarship->tingkat_gelar) === $jenjang ? 'selected' : '' }}>{{ $jenjang }}</option>
                      @endforeach
                    </select>
                    @error('tingkat_gelar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label for="kuota">Kuota <span class="text-danger">*</span></label>
                    <div class="input-group @error('kuota') is-invalid @enderror">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-users"></i></span>
                      </div>
                      <input type="number" class="form-control @error('kuota') is-invalid @enderror" id="kuota" name="kuota" value="{{ old('kuota', $scholarship->kuota) }}" min="0">
                    </div>
                    @error('kuota')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-4">
                    <label for="tanggal_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
                    <div class="input-group @error('tanggal_mulai') is-invalid @enderror">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                      </div>
                      <input type="text" class="form-control flatpickr bg-white @error('tanggal_mulai') is-invalid @enderror" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', $scholarship->tanggal_mulai?->format('Y-m-d')) }}" placeholder="Pilih tanggal">
                    </div>
                    @error('tanggal_mulai')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-4">
                    <label for="tanggal_selesai">Tanggal Selesai <span class="text-danger">*</span></label>
                    <div class="input-group @error('tanggal_selesai') is-invalid @enderror">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                      </div>
                      <input type="text" class="form-control flatpickr bg-white @error('tanggal_selesai') is-invalid @enderror" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai', $scholarship->tanggal_selesai?->format('Y-m-d')) }}" placeholder="Pilih tanggal">
                    </div>
                    @error('tanggal_selesai')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label for="ipk_minimal">IPK Minimal <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" max="4" class="form-control @error('ipk_minimal') is-invalid @enderror"
                      id="ipk_minimal" name="ipk_minimal" value="{{ old('ipk_minimal', $scholarship->ipk_minimal) }}" placeholder="contoh 3.00">
                    @error('ipk_minimal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  {{-- Semester minimal: dropdown gaya profil (permintaan
                       pengguna); kolom `status` sengaja tidak ada di form. --}}
                  <div class="form-group col-md-8">
                    <label for="semester_minimal">Semester Minimal <span class="text-danger">*</span></label>
                    <select class="form-control @error('semester_minimal') is-invalid @enderror" id="semester_minimal"
                      name="semester_minimal">
                      <option value="">-- Pilih Semester --</option>
                      @foreach(range(1, 8) as $semester)
                      <option value="{{ $semester }}" {{ old('semester_minimal', $scholarship->semester_minimal) == $semester ? 'selected' : '' }}>{{ $semester }}</option>
                      @endforeach
                    </select>
                    @error('semester_minimal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>
            </div>

            {{-- Section: Deskripsi & Persyaratan --}}
            <div class="card mb-4">
              <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Deskripsi & Persyaratan</h4>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="deskripsi">Deskripsi <span class="text-danger">*</span></label>
                  <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi" rows="4">{{ old('deskripsi', $scholarship->deskripsi) }}</textarea>
                  @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group mb-0">
                  <label for="persyaratan">Persyaratan <span class="text-danger">*</span></label>
                  <textarea class="form-control @error('persyaratan') is-invalid @enderror" id="persyaratan" name="persyaratan" rows="4">{{ old('persyaratan', $scholarship->persyaratan) }}</textarea>
                  @error('persyaratan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <small class="text-muted">Pisahkan setiap persyaratan dengan enter</small>
                </div>
              </div>
            </div>

            {{-- Section: Fakultas & Program Studi --}}
            <div class="card mb-4">
              <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-graduation-cap mr-2"></i>Fakultas &amp; Program Studi <span class="text-danger">*</span></h4>
              </div>
              <div class="card-body">
                <div class="alert alert-info mb-3">
                  <i class="fas fa-info-circle mr-1"></i> Centang kampus, fakultas, atau program studi
                  yang berhak mengikuti beasiswa ini (minimal 1 prodi tercentang). Centang kampus
                  atau fakultas memilih semua prodi di dalamnya sekaligus.
                </div>

                <div class="mb-3">
                  <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="btn-centang-semua">
                    <i class="fas fa-check-double mr-1"></i> Centang Semua
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-lepas-semua">
                    <i class="fas fa-times mr-1"></i> Lepas Semua
                  </button>
                </div>

                <div class="mb-2">
                  <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="btn-buka-semua">
                    <i class="fas fa-chevron-down mr-1"></i> Buka Semua
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-tutup-semua">
                    <i class="fas fa-chevron-up mr-1"></i> Tutup Semua
                  </button>
                </div>

                {{-- Accordion kampus -> fakultas -> prodi: yang sudah tercentang
                     terbuka otomatis supaya pilihan tersimpan langsung terlihat. --}}
                <div id="prodi-tree">
                  @forelse($kampusList as $kampus)
                    @php
                      $prodiTerpilihKampus = $kampusTerpilih[$kampus->id] ?? [];
                      $semuaProdiKampus = $kampus->fakultas->flatMap->prodi;
                      $semuaTercentangKampus = $semuaProdiKampus->isNotEmpty()
                        && $semuaProdiKampus->every(fn ($p) => in_array($p->id, $prodiTerpilihKampus, false));
                      $adaTerpilihKampus = $semuaProdiKampus->pluck('id')->intersect($prodiTerpilihKampus)->isNotEmpty();
                    @endphp
                    <div class="kampus-tree mb-2 border rounded" data-kampus-id="{{ $kampus->id }}">
                      <div class="d-flex align-items-center px-3 py-2 bg-light rounded">
                        <div class="custom-control custom-checkbox mb-0">
                          <input type="checkbox" class="custom-control-input kampus-check"
                            id="kampus-{{ $kampus->id }}" {{ $semuaTercentangKampus ? 'checked' : '' }}>
                          <label class="custom-control-label font-weight-bold" for="kampus-{{ $kampus->id }}">
                            <i class="fas fa-university mr-1"></i>{{ $kampus->nama_kampus }}
                          </label>
                        </div>
                        <span class="badge badge-light ml-2">{{ $semuaProdiKampus->count() }} prodi</span>
                        <button type="button" class="btn btn-sm btn-link ml-auto py-0 text-decoration-none kampus-toggle"
                          data-toggle="collapse" data-target="#kampus-body-{{ $kampus->id }}"
                          aria-expanded="{{ $adaTerpilihKampus ? 'true' : 'false' }}">
                          <i class="fas fa-chevron-down"></i>
                        </button>
                      </div>

                      <div class="collapse {{ $adaTerpilihKampus ? 'show' : '' }}" id="kampus-body-{{ $kampus->id }}">
                        <div class="px-3 py-2">
                          @foreach($kampus->fakultas as $fakultas)
                            @php
                              $prodiTerpilihFakultas = array_intersect(
                                $fakultas->prodi->pluck('id')->all(), $prodiTerpilihKampus,
                              );
                              $semuaTercentangFakultas = $fakultas->prodi->isNotEmpty()
                                && count($prodiTerpilihFakultas) === $fakultas->prodi->count();
                            @endphp
                            <div class="fakultas-tree mb-1" data-fakultas-id="{{ $fakultas->id }}">
                              <div class="d-flex align-items-center">
                                <div class="custom-control custom-checkbox mb-0">
                                  <input type="checkbox" class="custom-control-input fakultas-check"
                                    id="fakultas-{{ $fakultas->id }}" {{ $semuaTercentangFakultas ? 'checked' : '' }}>
                                  <label class="custom-control-label font-weight-bold" for="fakultas-{{ $fakultas->id }}">
                                    {{ $fakultas->nama }}
                                  </label>
                                </div>
                                @if($fakultas->prodi->isNotEmpty())
                                  <button type="button" class="btn btn-sm btn-link ml-auto py-0 text-decoration-none fakultas-toggle"
                                    data-toggle="collapse" data-target="#fakultas-body-{{ $fakultas->id }}"
                                    aria-expanded="{{ count($prodiTerpilihFakultas) > 0 ? 'true' : 'false' }}">
                                    <span class="text-muted small mr-1">{{ $fakultas->prodi->count() }}</span>
                                    <i class="fas fa-chevron-down"></i>
                                  </button>
                                @endif
                              </div>

                              <div class="collapse {{ count($prodiTerpilihFakultas) > 0 ? 'show' : '' }}"
                                id="fakultas-body-{{ $fakultas->id }}">
                                <div class="row ml-2 mt-1">
                                  @foreach($fakultas->prodi as $prodi)
                                    <div class="col-md-4 mb-1">
                                      <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input prodi-check" id="prodi-{{ $prodi->id }}"
                                          name="prodi_ids[]" value="{{ $prodi->id }}" @checked(in_array($prodi->id, $prodiTerpilihKampus, false))>
                                        <label class="custom-control-label" for="prodi-{{ $prodi->id }}">{{ $prodi->nama }}</label>
                                      </div>
                                    </div>
                                  @endforeach
                                </div>
                              </div>

                              @if($fakultas->prodi->isEmpty())
                                <div class="ml-4">
                                  <p class="text-muted mb-0">Belum ada program studi pada fakultas ini. Tambahkan melalui menu
                                    <a href="{{ route('admin.kampus.index') }}">Kampus</a>.</p>
                                </div>
                              @endif
                            </div>
                          @endforeach

                          @if($kampus->fakultas->isEmpty())
                            <p class="text-muted mb-0">Belum ada fakultas pada kampus ini. Tambahkan melalui menu
                              <a href="{{ route('admin.kampus.index') }}">Kampus</a>.</p>
                          @endif
                        </div>
                      </div>
                    </div>
                  @empty
                    <p class="text-muted mb-0">Belum ada kampus terdaftar. Tambahkan melalui menu
                      <a href="{{ route('admin.kampus.index') }}">Kampus</a>.</p>
                  @endforelse
                </div>

                @error('prodi_ids')
                  <div class="alert alert-danger mt-2 mb-0">{{ $message }}</div>
                @enderror
                <small class="text-muted mt-1 d-block" id="prodi-hint"></small>
              </div>
            </div>

            {{-- Footer Buttons --}}
            <div class="card">
              <div class="card-footer text-right">
                <a href="{{ route('admin.beasiswa.index') }}" class="btn btn-outline-secondary mr-2">
                  <i class="fas fa-arrow-left mr-1"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save mr-1"></i> Perbarui Beasiswa
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
</section>
@endsection

@push('script')
<script>
$(document).ready(function() {
  // Buka/tutup semua accordion sekaligus (Bootstrap collapse, tanpa state
  // sendiri di JS).
  $('#btn-buka-semua').on('click', function () {
    $('#prodi-tree .collapse').collapse('show');
  });

  $('#btn-tutup-semua').on('click', function () {
    $('#prodi-tree .collapse').collapse('hide');
  });

  // Pohon centang tiga tingkat: kampus -> fakultas -> prodi (sama seperti
  // `buat.blade.php`). Centang kampus/fakultas adalah TURUNAN dari centang
  // prodinya; yang dikirim ke server hanya `prodi_ids[]`.
  function refreshKampus($kampus) {
    var semuaProdi = $kampus.find('.prodi-check');
    var tercentang = semuaProdi.filter(':checked').length;
    var semua = semuaProdi.length > 0 && tercentang === semuaProdi.length;
    $kampus.find('.kampus-check').prop('checked', semua).prop('indeterminate', !semua && tercentang > 0);
  }

  function refreshFakultas($fakultas) {
    var semuaProdi = $fakultas.find('.prodi-check');
    var tercentang = semuaProdi.filter(':checked').length;
    var semua = semuaProdi.length > 0 && tercentang === semuaProdi.length;
    $fakultas.find('.fakultas-check').prop('checked', semua).prop('indeterminate', !semua && tercentang > 0);
    refreshKampus($fakultas.closest('.kampus-tree'));
  }

  $('#prodi-tree .kampus-tree').each(function() {
    var $kampus = $(this);
    $kampus.find('[data-fakultas-id]').each(function() {
      refreshFakultas($(this));
    });
  });

  $('#prodi-tree').on('change', '.prodi-check', function() {
    refreshFakultas($(this).closest('[data-fakultas-id]'));
    updateProdiHint();
  });

  $('#prodi-tree').on('change', '.fakultas-check', function() {
    var cek = $(this).prop('checked');
    var $fakultas = $(this).closest('[data-fakultas-id]');
    $fakultas.find('.prodi-check').prop('checked', cek);
    refreshKampus($fakultas.closest('.kampus-tree'));
    updateProdiHint();
  });

  $('#prodi-tree').on('change', '.kampus-check', function() {
    var cek = $(this).prop('checked');
    var $kampus = $(this).closest('.kampus-tree');
    $kampus.find('.prodi-check').prop('checked', cek);
    $kampus.find('.fakultas-check').prop('checked', cek);
    updateProdiHint();
  });

  $('#btn-centang-semua').on('click', function() {
    $('.prodi-check').prop('checked', true);
    $('.fakultas-check, .kampus-check').prop('checked', true).prop('indeterminate', false);
    updateProdiHint();
  });

  $('#btn-lepas-semua').on('click', function() {
    $('.prodi-check, .fakultas-check, .kampus-check').prop('checked', false).prop('indeterminate', false);
    updateProdiHint();
  });

  function updateProdiHint() {
    var count = $('#prodi-tree .prodi-check:checked').length;
    $('#prodi-hint').text(count === 0 ? 'Belum ada program studi yang dipilih. Minimal pilih 1.' : count + ' program studi dipilih.');
  }

  updateProdiHint();

  // Helper yang sama dipakai `buat.blade.php`, jadi tampilan dan perilaku
  // date picker identik di kedua form.
  initDatePicker('.flatpickr');
});
</script>
@endpush
