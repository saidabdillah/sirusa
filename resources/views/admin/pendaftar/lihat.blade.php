@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Detail Pendaftar</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.pendaftar.index') }}">Pendaftar</a></div>
      <div class="breadcrumb-item">Detail</div>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
  @endif

  <div class="section-body">
    <div class="row">
      {{-- LEFT COLUMN --}}
      <div class="col-lg-8">
        {{-- Card 1: Data Diri --}}
        @php $profile = optional($applicant->user->profile); @endphp
        <div class="card">
          <div class="card-header">
            <h4>Data Diri Pendaftar</h4>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-4">
                <strong>Nama Lengkap</strong>
                <p class="mb-0">{{ $profile->nama_lengkap ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Username</strong>
                <p class="mb-0">{{ $applicant->user->username ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>NIK</strong>
                <p class="mb-0">{{ $profile->nik ?? '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-4">
                <strong>Tempat, Tanggal Lahir</strong>
                <p class="mb-0">{{ $profile->tempat_lahir ?? '-' }}, {{ $profile->tanggal_lahir ?
                  $profile->tanggal_lahir->translatedFormat('d F Y') : '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Jenis Kelamin</strong>
                <p class="mb-0">{{ $profile->jenis_kelamin ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Email</strong>
                <p class="mb-0">{{ $applicant->user->email ?? '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-4">
                <strong>Agama</strong>
                <p class="mb-0">{{ $profile->agama ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Telepon</strong>
                <p class="mb-0">{{ $profile->telepon ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Kabupaten</strong>
                <p class="mb-0">{{ $profile->kabupaten_kota ?: 'Balangan' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-4">
                <strong>Kecamatan</strong>
                <p class="mb-0">{{ $profile->kecamatan ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Desa/Kelurahan</strong>
                <p class="mb-0">{{ $profile->desa_kelurahan ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Alamat Detail</strong>
                <p class="mb-0">{{ $profile->alamat ?? '-' }}</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Card 1b: Data Orang Tua --}}
        <div class="card">
          <div class="card-header">
            <h4>Data Orang Tua / Wali</h4>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Status Orang Tua</strong>
                <p class="mb-0">{{ $profile->status_orang_tua ?? '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-12">
                <h6 class="text-muted">Ayah</h6>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-4">
                <strong>Nama</strong>
                <p class="mb-0">{{ $profile->nama_ayah ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Pekerjaan</strong>
                <p class="mb-0">{{ $profile->pekerjaan_ayah ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Penghasilan</strong>
                <p class="mb-0">{{ $profile->penghasilan_ayah ?? '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-12">
                <h6 class="text-muted">Ibu</h6>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-4">
                <strong>Nama</strong>
                <p class="mb-0">{{ $profile->nama_ibu ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Pekerjaan</strong>
                <p class="mb-0">{{ $profile->pekerjaan_ibu ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Penghasilan</strong>
                <p class="mb-0">{{ $profile->penghasilan_ibu ?? '-' }}</p>
              </div>
            </div>
            @if($profile->nama_wali)
            <div class="row mb-3">
              <div class="col-md-12">
                <h6 class="text-muted">Wali</h6>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-3">
                <strong>Nama</strong>
                <p class="mb-0">{{ $profile->nama_wali }}</p>
              </div>
              <div class="col-md-3">
                <strong>Hubungan</strong>
                <p class="mb-0">{{ $profile->hubungan_wali ?? '-' }}</p>
              </div>
              <div class="col-md-3">
                <strong>Pekerjaan</strong>
                <p class="mb-0">{{ $profile->pekerjaan_wali ?? '-' }}</p>
              </div>
              <div class="col-md-3">
                <strong>Penghasilan</strong>
                <p class="mb-0">{{ $profile->penghasilan_wali ?? '-' }}</p>
              </div>
            </div>
            @endif
          </div>
        </div>

        {{-- Card 2: Data Pendidikan --}}
        <div class="card">
          <div class="card-header">
            <h4>Data Pendidikan</h4>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Fakultas</strong>
                <p class="mb-0">{{ $applicant->fakultas ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <strong>Program Studi</strong>
                <p class="mb-0">{{ $applicant->prodi ?? '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>IPK</strong>
                <p class="mb-0">{{ $applicant->ipk ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <strong>Semester</strong>
                <p class="mb-0">{{ $applicant->semester ?? '-' }}</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Card 3: Dokumen Pendukung --}}
        <div class="card">
          <div class="card-header">
            <h4>Dokumen Pendukung</h4>
          </div>
          <div class="card-body">
            @php
            $docs = [
            ['key' => 'dokumen_ktp', 'label' => 'Kartu Tanda Penduduk (KTP)'],
            ['key' => 'dokumen_kk', 'label' => 'Kartu Keluarga (KK)'],
            ['key' => 'dokumen_surat_permohonan', 'label' => 'Surat Permohonan'],
            ['key' => 'dokumen_transkrip', 'label' => 'Transkrip Nilai / KHS'],
            ['key' => 'dokumen_surat_aktif', 'label' => 'Surat Aktif Kuliah / KTM'],
            ['key' => 'dokumen_pas_foto', 'label' => 'Pas Foto 3x4'],
            ['key' => 'dokumen_surat_pernyataan', 'label' => 'Surat Pernyataan Tidak Menerima Beasiswa Lain'],
            ['key' => 'dokumen_sktm', 'label' => 'Surat Keterangan Tidak Mampu (SKTM)'],
            ['key' => 'dokumen_bukti_ukt', 'label' => 'Bukti Pembayaran UKT/SPP'],
            ];
            @endphp

            @foreach($docs as $doc)
            <div class="mb-4">
              <strong>{{ $doc['label'] }}</strong>
              <div class="mt-2">
                @if($applicant->{$doc['key']})
                <div class="d-flex flex-wrap">
                  <a href="{{ asset('storage/' . $applicant->{$doc['key']}) }}" target="_blank"
                    class="btn btn-sm btn-primary mr-2">
                    <i class="fas fa-eye"></i> Lihat
                  </a>
                  <a href="{{ asset('storage/' . $applicant->{$doc['key']}) }}" download
                    class="btn btn-sm btn-outline-secondary mr-2">
                    <i class="fas fa-download"></i> Download
                  </a>
                </div>
                @else
                <span class="text-muted">Tidak ada</span>
                @endif
              </div>
            </div>
            <hr>
            @endforeach

            {{-- Sertifikat Prestasi --}}
            <div class="mb-4">
              <strong>Sertifikat Prestasi</strong>
              <div class="mt-2">
                @if($applicant->dokumen_prestasi && count($applicant->dokumen_prestasi) > 0)
                @foreach($applicant->dokumen_prestasi as $index => $dokumen)
                <div class="mb-3">
                  <small class="text-muted">Prestasi {{ $index + 1 }}</small>
                  <div class="d-flex flex-wrap">
                    <a href="{{ asset('storage/' . $dokumen) }}" target="_blank" class="btn btn-sm btn-primary mr-2">
                      <i class="fas fa-eye"></i> Lihat
                    </a>
                    <a href="{{ asset('storage/' . $dokumen) }}" download class="btn btn-sm btn-outline-secondary mr-2">
                      <i class="fas fa-download"></i> Download
                    </a>
                  </div>
                </div>
                @endforeach
                @else
                <span class="text-muted">Tidak ada</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- RIGHT COLUMN --}}
      <div class="col-lg-4">
        {{-- Card 1: Beasiswa --}}
        <div class="card">
          <div class="card-header">
            <h4>Beasiswa yang Dilamar</h4>
          </div>
          <div class="card-body text-center">
            <div class="mb-3">
              <i class="fas fa-award fa-2x text-primary mb-2"></i>
              <div class="font-weight-bold h5 mb-1">{{ $applicant->beasiswa->nama }}</div>
              <div class="text-muted">{{ $applicant->beasiswa->kampus }}</div>
            </div>
            <hr>
            <div class="row text-center">
              <div class="col-6">
                <div class="text-muted small">Tingkat Gelar</div>
                <div class="font-weight-bold">{{ $applicant->beasiswa->tingkat_gelar }}</div>
              </div>
              <div class="col-6">
                <div class="text-muted small">Batas Waktu</div>
                <div class="font-weight-bold">{{ $applicant->beasiswa->batas_waktu ?
                  $applicant->beasiswa->batas_waktu->translatedFormat('d F Y') : '-' }}</div>
              </div>
            </div>
          </div>
        </div>

        {{-- Card 1c: Syarat Beasiswa --}}
        <div class="card">
          <div class="card-header">
            <h4>Syarat Beasiswa</h4>
          </div>
          <div class="card-body">
            @php
            $syaratList = collect(explode("\n", $applicant->beasiswa->persyaratan ?? ''))
            ->map(fn ($s) => trim($s))
            ->reject(fn ($s) => $s === '')
            ->values();
            @endphp
            @if($syaratList->isEmpty())
            <p class="text-muted mb-0">Tidak ada persyaratan</p>
            @else
            <ul class="mb-0 pl-3">
              @foreach($syaratList as $syarat)
              <li class="mb-1">{{ $syarat }}</li>
              @endforeach
            </ul>
            @endif
            <hr>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted">IPK Pendaftar</span>
              <span class="badge badge-primary">{{ $applicant->ipk ?? '-' }}</span>
            </div>
          </div>
        </div>

        {{-- Card 2: Ubah Status (admin only) --}}
        @if(auth()->user()->hasRole('admin'))
        <div class="card">
          <div class="card-header">
            <h4>Ubah Status</h4>
          </div>
          <div class="card-body">
            @if($applicant->status === 'ditolak')
            <div class="alert alert-danger mb-3"><i class="fas fa-times-circle"></i> Pendaftar ditolak.</div>
            <div class="form-group">
              <label for="status">Status</label>
              <input type="text" class="form-control" value="{{ $applicant->getStatusLabelAttribute() }}" readonly>
            </div>
            @else
            <form action="{{ route('admin.pendaftar.perbarui', $applicant) }}" method="POST" id="form-update-status">
              @csrf
              @method('PUT')
              <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control @error('status') is-invalid @enderror" name="status" id="status" required>
                  <option value="verifikasi" {{ $applicant->status === 'verifikasi' ? 'selected' : '' }}>Verifikasi
                  </option>
                  <option value="diterima" {{ $applicant->status === 'diterima' ? 'selected' : '' }}>Diterima</option>
                  <option value="revisi" {{ $applicant->status === 'revisi' ? 'selected' : '' }}>Revisi</option>
                  <option value="ditolak" {{ $applicant->status === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
                @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group">
                <label for="catatan">Catatan</label>
                <textarea class="form-control @error('catatan') is-invalid @enderror" name="catatan" id="catatan"
                  rows="4" placeholder="Catatan (opsional)">{{ old('catatan', $applicant->catatan) }}</textarea>
                @error('catatan')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <button type="button" class="btn btn-primary btn-block" id="btn-update-status">
                <i class="fas fa-save"></i> Perbarui Status
              </button>
            </form>
            @endif
          </div>
        </div>
        @endif

        {{-- Card 3: Aksi (admin only) --}}
        @if(auth()->user()->hasRole('admin'))
        <div class="card">
          <div class="card-header">
            <h4>Aksi</h4>
          </div>
          <div class="card-body">
            <form action="{{ route('admin.pendaftar.hapus', $applicant) }}" method="POST" class="btn-delete-form">
              @csrf
              @method('DELETE')
              <button type="button" class="btn btn-danger btn-delete">
                <i class="fas fa-trash"></i> Hapus Data
              </button>
            </form>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</section>
@endsection

@push('script')
<script>
  $(document).ready(function() {
    // SweetAlert2 confirmation for status update
    $('#btn-update-status').on('click', function () {
      var status = $('#status').val();
      var statusText = $('#status option:selected').text();
      var confirmMessage = 'Yakin ingin mengubah status menjadi "' + statusText + '"?';

      Swal.fire({
        title: 'Konfirmasi Perubahan Status',
        text: confirmMessage,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#47c363',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Perbarui!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('form-update-status').submit();
        }
      });
    });
  });
</script>
@endpush