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
            <h4>Konfirmasi Pendaftaran</h4>
          </div>
          <form action="{{ route('user.pendaftaran.simpan') }}" method="POST" id="formAjukan">
            @csrf
            <input type="hidden" name="beasiswa_id" value="{{ $scholarship->id }}">
            <div class="card-body">
              <div class="alert alert-info">
                <strong>Beasiswa yang Dipilih:</strong> {{ $scholarship->nama }} ({{ $scholarship->kampus }})
              </div>

              <div class="alert alert-success">
                <i class="fas fa-shield-alt"></i>
                <strong>Data profil Anda telah terverifikasi</strong> dan akan digunakan sebagai dasar pendaftaran.
              </div>

              <h5 class="mb-3">Data yang Dikirim</h5>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Fakultas:</strong><br>
                  {{ $profile->prodi?->fakultas?->nama ?? '-' }}
                </div>
                <div class="col-md-6">
                  <strong>Program Studi:</strong><br>
                  {{ $profile->prodi?->nama ?? '-' }}
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>IPK:</strong><br>
                  {{ $profile->ipk ?? '-' }}
                </div>
                <div class="col-md-6">
                  <strong>Semester:</strong><br>
                  {{ $profile->semester ?? '-' }}
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Desil:</strong><br>
                  Desil {{ $profile->desil ?? '-' }}
                </div>
                <div class="col-md-6">
                  <strong>UKT:</strong><br>
                  Rp {{ number_format($profile->ukt ?? 0, 0, ',', '.') }}
                </div>
              </div>

              <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i>
                Pastikan seluruh dokumen pendukung (KTP, KK, akta, transkrip, SKTM, dll) sudah diunggah pada halaman
                <a href="{{ route('profile') }}">Profil</a>. Dokumen tersebut akan diverifikasi oleh pihak terkait.
              </div>
            </div>
            <div class="card-footer text-right">
              <a href="{{ route('user.beasiswa.lihat', $scholarship) }}" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i> Batal</a>
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
              <strong>Periode Pendaftaran:</strong><br>
              {{ $scholarship->tanggal_mulai->translatedFormat('d F Y') }} s.d. {{ $scholarship->tanggal_selesai->translatedFormat('d F Y') }}
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
              <li>Dokumen cukup diunggah sekali melalui halaman Profil.</li>
              <li>Pastikan semua data sudah benar sebelum mengirim.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection