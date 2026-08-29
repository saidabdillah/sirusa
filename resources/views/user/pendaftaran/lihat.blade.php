@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Detail Pendaftaran</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('user.pendaftaran.index') }}">Pendaftaran Saya</a></div>
      <div class="breadcrumb-item">Detail</div>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
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
              <div class="col-md-6">
                <strong>Nama Lengkap</strong>
                <p class="mb-0">{{ $profile->nama_lengkap ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <strong>Username</strong>
                <p class="mb-0">{{ $applicant->user->username ?? '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>NIK</strong>
                <p class="mb-0">{{ $profile->nik ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <strong>Tempat, Tanggal Lahir</strong>
                <p class="mb-0">{{ $profile->tempat_lahir ?? '-' }}, {{ $profile->tanggal_lahir ?
                  $profile->tanggal_lahir->translatedFormat('d F Y') : '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Jenis Kelamin</strong>
                <p class="mb-0">{{ $profile->jenis_kelamin ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <strong>Email</strong>
                <p class="mb-0">{{ $applicant->user->email ?? '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <strong>Agama</strong>
                <p class="mb-0">{{ $profile->agama ?? '-' }}</p>
              </div>
              <div class="col-md-6">
                <strong>Telepon</strong>
                <p class="mb-0">{{ $profile->telepon ?? '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-12">
                <strong>Alamat Detail</strong>
                <p class="mb-0">{{ $profile->alamat ?? '-' }}</p>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-4">
                <strong>Kabupaten</strong>
                <p class="mb-0">{{ $profile->kabupaten_kota ?: 'Balangan' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Kecamatan</strong>
                <p class="mb-0">{{ $profile->kecamatan ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Desa/Kelurahan</strong>
                <p class="mb-0">{{ $profile->desa_kelurahan ?? '-' }}</p>
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
              <div class="col-md-12"><h6 class="text-muted">Ayah</h6></div>
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
              <div class="col-md-12"><h6 class="text-muted">Ibu</h6></div>
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
              <div class="col-md-12"><h6 class="text-muted">Wali</h6></div>
            </div>
            <div class="row mb-3">
              <div class="col-md-4">
                <strong>Nama</strong>
                <p class="mb-0">{{ $profile->nama_wali }}</p>
              </div>
              <div class="col-md-4">
                <strong>Hubungan</strong>
                <p class="mb-0">{{ $profile->hubungan_wali ?? '-' }}</p>
              </div>
              <div class="col-md-4">
                <strong>Pekerjaan</strong>
                <p class="mb-0">{{ $profile->pekerjaan_wali ?? '-' }}</p>
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

            <hr>

            <h5 class="mb-3"><i class="fas fa-file-alt mr-1"></i> Berkas Tahap 2</h5>

            @php
            $tahap2Docs = [
            ['key' => 'dokumen_surat_pernyataan', 'label' => 'Surat Pernyataan Tidak Menerima Beasiswa Lain'],
            ['key' => 'dokumen_sktm', 'label' => 'Surat Keterangan Tidak Mampu (SKTM)'],
            ['key' => 'dokumen_bukti_ukt', 'label' => 'Bukti Pembayaran UKT/SPP'],
            ];
            @endphp

            @foreach($tahap2Docs as $doc)
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
                <span class="text-muted">Belum diunggah</span>
                @endif
              </div>
            </div>
            <hr>
            @endforeach
          </div>
        </div>
      </div>

      {{-- RIGHT COLUMN --}}
      <div class="col-lg-4">
        {{-- Card 1: Status --}}
        <div class="card">
          <div class="card-header">
            <h4>Status Pendaftaran</h4>
          </div>
          <div class="card-body text-center">
            <div class="mb-3">
              @if($applicant->status === 'verifikasi')
                <span class="badge badge-warning p-2"><i class="fas fa-clock"></i> Verifikasi</span>
              @elseif($applicant->status === 'diterima')
                <span class="badge badge-info p-2"><i class="fas fa-check-circle"></i> Diterima Tahap 1</span>
              @elseif($applicant->status === 'verifikasi_akhir')
                <span class="badge badge-primary p-2"><i class="fas fa-hourglass-half"></i> Verifikasi Akhir</span>
              @elseif($applicant->status === 'selesai')
                <span class="badge badge-success p-2"><i class="fas fa-check-double"></i> Selesai</span>
              @elseif($applicant->status === 'revisi')
                <span class="badge badge-secondary p-2"><i class="fas fa-edit"></i> Perlu Revisi</span>
              @elseif($applicant->status === 'ditolak')
                <span class="badge badge-danger p-2"><i class="fas fa-times-circle"></i> Ditolak</span>
              @endif
            </div>
            @if($applicant->status === 'selesai')
              <div class="alert alert-success text-left">
                <strong><i class="fas fa-award"></i> Nomor Penetapan</strong><br>
                {{ $applicant->nomor_penetapan ?: '-' }}
                <br>
                <small>Tanggal: {{ $applicant->tanggal_penetapan ? \Carbon\Carbon::parse($applicant->tanggal_penetapan)->translatedFormat('d F Y') : '-' }}</small>
              </div>
            @endif
            @if($applicant->catatan)
            <div class="text-left">
              <strong>Catatan Admin:</strong>
              <p class="mb-0">{!! nl2br(e($applicant->catatan)) !!}</p>
            </div>
            @endif
          </div>
        </div>

        {{-- Card 2: Beasiswa --}}
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

        {{-- Card 3: Aksi --}}
        <div class="card">
          <div class="card-header">
            <h4>Aksi</h4>
          </div>
          <div class="card-body">
            @if($applicant->status === 'verifikasi_akhir')
              <div class="alert alert-primary mb-0">
                <i class="fas fa-hourglass-half"></i> Berkas Tahap 2 sedang diverifikasi admin. Mohon menunggu hasil verifikasi akhir.
              </div>
            @endif
            @if($applicant->status === 'revisi')
              <a href="{{ route('user.pendaftaran.lengkapi', $applicant) }}" class="btn btn-warning btn-block mb-2">
                <i class="fas fa-edit"></i> Revisi Pendaftaran
              </a>
            @endif
            @if($applicant->status === 'diterima')
              <a href="{{ route('user.pendaftaran.melengkapi', $applicant) }}" class="btn btn-info btn-block mb-2">
                <i class="fas fa-upload"></i> Unggah Berkas Tahap 2
              </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
