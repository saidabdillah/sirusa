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

  <div class="section-body">
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h4>Upload Dokumen Pendukung</h4>
          </div>
          <form action="{{ route('user.pendaftaran.simpan') }}" method="POST" enctype="multipart/form-data" id="formAjukan">
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

              <h5 class="mb-3">Dokumen Diri Sendiri</h5>
              <div class="form-group">
                <label for="dokumen_ktp">KTP <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_ktp') is-invalid @enderror" id="dokumen_ktp"
                  name="dokumen_ktp" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('dokumen_ktp')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_kk">Kartu Keluarga <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_kk') is-invalid @enderror" id="dokumen_kk"
                  name="dokumen_kk" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('dokumen_kk')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_akta">Akta Kelahiran <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_akta') is-invalid @enderror" id="dokumen_akta"
                  name="dokumen_akta" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('dokumen_akta')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_pas_foto">Pas Foto 3x4 <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_pas_foto') is-invalid @enderror"
                  id="dokumen_pas_foto" name="dokumen_pas_foto" accept=".jpg,.jpeg,.png">
                <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('dokumen_pas_foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_sktm">Surat Keterangan Tidak Mampu (SKTM) <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_sktm') is-invalid @enderror"
                  id="dokumen_sktm" name="dokumen_sktm" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('dokumen_sktm')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_prestasi">Sertifikat Prestasi (opsional)</label>
                <input type="file" class="form-control @error('dokumen_prestasi.*') is-invalid @enderror"
                  id="dokumen_prestasi" name="dokumen_prestasi[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                <small class="text-muted">Bisa upload multiple file. Format: PDF, JPG, JPEG, PNG. Maksimal 2MB per
                  file.</small>
                @error('dokumen_prestasi.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <hr>
              <h5 class="mb-3">Dokumen untuk Kampus</h5>
              <div class="form-group">
                <label for="dokumen_surat_permohonan">Surat Permohonan <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_surat_permohonan') is-invalid @enderror"
                  id="dokumen_surat_permohonan" name="dokumen_surat_permohonan" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB. Template:
                  <a href="{{ route('download.application-letter') }}" target="_blank">Unduh Template</a>.</small>
                @error('dokumen_surat_permohonan')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_transkrip">Transkrip Nilai / KHS <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_transkrip') is-invalid @enderror"
                  id="dokumen_transkrip" name="dokumen_transkrip" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('dokumen_transkrip')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_surat_aktif">Surat Aktif Kuliah / KTM @if(($scholarship->semester_minimal ?? 1) >= 2)<span class="text-danger">*</span>@endif</label>
                <input type="file" class="form-control @error('dokumen_surat_aktif') is-invalid @enderror"
                  id="dokumen_surat_aktif" name="dokumen_surat_aktif" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB. @if(($scholarship->semester_minimal ?? 1) >= 2)Wajib untuk beasiswa ini.@else Tidak wajib untuk beasiswa ini.@endif</small>
                @error('dokumen_surat_aktif')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_surat_pernyataan">Surat Pernyataan Tidak Menerima Beasiswa Lain <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_surat_pernyataan') is-invalid @enderror"
                  id="dokumen_surat_pernyataan" name="dokumen_surat_pernyataan" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('dokumen_surat_pernyataan')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="dokumen_bukti_ukt">Bukti Pembayaran UKT/SPP <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('dokumen_bukti_ukt') is-invalid @enderror"
                  id="dokumen_bukti_ukt" name="dokumen_bukti_ukt" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('dokumen_bukti_ukt')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <hr>
              <h5 class="mb-3">Dokumen Orang Tua / Wali</h5>
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

              @php $statusOrangTua = $profile->status_orang_tua; @endphp
              @if(in_array($statusOrangTua, ['Lengkap', 'Piatu']))
              <div class="form-group">
                <label for="ktp_ayah">KTP Ayah <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('ktp_ayah') is-invalid @enderror"
                  id="ktp_ayah" name="ktp_ayah" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('ktp_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              @endif
              @if(in_array($statusOrangTua, ['Lengkap', 'Yatim']))
              <div class="form-group">
                <label for="ktp_ibu">KTP Ibu <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('ktp_ibu') is-invalid @enderror"
                  id="ktp_ibu" name="ktp_ibu" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('ktp_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              @endif
              @if($statusOrangTua === 'Yatim Piatu')
              <div class="form-group">
                <label for="ktp_wali">KTP Wali <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('ktp_wali') is-invalid @enderror"
                  id="ktp_wali" name="ktp_wali" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('ktp_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label for="kk_wali">Kartu Keluarga Wali <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('kk_wali') is-invalid @enderror"
                  id="kk_wali" name="kk_wali" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 2MB.</small>
                @error('kk_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              @endif
            </div>
            <div class="card-footer text-right">
              <a href="{{ route('user.beasiswa.lihat', $scholarship) }}" class="btn btn-secondary mr-2">Batal</a>
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
              <li>Ukuran setiap dokumen maksimal 2MB.</li>
              <li>Pastikan semua data sudah benar sebelum mengirim.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@push('script')
<script>
  $(function () {
    var MAX_BYTES = 2 * 1024 * 1024;
    var allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
    var imageOnly = ['dokumen_pas_foto'];
    var labelMap = {
      dokumen_ktp: 'KTP',
      dokumen_kk: 'Kartu Keluarga',
      dokumen_akta: 'Akta Kelahiran',
      dokumen_surat_permohonan: 'Surat Permohonan',
      dokumen_transkrip: 'Transkrip Nilai / KHS',
      dokumen_surat_aktif: 'Surat Aktif Kuliah / KTM',
      dokumen_pas_foto: 'Pas Foto 3x4',
      dokumen_prestasi: 'Sertifikat Prestasi',
      dokumen_surat_pernyataan: 'Surat Pernyataan',
      dokumen_sktm: 'SKTM',
      dokumen_bukti_ukt: 'Bukti UKT/SPP',
      ktp_ayah: 'KTP Ayah',
      ktp_ibu: 'KTP Ibu',
      ktp_wali: 'KTP Wali',
      kk_wali: 'KK Wali'
    };

    function fileExt(name) {
      return (name.split('.').pop() || '').toLowerCase();
    }

    function validateFiles($input) {
      var name = ($input.attr('name') || '').replace(/\[\]$/, '');
      var files = $input[0].files || [];
      var errors = [];
      for (var i = 0; i < files.length; i++) {
        var file = files[i];
        var label = labelMap[name] || 'File';
        if (file.size > MAX_BYTES) {
          errors.push(label + ': ukuran file melebihi 2MB.');
        } else if (imageOnly.indexOf(name) !== -1 && allowedExt.indexOf(fileExt(file.name)) === -1) {
          errors.push(label + ': format harus JPG, JPEG, atau PNG.');
        } else if (allowedExt.indexOf(fileExt(file.name)) === -1) {
          errors.push(label + ': format harus PDF, JPG, JPEG, atau PNG.');
        }
      }
      return errors;
    }

    var $form = $('#formAjukan');
    var $submitBtn = $form.find('button[type="submit"]').first();
    var originalBtnHtml = $submitBtn.html();

    $form.on('submit', function (e) {
      var errors = [];
      var firstInvalid = null;

      $form.find('input[type="file"]').each(function () {
        var $input = $(this);
        var inputErrors = validateFiles($input);
        if (inputErrors.length) {
          $input.addClass('is-invalid');
          if (!firstInvalid) firstInvalid = $input;
          errors = errors.concat(inputErrors);
        } else {
          $input.removeClass('is-invalid');
        }
      });

      if (errors.length) {
        e.preventDefault();
        $submitBtn.prop('disabled', false).html(originalBtnHtml);
        $form.find('a.btn.disabled').removeClass('disabled');
        var list = errors.map(function (msg) { return '- ' + msg; }).join('<br>');
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Ada file yang belum sesuai',
            html: list + '<br><br>Periksa kembali file sebelum mengirim.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6'
          });
        } else {
          alert('Ada file yang belum sesuai:\n' + errors.join('\n'));
        }
        if (firstInvalid) firstInvalid.focus();
      }
    });

    $form.on('change', 'input[type="file"]', function () {
      $(this).removeClass('is-invalid');
    });
  });
</script>
@endpush
@endsection
