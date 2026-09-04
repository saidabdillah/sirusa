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
                <div class="form-group">
                  <label for="dokumen_ktp">KTP</label>
                  @if($applicant->dokumen_ktp)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->dokumen_ktp) }}</small>
                      <a href="{{ asset('storage/' . $applicant->dokumen_ktp) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_ktp') is-invalid @enderror" id="dokumen_ktp"
                    name="dokumen_ktp" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('dokumen_ktp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                  <label for="dokumen_kk">Kartu Keluarga</label>
                  @if($applicant->dokumen_kk)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->dokumen_kk) }}</small>
                      <a href="{{ asset('storage/' . $applicant->dokumen_kk) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_kk') is-invalid @enderror" id="dokumen_kk"
                    name="dokumen_kk" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('dokumen_kk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                  <label for="dokumen_akta">Akta Kelahiran</label>
                  @if($applicant->dokumen_akta)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->dokumen_akta) }}</small>
                      <a href="{{ asset('storage/' . $applicant->dokumen_akta) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_akta') is-invalid @enderror" id="dokumen_akta"
                    name="dokumen_akta" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('dokumen_akta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                  <label for="dokumen_surat_permohonan">Surat Permohonan</label>
                  @if($applicant->dokumen_surat_permohonan)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->dokumen_surat_permohonan) }}</small>
                      <a href="{{ asset('storage/' . $applicant->dokumen_surat_permohonan) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_surat_permohonan') is-invalid @enderror"
                    id="dokumen_surat_permohonan" name="dokumen_surat_permohonan" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('dokumen_surat_permohonan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                  <label for="dokumen_transkrip">Transkrip Nilai / KHS</label>
                  @if($applicant->dokumen_transkrip)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->dokumen_transkrip) }}</small>
                      <a href="{{ asset('storage/' . $applicant->dokumen_transkrip) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_transkrip') is-invalid @enderror"
                    id="dokumen_transkrip" name="dokumen_transkrip" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('dokumen_transkrip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                  <label for="dokumen_surat_aktif">Surat Aktif Kuliah / KTM</label>
                  @if($applicant->dokumen_surat_aktif)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->dokumen_surat_aktif) }}</small>
                      <a href="{{ asset('storage/' . $applicant->dokumen_surat_aktif) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_surat_aktif') is-invalid @enderror"
                    id="dokumen_surat_aktif" name="dokumen_surat_aktif" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 20MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('dokumen_surat_aktif')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                  <label for="dokumen_pas_foto">Pas Foto 3x4</label>
                  @if($applicant->dokumen_pas_foto)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->dokumen_pas_foto) }}</small>
                      <a href="{{ asset('storage/' . $applicant->dokumen_pas_foto) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('dokumen_pas_foto') is-invalid @enderror"
                    id="dokumen_pas_foto" name="dokumen_pas_foto" accept=".jpg,.jpeg,.png">
                  <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal 20MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('dokumen_pas_foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

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
                @endphp
                @if(in_array($profile?->status_orang_tua, ['Lengkap', 'Piatu']))
                <div class="form-group">
                  <label for="ktp_ayah">KTP Ayah <span class="text-danger">*</span></label>
                  @if($applicant->ktp_ayah)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->ktp_ayah) }}</small>
                      <a href="{{ asset('storage/' . $applicant->ktp_ayah) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('ktp_ayah') is-invalid @enderror" id="ktp_ayah"
                    name="ktp_ayah" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('ktp_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @endif

                @if(in_array($profile?->status_orang_tua, ['Lengkap', 'Yatim']))
                <div class="form-group">
                  <label for="ktp_ibu">KTP Ibu <span class="text-danger">*</span></label>
                  @if($applicant->ktp_ibu)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->ktp_ibu) }}</small>
                      <a href="{{ asset('storage/' . $applicant->ktp_ibu) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('ktp_ibu') is-invalid @enderror" id="ktp_ibu"
                    name="ktp_ibu" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('ktp_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @endif

                @if(($profile?->status_orang_tua ?? '') === 'Yatim Piatu')
                <div class="form-group">
                  <label for="ktp_wali">KTP Wali <span class="text-danger">*</span></label>
                  @if($applicant->ktp_wali)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->ktp_wali) }}</small>
                      <a href="{{ asset('storage/' . $applicant->ktp_wali) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('ktp_wali') is-invalid @enderror" id="ktp_wali"
                    name="ktp_wali" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('ktp_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @endif

                @if(($profile?->status_orang_tua ?? '') === 'Yatim Piatu')
                <div class="form-group">
                  <label for="kk_wali">Kartu Keluarga Wali <span class="text-danger">*</span></label>
                  @if($applicant->kk_wali)
                    <div class="mb-1">
                      <small class="text-muted">File saat ini: {{ basename($applicant->kk_wali) }}</small>
                      <a href="{{ asset('storage/' . $applicant->kk_wali) }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </div>
                  @endif
                  <input type="file" class="form-control @error('kk_wali') is-invalid @enderror" id="kk_wali"
                    name="kk_wali" accept=".pdf,.jpg,.jpeg,.png">
                  <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB. Kosongkan jika tidak ingin
                    mengganti.</small>
                  @error('kk_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @endif
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
