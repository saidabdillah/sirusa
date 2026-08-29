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
                <h5 class="mb-3">Data Pendidikan</h5>
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="fakultas">Fakultas <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('fakultas') is-invalid @enderror" id="fakultas"
                      name="fakultas" value="{{ old('fakultas', $applicant->fakultas) }}" required>
                    @error('fakultas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="prodi">Program Studi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('prodi') is-invalid @enderror" id="prodi"
                      name="prodi" value="{{ old('prodi', $applicant->prodi) }}" required>
                    @error('prodi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="ipk">IPK <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" max="4" class="form-control @error('ipk') is-invalid @enderror" id="ipk" name="ipk" value="{{ old('ipk', $applicant->ipk) }}" required>
                    @error('ipk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="semester">Semester <span class="text-danger">*</span></label>
                    <input type="number" min="1" max="14" class="form-control @error('semester') is-invalid @enderror" id="semester" name="semester" value="{{ old('semester', $applicant->semester) }}" required>
                    @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
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
