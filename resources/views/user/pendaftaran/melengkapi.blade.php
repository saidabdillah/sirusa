@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Lengkapi Berkas Tahap 2</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active"><a href="{{ route('user.pendaftaran.index') }}">Pendaftaran Saya</a></div>
        <div class="breadcrumb-item">Lengkapi Berkas</div>
      </div>
    </div>

    <div class="section-body">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h4>Unggah Berkas Tahap 2</h4>
            </div>
            <form action="{{ route('user.pendaftaran.simpan-melengkapi', $applicant) }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="card-body">
                <div class="alert alert-info">
                  <i class="fas fa-info-circle"></i> Pendaftaran Anda telah diterima. Silakan lengkapi berkas Tahap 2 di bawah ini.
                </div>

                <div class="alert alert-success">
                  <strong>Beasiswa:</strong> {{ $applicant->beasiswa->nama }} ({{ $applicant->beasiswa->kampus }})
                </div>

                <h5 class="mb-3">Berkas yang Diperlukan</h5>

                <div class="form-group">
                  <label for="dokumen_surat_pernyataan">Surat Pernyataan Tidak Menerima Beasiswa Lain <span class="text-danger">*</span></label>
                  @if($applicant->dokumen_surat_pernyataan)
                    <div class="mb-1">
                      <small class="text-success"><i class="fas fa-check-circle"></i> Sudah diunggah: {{ basename($applicant->dokumen_surat_pernyataan) }}</small>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_surat_pernyataan') is-invalid @enderror" id="dokumen_surat_pernyataan" name="dokumen_surat_pernyataan" accept=".pdf,.jpg,.jpeg,.png" {{ $applicant->dokumen_surat_pernyataan ? '' : 'required' }}>
                  <small class="text-muted">Surat pernyataan bermeterai bahwa tidak sedang menerima beasiswa lain. Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                  @error('dokumen_surat_pernyataan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                  <label for="dokumen_sktm">Surat Keterangan Tidak Mampu (SKTM) <span class="text-danger">*</span></label>
                  @if($applicant->dokumen_sktm)
                    <div class="mb-1">
                      <small class="text-success"><i class="fas fa-check-circle"></i> Sudah diunggah: {{ basename($applicant->dokumen_sktm) }}</small>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_sktm') is-invalid @enderror" id="dokumen_sktm" name="dokumen_sktm" accept=".pdf,.jpg,.jpeg,.png" {{ $applicant->dokumen_sktm ? '' : 'required' }}>
                  <small class="text-muted">Surat keterangan tidak mampu dari Kelurahan/Desa. Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                  @error('dokumen_sktm')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group mb-0">
                  <label for="dokumen_bukti_ukt">Bukti Pembayaran UKT/SPP <span class="text-danger">*</span></label>
                  @if($applicant->dokumen_bukti_ukt)
                    <div class="mb-1">
                      <small class="text-success"><i class="fas fa-check-circle"></i> Sudah diunggah: {{ basename($applicant->dokumen_bukti_ukt) }}</small>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_bukti_ukt') is-invalid @enderror" id="dokumen_bukti_ukt" name="dokumen_bukti_ukt" accept=".pdf,.jpg,.jpeg,.png" {{ $applicant->dokumen_bukti_ukt ? '' : 'required' }}>
                  <small class="text-muted">Bukti pembayaran UKT atau SPP dari kampus. Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                  @error('dokumen_bukti_ukt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="card-footer text-right">
                <a href="{{ route('user.pendaftaran.lihat', $applicant) }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary">Kirim Berkas Tahap 2</button>
              </div>
            </form>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h4>Ringkasan</h4>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <strong>Beasiswa:</strong><br>
                {{ $applicant->beasiswa->nama }}
              </div>
              <div class="mb-2">
                <strong>Penyedia:</strong><br>
                {{ $applicant->beasiswa->kampus }}
              </div>
              <div class="mb-2">
                <strong>Nama:</strong><br>
                {{ $applicant->user->profile->nama_lengkap ?? '-' }}
              </div>
              <div class="mb-2">
                <strong>IPK:</strong><br>
                {{ $applicant->ipk }}
              </div>
              <div class="mb-2">
                <strong>Semester:</strong><br>
                {{ $applicant->semester }}
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h4>Petunjuk</h4>
            </div>
            <div class="card-body">
              <ul class="mb-0">
                <li>Surat pernyataan harus bermeterai.</li>
                <li>SKTM dari Kelurahan/Desa setempat.</li>
                <li>Bukti UKT/SPP dari kampus.</li>
                <li>Semua dokumen maksimal 2MB.</li>
                <li>Jika sudah pernah upload, file baru akan menggantikan yang lama.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection
