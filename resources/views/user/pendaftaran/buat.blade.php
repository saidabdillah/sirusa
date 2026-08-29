@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Formulir Pendaftaran</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('user.pendaftaran.index') }}">Daftar Beasiswa</a></div>
      <div class="breadcrumb-item">Daftar</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h4>Formulir Pendaftaran Beasiswa</h4>
          </div>
          <form action="{{ route('user.pendaftaran.simpan') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="beasiswa_id" value="{{ $scholarship->id }}">
            <div class="card-body">
              <div class="alert alert-info">
                <strong>Beasiswa yang Dipilih:</strong> {{ $scholarship->nama }} ({{ $scholarship->kampus }})
              </div>

              <h5 class="mb-3">Data Pendidikan</h5>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="fakultas">Fakultas <span class="text-danger">*</span></label>
                  <select class="form-control @error('fakultas') is-invalid @enderror" id="fakultas" name="fakultas" required>
                    <option value="">Pilih Fakultas</option>
                    @foreach($scholarship->fakultas as $f)
                      <option value="{{ $f->nama }}" {{ old('fakultas') === $f->nama ? 'selected' : '' }}>{{ $f->nama }}</option>
                    @endforeach
                  </select>
                  @error('fakultas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="prodi">Program Studi <span class="text-danger">*</span></label>
                  <select class="form-control @error('prodi') is-invalid @enderror" id="prodi" name="prodi" required>
                    <option value="">Pilih Program Studi</option>
                  </select>
                  @error('prodi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="ipk">IPK <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" min="0" max="4"
                    class="form-control @error('ipk') is-invalid @enderror" id="ipk" name="ipk" value="{{ old('ipk') }}"
                    required>
                  @error('ipk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="semester">Semester <span class="text-danger">*</span></label>
                  <input type="number" min="1" max="14" class="form-control @error('semester') is-invalid @enderror"
                    id="semester" name="semester" value="{{ old('semester') }}" required>
                  @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <h5 class="mb-3 mt-4">Dokumen Pendukung</h5>
              <div class="form-group">
                <label for="dokumen_ktp">KTP <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_ktp') is-invalid @enderror" id="dokumen_ktp"
                  name="dokumen_ktp" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB.</small>
                @error('dokumen_ktp')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_kk">Kartu Keluarga <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_kk') is-invalid @enderror" id="dokumen_kk"
                  name="dokumen_kk" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB.</small>
                @error('dokumen_kk')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_surat_permohonan">Surat Permohonan <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_surat_permohonan') is-invalid @enderror"
                  id="dokumen_surat_permohonan" name="dokumen_surat_permohonan" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB.</small>
                @error('dokumen_surat_permohonan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if(route('download.application-letter'))
                <a href="{{ route('download.application-letter') }}" target="_blank" class="mt-1 d-inline-block">
                  <i class="fas fa-download"></i> Download Surat Permohonan
                </a>
                @endif
              </div>
              <div class="form-group">
                <label for="dokumen_transkrip">Transkrip Nilai / KHS <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_transkrip') is-invalid @enderror"
                  id="dokumen_transkrip" name="dokumen_transkrip" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB.</small>
                @error('dokumen_transkrip')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_surat_aktif">Surat Aktif Kuliah / KTM <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_surat_aktif') is-invalid @enderror"
                  id="dokumen_surat_aktif" name="dokumen_surat_aktif" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB.</small>
                @error('dokumen_surat_aktif')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_pas_foto">Pas Foto 3x4 <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_pas_foto') is-invalid @enderror"
                  id="dokumen_pas_foto" name="dokumen_pas_foto" accept=".jpg,.jpeg,.png" required>
                <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal 20MB.</small>
                @error('dokumen_pas_foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_prestasi">Sertifikat Prestasi (opsional)</label>
                <input type="file" class="form-control @error('dokumen_prestasi.*') is-invalid @enderror"
                  id="dokumen_prestasi" name="dokumen_prestasi[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                <small class="text-muted">Bisa upload multiple file. Format: PDF, JPG, JPEG, PNG. Maksimal 20MB per
                  file.</small>
                @error('dokumen_prestasi.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="card-footer text-right">
              <a href="{{ route('user.beasiswa.lihat', $scholarship) }}" class="btn btn-secondary">Batal</a>
              <button type="submit" class="btn btn-primary">Kirim Pendaftaran</button>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card">
          <div class="card-header">
            <h4>Ringkasan Beasiswa</h4>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <strong>Nama Beasiswa:</strong><br>
              {{ $scholarship->nama }}
            </div>
            <div class="mb-2">
              <strong>Penyedia:</strong><br>
              {{ $scholarship->kampus }}
            </div>
            <div class="mb-2">
              <strong>Tingkat Gelar:</strong><br>
              {{ $scholarship->tingkat_gelar ?? '-' }}
            </div>
            <div class="mb-2">
              <strong>Cakupan:</strong><br>
              {{ $scholarship->cakupan ?? '-' }}
            </div>
            <div class="mb-2">
              <strong>Batas Waktu:</strong><br>
              {{ $scholarship->batas_waktu->translatedFormat('d F Y') }}
            </div>
          </div>
        </div>

        @if($profile)
        <div class="card">
          <div class="card-header">
            <h4>Data Profil Anda</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-info mb-3">
              <i class="fas fa-info-circle"></i> Data profil akan digunakan otomatis oleh admin untuk verifikasi.
            </div>
            <div class="mb-2">
              <strong>Nama:</strong> {{ $profile->nama_lengkap ?? '-' }}
            </div>
            <div class="mb-2">
              <strong>NIK:</strong> {{ $profile->nik ?? '-' }}
            </div>
            <div class="mb-2">
              <strong>Email:</strong> {{ auth()->user()->email }}
            </div>
            <div class="mb-2">
              <strong>Telepon:</strong> {{ $profile->telepon ?? '-' }}
            </div>
            <div class="mb-2">
              <strong>Alamat:</strong> {{ $profile->alamat ?? '-' }}
            </div>
            <small class="text-muted">Pastikan profil Anda sudah lengkap. <a href="{{ route('profile') }}">Update Profil</a></small>
          </div>
        </div>
        @endif

        <div class="card">
          <div class="card-header">
            <h4>Petunjuk</h4>
          </div>
          <div class="card-body">
            <ul class="mb-0">
              <li>Isi data pendidikan dengan lengkap dan benar.</li>
              <li>Upload dokumen yang diperlukan dalam format PDF/JPG/PNG.</li>
              <li>Ukuran setiap dokumen maksimal 20MB.</li>
              <li>Surat permohonan dapat didownload dari link yang tersedia.</li>
              <li>Pastikan semua data sudah benar sebelum mengirim.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('script')
<script>
$(document).ready(function() {
  var fakultasData = @json($scholarship->fakultas->map(fn($f) => ['nama' => $f->nama, 'prodi' => $f->prodi->pluck('nama')]));
  var oldFakultas = '{{ old('fakultas') }}';
  var oldProdi = '{{ old('prodi') }}';

  function loadProdi(fakultasNama, selectedProdi) {
    var $prodi = $('#prodi');
    $prodi.html('<option value="">Pilih Program Studi</option>');
    var fakultas = fakultasData.find(function(f) { return f.nama === fakultasNama; });
    if (fakultas) {
      fakultas.prodi.forEach(function(prodi) {
        var selected = prodi === selectedProdi ? 'selected' : '';
        $prodi.append('<option value="' + prodi + '" ' + selected + '>' + prodi + '</option>');
      });
    }
  }

  $('#fakultas').on('change', function() {
    loadProdi($(this).val(), '');
  });

  if (oldFakultas) {
    $('#fakultas').val(oldFakultas);
    loadProdi(oldFakultas, oldProdi);
  }
});
</script>
@endpush
