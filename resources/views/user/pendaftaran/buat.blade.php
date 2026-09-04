@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Ajukan Beasiswa</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('user.beasiswa.index') }}">Daftar Beasiswa</a></div>
      <div class="breadcrumb-item">Ajukan</div>
    </div>
  </div>

  @if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
  @endif

  <div class="section-body">
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h4>Upload Dokumen Pendukung</h4>
          </div>
          <form action="{{ route('user.pendaftaran.simpan') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="beasiswa_id" value="{{ $scholarship->id }}">
            <div class="card-body">
              <div class="alert alert-info">
                <strong>Beasiswa yang Dipilih:</strong> {{ $scholarship->nama }} ({{ $scholarship->kampus }})
              </div>

              <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <strong>Data pendidikan Anda sudah terisi otomatis dari profil:</strong><br>
                Fakultas: <strong>{{ $profile->prodi?->fakultas?->nama ?? '-' }}</strong><br>
                Program Studi: <strong>{{ $profile->prodi?->nama ?? '-' }}</strong><br>
                IPK: <strong>{{ $profile->ipk ?? '-' }}</strong> &nbsp;|&nbsp;
                Semester: <strong>{{ $profile->semester ?? '-' }}</strong>
                @if(! $profile->prodi_id)
                <div class="mt-2">
                  <a href="{{ route('profile') }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-user-edit"></i> Lengkapi Profil
                  </a>
                </div>
                @endif
              </div>

              <h5 class="mb-3">Dokumen Pendukung</h5>
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
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB. Template dapat diunduh dari
                  menu Template Surat.</small>
                @error('dokumen_surat_permohonan')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                <label for="dokumen_surat_pernyataan">Surat Pernyataan Tidak Menerima Beasiswa Lain <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_surat_pernyataan') is-invalid @enderror"
                  id="dokumen_surat_pernyataan" name="dokumen_surat_pernyataan" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB.</small>
                @error('dokumen_surat_pernyataan')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_sktm">Surat Keterangan Tidak Mampu (SKTM) <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_sktm') is-invalid @enderror"
                  id="dokumen_sktm" name="dokumen_sktm" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB.</small>
                @error('dokumen_sktm')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_bukti_ukt">Bukti Pembayaran UKT/SPP <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_bukti_ukt') is-invalid @enderror"
                  id="dokumen_bukti_ukt" name="dokumen_bukti_ukt" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB.</small>
                @error('dokumen_bukti_ukt')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_prestasi">Sertifikat Prestasi (opsional)</label>
                <input type="file" class="form-control @error('dokumen_prestasi.*') is-invalid @enderror"
                  id="dokumen_prestasi" name="dokumen_prestasi[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                <small class="text-muted">Bisa upload multiple file. Format: PDF, JPG, JPEG, PNG. Maksimal 20MB per
                  file.</small>
                @error('dokumen_prestasi.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <hr>
              <h5 class="mb-3">KTP Orang Tua / Wali</h5>
              <input type="hidden" name="status_orang_tua" value="{{ $profile->status_orang_tua }}">
              <div class="alert alert-info">
                KTP Orang Tua / Wali diwajibkan sesuai dengan status orang tua Anda di profil.
                @if(! $profile->status_orang_tua)
                  <div class="mt-2">
                    <a href="{{ route('profile') }}" class="btn btn-warning btn-sm">
                      <i class="fas fa-user-edit"></i> Lengkapi Status Orang Tua di Profil
                    </a>
                  </div>
                @endif
              </div>

              <div class="form-group">
                <label for="ktp_ayah">KTP Ayah @if(in_array($profile->status_orang_tua, ['Lengkap', 'Piatu']))<span class="text-danger">*</span>@endif</label>
                <input type="file" class="form-control @error('ktp_ayah') is-invalid @enderror"
                  id="ktp_ayah" name="ktp_ayah" accept=".pdf,.jpg,.jpeg,.png"
                  {{ in_array($profile->status_orang_tua, ['Lengkap', 'Piatu']) ? 'required' : '' }}>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('ktp_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="ktp_ibu">KTP Ibu @if(in_array($profile->status_orang_tua, ['Lengkap', 'Yatim']))<span class="text-danger">*</span>@endif</label>
                <input type="file" class="form-control @error('ktp_ibu') is-invalid @enderror"
                  id="ktp_ibu" name="ktp_ibu" accept=".pdf,.jpg,.jpeg,.png"
                  {{ in_array($profile->status_orang_tua, ['Lengkap', 'Yatim']) ? 'required' : '' }}>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('ktp_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="ktp_wali">KTP Wali @if($profile->status_orang_tua === 'Yatim Piatu')<span class="text-danger">*</span>@endif</label>
                <input type="file" class="form-control @error('ktp_wali') is-invalid @enderror"
                  id="ktp_wali" name="ktp_wali" accept=".pdf,.jpg,.jpeg,.png"
                  {{ $profile->status_orang_tua === 'Yatim Piatu' ? 'required' : '' }}>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('ktp_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="kk_wali">Kartu Keluarga Wali @if($profile->status_orang_tua === 'Yatim Piatu')<span class="text-danger">*</span>@endif</label>
                <input type="file" class="form-control @error('kk_wali') is-invalid @enderror"
                  id="kk_wali" name="kk_wali" accept=".pdf,.jpg,.jpeg,.png"
                  {{ $profile->status_orang_tua === 'Yatim Piatu' ? 'required' : '' }}>
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('kk_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="card-footer text-right">
              <a href="{{ route('user.beasiswa.lihat', $scholarship) }}" class="btn btn-secondary">Batal</a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Kirim Pendaftaran
              </button>
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
            <div class="mb-2">
              <strong>Sisa Kuota:</strong><br>
              {{ $scholarship->sisaKuota() }} orang
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h4>Petunjuk</h4>
          </div>
          <div class="card-body">
            <ul class="mb-0">
              <li>Data pendidikan diambil otomatis dari profil Anda.</li>
              <li>Pastikan profil (khususnya Program Studi, IPK, dan Semester) sudah benar.</li>
              <li>Upload dokumen yang diperlukan dalam format PDF/JPG/PNG.</li>
              <li>Ukuran setiap dokumen maksimal 20MB.</li>
              <li>Pastikan semua data sudah benar sebelum mengirim.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection