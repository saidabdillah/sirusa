@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Lengkapi Pendaftaran</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('user.pendaftaran.index') }}">Pendaftaran Saya</a></div>
        <div class="breadcrumb-item">Lengkapi</div>
      </div>
    </div>

    <div class="section-body">
      @if($applicant->catatan)
        <div class="alert alert-warning">
          <strong><i class="fas fa-exclamation-triangle"></i> Catatan dari Admin:</strong><br>
          {!! nl2br(e($applicant->catatan)) !!}
        </div>
      @endif

      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>Lengkapi Data Pendaftaran</h4>
            </div>
            <form action="{{ route('user.pendaftaran.perbarui', $applicant) }}" method="POST" enctype="multipart/form-data">
              @csrf
              @method('PUT')
              <div class="card-body">
                <div class="alert alert-success">
                  <i class="fas fa-check-circle"></i>
                  <strong>Data pendidikan terisi otomatis dari profil:</strong><br>
                  Fakultas: <strong>{{ $profile->prodi?->fakultas?->nama ?? '-' }}</strong><br>
                  Program Studi: <strong>{{ $profile->prodi?->nama ?? '-' }}</strong><br>
                  IPK: <strong>{{ $profile->ipk ?? '-' }}</strong> &nbsp;|&nbsp;
                  Semester: <strong>{{ $profile->semester ?? '-' }}</strong><br>
                  <small class="text-muted">Jika data di atas perlu diubah, perbarui terlebih dahulu melalui
                    <a href="{{ route('profile') }}">halaman Profil</a>, kemudian revisi pendaftaran kembali.</small>
                </div>

                <h5 class="mb-3 mt-4">Dokumen Pendukung (Kosongkan jika tidak ingin mengganti)</h5>
                @foreach([
                  ['key' => 'dokumen_ktp', 'label' => 'KTP', 'accept' => '.pdf,.jpg,.jpeg,.png'],
                  ['key' => 'dokumen_kk', 'label' => 'Kartu Keluarga', 'accept' => '.pdf,.jpg,.jpeg,.png'],
                  ['key' => 'dokumen_surat_permohonan', 'label' => 'Surat Permohonan', 'accept' => '.pdf,.jpg,.jpeg,.png'],
                  ['key' => 'dokumen_transkrip', 'label' => 'Transkrip Nilai / KHS', 'accept' => '.pdf,.jpg,.jpeg,.png'],
                  ['key' => 'dokumen_surat_aktif', 'label' => 'Surat Aktif Kuliah / KTM', 'accept' => '.pdf,.jpg,.jpeg,.png'],
                  ['key' => 'dokumen_pas_foto', 'label' => 'Pas Foto 3x4', 'accept' => '.jpg,.jpeg,.png'],
                ] as $doc)
                  <div class="form-group">
                    <label for="{{ $doc['key'] }}">{{ $doc['label'] }}</label>
                    @if($applicant->{$doc['key']})
                      <div class="mb-1">
                        <small class="text-muted">File saat ini: {{ basename($applicant->{$doc['key']}) }}</small>
                        <a href="{{ asset('storage/' . $applicant->{$doc['key']}) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                          <i class="fas fa-eye"></i> Lihat
                        </a>
                      </div>
                    @endif
                    <input type="file" class="form-control @error($doc["key"]) is-invalid @enderror" id="{{ $doc['key'] }}" name="{{ $doc['key'] }}" accept="{{ $doc['accept'] }}">
                    <small class="text-muted">Format: {{ str_replace('.', '', $doc['accept']) }}. Maksimal 20MB. Kosongkan jika tidak ingin mengganti.</small>
                    @error($doc['key'])<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                @endforeach

                <div class="form-group">
                  <label for="dokumen_prestasi">Sertifikat Prestasi (opsional)</label>
                  @if($applicant->dokumen_prestasi && count($applicant->dokumen_prestasi) > 0)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ count($applicant->dokumen_prestasi) }} file</small>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_prestasi.*') is-invalid @enderror" id="dokumen_prestasi" name="dokumen_prestasi[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                  <small class="text-muted">Bisa upload multiple file. File baru akan ditambahkan ke daftar yang sudah ada. Maksimal 20MB per file.</small>
                  @error('dokumen_prestasi.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <hr>
                <h5 class="mb-3">KTP & Kartu Keluarga Orang Tua / Wali</h5>
                @php
                  $profile = $applicant->user->profile;
                  $ktpFields = [
                    ['key' => 'ktp_ayah', 'label' => 'KTP Ayah', 'required' => in_array($profile?->status_orang_tua, ['Lengkap', 'Piatu'])],
                    ['key' => 'ktp_ibu', 'label' => 'KTP Ibu', 'required' => in_array($profile?->status_orang_tua, ['Lengkap', 'Yatim'])],
                    ['key' => 'ktp_wali', 'label' => 'KTP Wali', 'required' => $profile?->status_orang_tua === 'Yatim Piatu'],
                    ['key' => 'kk_wali', 'label' => 'Kartu Keluarga Wali', 'required' => $profile?->status_orang_tua === 'Yatim Piatu'],
                  ];
                @endphp
                @foreach($ktpFields as $ktp)
                  <div class="form-group">
                    <label for="{{ $ktp['key'] }}">{{ $ktp['label'] }}
                      @if($ktp['required'])<span class="text-danger">*</span>@endif
                    </label>
                    @if($applicant->{$ktp['key']})
                      <div class="mb-1">
                        <small class="text-muted">File saat ini: {{ basename($applicant->{$ktp['key']}) }}</small>
                        <a href="{{ asset('storage/' . $applicant->{$ktp['key']}) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                          <i class="fas fa-eye"></i> Lihat
                        </a>
                      </div>
                    @endif
                    <input type="file" class="form-control @error($ktp['key']) is-invalid @enderror" id="{{ $ktp['key'] }}" name="{{ $ktp['key'] }}" accept=".pdf,.jpg,.jpeg,.png" {{ ($ktp['required'] && ! $applicant->{$ktp['key']}) ? 'required' : '' }}>
                    <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB. Kosongkan jika tidak ingin mengganti.</small>
                    @error($ktp['key'])<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                @endforeach
              </div>
              <div class="card-footer text-right">
                <a href="{{ route('user.pendaftaran.lihat', $applicant) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Perbarui Pendaftaran</button>
              </div>
            </form>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h4>Status Saat Ini</h4>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <strong>Status:</strong><br>
                <span class="badge badge-secondary">Revisi</span>
              </div>
              @if($applicant->catatan)
                <div>
                  <strong>Catatan Admin:</strong><br>
                  <p class="mb-0">{!! nl2br(e($applicant->catatan)) !!}</p>
                </div>
              @endif
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h4>Ringkasan Beasiswa</h4>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <strong>Nama Beasiswa:</strong><br>
                {{ $applicant->beasiswa->nama }}
              </div>
              <div class="mb-2">
                <strong>Penyedia:</strong><br>
                {{ $applicant->beasiswa->kampus }}
              </div>
              <div class="mb-2">
                <strong>Batas Waktu:</strong><br>
                {{ $applicant->beasiswa->batas_waktu->translatedFormat('d F Y') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection
