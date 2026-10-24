@extends('layouts.app')

@php
$documentGroups = [
'Berkas Data Diri' => [
'foto_profil' => 'Pas Foto 3x4',
'dokumen_ktp' => 'KTP (Kartu Tanda Penduduk)',
'dokumen_kk' => 'KK (Kartu Keluarga)',
'dokumen_desil' => 'Dokumen Desil',
],
'Berkas Kampus' => [
// Transkrip digantikan PDDIKTI; SKTM tidak lagi disyaratkan. Berkas KRS dan
// KHS diunggah di bagian akademik tersendiri (per semester), bukan di sini.
'dokumen_pddikti' => 'Dokumen PDDIKTI',
'dokumen_surat_aktif' => 'Surat Aktif Kuliah',
'dokumen_surat_pernyataan' => 'Surat Pernyataan',
'dokumen_bukti_ukt' => 'Bukti UKT (Uang Kuliah Tunggal)',
],
'Berkas Orang Tua/Famili Lain' => [
'ktp_ayah' => 'KTP (Kartu Tanda Penduduk) Ayah',
'ktp_ibu' => 'KTP (Kartu Tanda Penduduk) Ibu',
'ktp_wali' => 'KTP (Kartu Tanda Penduduk) Famili Lain',
'kk_wali' => 'KK (Kartu Keluarga) Famili Lain',
],
];
$waliDocFields = ['ktp_wali', 'kk_wali'];
$pekerjaanList = ['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'];
@endphp

@section('skeleton')
@include('layouts.partials.skeleton.shell', [
'content' => view('layouts.partials.skeleton.form', [
'avatar' => false,
'sections' => $isMahasiswa ? [
['label' => 'Data Diri', 'rows' => [1, 2, 2, 2]],
['label' => 'Data Kampus', 'rows' => [2, 3, 2]],
['label' => 'Data Orang Tua', 'rows' => [1, 2, 2, 2, 2]],
] : [],
])->render(),
])
@endsection

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Profil Saya</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Profil</div>
    </div>
  </div>

  <div class="section-body">

    {{-- Semua blok ini hanya relevan untuk mahasiswa. Akun staf (super_admin,
    kesra, kampus, catpil) tidak punya baris profil, jadi halaman profil
    mereka berhenti di Informasi Akun. --}}
    @if ($isMahasiswa)
    @if(! $profileComplete)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <strong><i class="fas fa-exclamation-triangle"></i> Profil belum lengkap!</strong><br>
      Anda harus melengkapi data dan dokumen berikut sebelum bisa mendaftar beasiswa.
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if($profileComplete)
    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i> Profil Anda sudah lengkap. Anda bisa mendaftar beasiswa.
    </div>
    @endif

    {{-- Hanya umpan balik yang harus ditindaklanjuti mahasiswa yang
         ditampilkan. Status tahap dan progress verifikasi sengaja tidak
         dimunculkan; datanya tetap disimpan untuk penyaringan, audit, dan
         alur kerja verifikator. --}}
    @if($profile)
    @switch($profile->verifStatus())
    @case('revisi')
    <div class="alert alert-danger">
      <i class="fas fa-times-circle"></i> Data profil Anda <strong>perlu perbaikan</strong>. Silakan periksa catatan
      verifikator lalu simpan kembali profil Anda.
      @foreach($profile->verifStageLabels() as $stage => $label)
      @if($profile->{'verif_'.$stage} === 'revisi' && $profile->{'catatan_'.$stage})
      <div class="mt-2"><strong>{{ $label }}:</strong> {{ $profile->{'catatan_'.$stage} }}</div>
      @endif
      @endforeach
    </div>
    @break
    @case('tolak')
    <div class="alert alert-danger">
      <i class="fas fa-times-circle"></i> Data profil Anda <strong>ditolak</strong>. Silakan periksa catatan verifikator
      lalu simpan kembali profil Anda.
      @foreach($profile->verifStageLabels() as $stage => $label)
      @if($profile->{'verif_'.$stage} === 'tolak' && $profile->{'catatan_'.$stage})
      <div class="mt-2"><strong>{{ $label }}:</strong> {{ $profile->{'catatan_'.$stage} }}</div>
      @endif
      @endforeach
    </div>
    @break
    @endswitch
    @endif
    @endif

    {{-- Kartu Tunggal untuk staf: `justify-content-start` (bukan `offset-lg-*`
         yang hanya berlaku di >=992px, dan bukan `justify-content-center` yang
         membuat kartu melayang di tengah halaman). --}}
    {{-- Mahasiswa memakai lebar penuh: kartu Informasi Akun tidak lagi
         ditampilkan untuk mereka (hanya akun staf yang punya kartu itu). --}}
    <div class="row {{ $isMahasiswa ? '' : 'justify-content-start' }}">
      @if ($isMahasiswa)
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Edit Profil</h4>
          </div>
          <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profilForm"
            data-ajax-form>
            @csrf
            @method('PUT')
            <div class="card-body">

              {{-- DATA DIRI --}}
              <h5 class="mb-3">Data Diri</h5>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nama_lengkap">Nama Lengkap <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror" id="nama_lengkap"
                    name="nama_lengkap" value="{{ old('nama_lengkap', $profile->nama_lengkap ?? '') }}">
                  @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="email">Email <span class="text-danger">*</span></label>
                  <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                    value="{{ old('email', auth()->user()->email) }}" autocomplete="email">
                  @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nik">NIK (Nomor Induk Kependudukan) <span class="text-danger">*</span></label>
                  <input type="text" inputmode="numeric" class="form-control @error('nik') is-invalid @enderror" id="nik"
                    name="nik" value="{{ old('nik', $profile->nik ?? '') }}" maxlength="16">
                  @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="no_kk">No. Kartu Keluarga <span class="text-danger">*</span></label>
                  <input type="text" inputmode="numeric" class="form-control @error('no_kk') is-invalid @enderror"
                    id="no_kk" name="no_kk" value="{{ old('no_kk', $profile->no_kk ?? '') }}" maxlength="16">
                  @error('no_kk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="telepon">No. HP <span class="text-danger">*</span></label>
                  <input type="text" inputmode="numeric" class="form-control @error('telepon') is-invalid @enderror"
                    id="telepon" name="telepon" value="{{ old('telepon', $profile->telepon ?? '') }}" maxlength="13"
                    placeholder="contoh 081234567890">
                  @error('telepon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="desil">Desil Kesejahteraan <span class="text-danger">*</span></label>
                  <select class="form-control @error('desil') is-invalid @enderror" id="desil" name="desil">
                    <option value="">Pilih Desil (1-10)</option>
                    @foreach(range(1, 10) as $d)
                    <option value="{{ $d }}" {{ old('desil', $profile->desil ?? '') == $d ? 'selected' : '' }}>Desil {{
                      $d }}</option>
                    @endforeach
                  </select>
                  @error('desil')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="tempat_lahir">Tempat Lahir <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('tempat_lahir') is-invalid @enderror" id="tempat_lahir"
                    name="tempat_lahir" value="{{ old('tempat_lahir', $profile->tempat_lahir ?? '') }}">
                  @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="tanggal_lahir">Tanggal Lahir <span class="text-danger">*</span></label>
                  <input type="text"
                    class="form-control flatpickr bg-white @error('tanggal_lahir') is-invalid @enderror"
                    id="tanggal_lahir" name="tanggal_lahir"
                    value="{{ old('tanggal_lahir', $profile?->tanggal_lahir?->format('Y-m-d') ?? '') }}"
                    placeholder="Pilih tanggal" style="cursor: pointer;">
                  @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Jenis Kelamin <span class="text-danger">*</span></label>
                  {{-- Pesan galat radio WAJIB di luar baris flex-nya. Kalau
                       `.invalid-feedback` ditulis di dalam sini, ia jadi anak
                       dari `d-flex` dan tampil di SAMPING "Laki-laki", bukan
                       di bawah kedua pilihan. Kelas pembungkusnya juga
                       ditandai `is-invalid` supaya AJAX tetap menemukannya
                       (lihat `paintFieldError` di `custom.js`). --}}
                  <div class="pilihan-radio @error('jenis_kelamin') is-invalid @enderror">
                    <div class="d-flex align-items-center flex-wrap">
                      @foreach(['Laki-laki' => 'laki_laki', 'Perempuan' => 'perempuan'] as $label => $key)
                      <div class="custom-control custom-radio mr-4">
                        <input type="radio" class="custom-control-input @error('jenis_kelamin') is-invalid @enderror"
                          id="jenis_kelamin_{{ $key }}" name="jenis_kelamin" value="{{ $label }}" {{ old('jenis_kelamin',
                          $profile->jenis_kelamin ?? '') === $label ? 'checked' : '' }}>
                        <label class="custom-control-label" for="jenis_kelamin_{{ $key }}">{{ $label }}</label>
                      </div>
                      @endforeach
                    </div>
                  </div>
                  @error('jenis_kelamin')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="agama">Agama <span class="text-danger">*</span></label>
                  <select class="form-control @error('agama') is-invalid @enderror" id="agama" name="agama">
                    <option value="">Pilih Agama</option>
                    @foreach(['Islam', 'Kristen', 'Katholik', 'Hindu', 'Buddha', 'Konghucu'] as $agama)
                    <option value="{{ $agama }}" {{ old('agama', $profile->agama ?? '') === $agama ? 'selected' : ''
                      }}>{{ $agama }}</option>
                    @endforeach
                  </select>
                  @error('agama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              {{-- DATA TEMPAT TINGGAL --}}
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="provinsi">Provinsi</label>
                  <input type="text" class="form-control" id="provinsi" name="provinsi"
                    value="{{ old('provinsi', $profile->provinsi ?? 'Kalimantan Selatan') }}" readonly>
                </div>
                <div class="form-group col-md-6">
                  <label for="kabupaten_kota">Kabupaten/Kota</label>
                  <input type="text" class="form-control" id="kabupaten_kota" name="kabupaten_kota"
                    value="{{ old('kabupaten_kota', $profile->kabupaten_kota ?? 'Balangan') }}" readonly>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="kecamatan">Kecamatan <span class="text-danger">*</span></label>
                  <select class="form-control @error('kecamatan') is-invalid @enderror" id="kecamatan" name="kecamatan">
                    <option value="">Pilih Kecamatan</option>
                    @foreach($districts as $district)
                    <option value="{{ $district['district'] }}" data-code="{{ $district['code'] }}" {{ old('kecamatan',
                      $profile->kecamatan ?? '') === $district['district'] ? 'selected' : '' }}>
                      {{ $district['district'] }}
                    </option>
                    @endforeach
                  </select>
                  @error('kecamatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="desa_kelurahan">Desa/Kelurahan <span class="text-danger">*</span></label>
                  <select class="form-control @error('desa_kelurahan') is-invalid @enderror" id="desa_kelurahan"
                    name="desa_kelurahan">
                    <option value="">{{ ($profile->desa_kelurahan ?? '') ? 'Pilih Desa/Kelurahan' : 'Pilih Kecamatan
                      terlebih dahulu' }}</option>
                  </select>
                  @error('desa_kelurahan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-group">
                <label for="alamat">Alamat Detail (RT/RW, Nama Jalan, No. Rumah) <span
                    class="text-danger">*</span></label>
                <textarea class="form-control @error('alamat') is-invalid @enderror" id="alamat" name="alamat"
                  rows="3">{{ old('alamat', $profile->alamat ?? '') }}</textarea>
                @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <hr>

              {{-- DATA KAMPUS --}}
              <h5 class="mb-3">Data Kampus</h5>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nama_kampus">Nama Kampus <span class="text-danger">*</span></label>
                  <select class="form-control select2-cari @error('nama_kampus') is-invalid @enderror" id="nama_kampus"
                    name="nama_kampus">
                    <option value="">Pilih Kampus</option>
                    @foreach($kampusList as $kampus)
                    <option value="{{ $kampus->nama_kampus }}" {{ old('nama_kampus', $profile->
                      prodi?->fakultas?->kampus?->nama_kampus ?? '') === $kampus->nama_kampus ? 'selected' : '' }}>{{
                      $kampus->nama_kampus }}</option>
                    @endforeach
                  </select>
                  @error('nama_kampus')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="fakultas">Fakultas <span class="text-danger">*</span></label>
                  <select class="form-control select2-cari @error('fakultas') is-invalid @enderror" id="fakultas" name="fakultas">
                    <option value="">Pilih Kampus terlebih dahulu</option>
                  </select>
                  @error('fakultas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="prodi">Program Studi <span class="text-danger">*</span></label>
                  <select class="form-control select2-cari @error('prodi_id') is-invalid @enderror" id="prodi" name="prodi_id">
                    <option value="">Pilih Fakultas terlebih dahulu</option>
                  </select>
                  @error('prodi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="jenjang">Jenjang Pendidikan <span class="text-danger">*</span></label>
                  <select class="form-control @error('jenjang') is-invalid @enderror" id="jenjang" name="jenjang">
                    <option value="">-- Pilih Jenjang --</option>
                    @foreach($jenjangList as $jenjang)
                    <option value="{{ $jenjang }}" {{ old('jenjang', $profile->jenjang ?? '') === $jenjang ? 'selected' :
                      '' }}>{{ $jenjang }}</option>
                    @endforeach
                  </select>
                  @error('jenjang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="tahun_masuk">Tahun Masuk <span class="text-danger">*</span></label>
                  <input type="number" min="{{ \App\Support\JenjangPendidikan::rentangTahunMasuk()[0] }}"
                    max="{{ \App\Support\JenjangPendidikan::rentangTahunMasuk()[1] }}" step="1"
                    class="form-control @error('tahun_masuk') is-invalid @enderror" id="tahun_masuk" name="tahun_masuk"
                    value="{{ old('tahun_masuk', $profile->tahun_masuk ?? '') }}" placeholder="contoh 2023">
                  @error('tahun_masuk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="ipk">IPK (Indeks Prestasi Kumulatif) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" min="0" max="4"
                    class="form-control @error('ipk') is-invalid @enderror" id="ipk" name="ipk"
                    value="{{ old('ipk', $profile->ipk ?? '') }}">
                  @error('ipk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  {{-- Semester dipilih dari daftar, bukan diketik: pilihannya
                       mengikuti jenjang, jadi angka yang tidak ada di jenjang
                       itu tidak pernah bisa dipilih. Dropdown-nya nonaktif
                       sampai jenjang dipilih, dan diisi ulang oleh skrip di
                       bawah saat jenjang berubah. --}}
                  <label for="semester">Semester <span class="text-danger">*</span></label>
                  <select class="form-control @error('semester') is-invalid @enderror" id="semester" name="semester">
                    <option value="">-- Pilih Semester --</option>
                  </select>
                  @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="nim">NIM/NPM</label>
                  <input type="text" class="form-control @error('nim') is-invalid @enderror" id="nim" name="nim"
                    value="{{ old('nim', $profile->nim ?? '') }}" maxlength="30">
                  @error('nim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="ukt">UKT (Uang Kuliah Tunggal) <span class="text-danger">*</span></label>
                  <div class="input-group @error('ukt') is-invalid @enderror">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input type="text" inputmode="numeric" class="form-control @error('ukt') is-invalid @enderror"
                      id="ukt" name="ukt" value="{{ old('ukt', $profile->ukt ?? '') }}" placeholder="contoh 2.500.000">
                  </div>
                  @error('ukt')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
              </div>

              {{-- DATA ORANG TUA & WALI --}}
              <h5 class="mb-3">Data Orang Tua &amp; Famili Lain</h5>
              <div class="form-row">
                <div class="form-group col-md-12">
                  <label>Ikut KK (Kartu Keluarga) <span class="text-danger">*</span></label>
                  <div class="pilihan-radio @error('ikut_kk') is-invalid @enderror">
                    <div class="d-flex align-items-center flex-wrap">
                      @foreach(['ayah' => 'Ayah', 'ibu' => 'Ibu', 'famili_lain' => 'Famili Lain', 'kepala_keluarga' => 'Kepala Keluarga'] as $value => $label)
                      <div class="custom-control custom-radio mr-4">
                        <input type="radio" class="custom-control-input @error('ikut_kk') is-invalid @enderror"
                          id="ikut_kk_{{ $value }}" name="ikut_kk" value="{{ $value }}" {{ old('ikut_kk',
                          $profile?->ikut_kk) === $value ? 'checked' : '' }}>
                        <label class="custom-control-label" for="ikut_kk_{{ $value }}">{{ $label }}</label>
                      </div>
                      @endforeach
                    </div>
                  </div>
                  @error('ikut_kk')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  {{-- Kolom boolean `kepala_keluarga` tetap ada di database dan
                       NILAINYA DIHITUNG SERVER dari opsi radio ini
                       (`prepareForValidation` menimpanya: `kepala_keluarga` =
                       pilihan === 'kepala_keluarga'). Input klien untuk kolom
                       itu tidak bisa merubah keputusan, jadi tidak perlu field
                       apa pun di form. --}}
                </div>
              </div>

              <h6 class="text-muted mt-3 mb-2">Ayah</h6>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nama_ayah">Nama Ayah <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nama_ayah') is-invalid @enderror" id="nama_ayah"
                    name="nama_ayah" value="{{ old('nama_ayah', $profile->nama_ayah ?? '') }}">
                  @error('nama_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="nik_ayah">NIK (Nomor Induk Kependudukan) Ayah <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nik_ayah') is-invalid @enderror" id="nik_ayah"
                    name="nik_ayah" value="{{ old('nik_ayah', $profile->nik_ayah ?? '') }}" maxlength="16">
                  @error('nik_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="pekerjaan_ayah">Pekerjaan Ayah <span class="text-danger">*</span></label>
                  <select class="form-control @error('pekerjaan_ayah') is-invalid @enderror" id="pekerjaan_ayah"
                    name="pekerjaan_ayah">
                    <option value="">Pilih Pekerjaan</option>
                    @foreach($pekerjaanList as $pekerjaan)
                    <option value="{{ $pekerjaan }}" {{ old('pekerjaan_ayah', $profile->pekerjaan_ayah ?? '') ===
                      $pekerjaan ? 'selected' : '' }}>{{ $pekerjaan }}</option>
                    @endforeach
                  </select>
                  @error('pekerjaan_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <h6 class="text-muted mt-3 mb-2">Ibu</h6>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nama_ibu">Nama Ibu <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nama_ibu') is-invalid @enderror" id="nama_ibu"
                    name="nama_ibu" value="{{ old('nama_ibu', $profile->nama_ibu ?? '') }}">
                  @error('nama_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="nik_ibu">NIK (Nomor Induk Kependudukan) Ibu <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nik_ibu') is-invalid @enderror" id="nik_ibu"
                    name="nik_ibu" value="{{ old('nik_ibu', $profile->nik_ibu ?? '') }}" maxlength="16">
                  @error('nik_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="pekerjaan_ibu">Pekerjaan Ibu <span class="text-danger">*</span></label>
                  <select class="form-control @error('pekerjaan_ibu') is-invalid @enderror" id="pekerjaan_ibu"
                    name="pekerjaan_ibu">
                    <option value="">Pilih Pekerjaan</option>
                    @foreach($pekerjaanList as $pekerjaan)
                    <option value="{{ $pekerjaan }}" {{ old('pekerjaan_ibu', $profile->pekerjaan_ibu ?? '') ===
                      $pekerjaan ? 'selected' : '' }}>{{ $pekerjaan }}</option>
                    @endforeach
                  </select>
                  @error('pekerjaan_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div id="section-wali"
                style="{{ old('ikut_kk', $profile->ikut_kk ?? 'ayah') === 'famili_lain' ? '' : 'display:none;' }}">
                <hr>
                <h6 class="text-muted mb-2">Famili Lain</h6>
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="nama_wali">Nama Wali <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nama_wali') is-invalid @enderror" id="nama_wali"
                      name="nama_wali" value="{{ old('nama_wali', $profile->nama_wali ?? '') }}">
                    @error('nama_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="nik_wali">NIK (Nomor Induk Kependudukan) Famili Lain <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nik_wali') is-invalid @enderror" id="nik_wali"
                      name="nik_wali" value="{{ old('nik_wali', $profile->nik_wali ?? '') }}" maxlength="16">
                    @error('nik_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="hubungan_wali">Hubungan <span class="text-danger">*</span></label>
                    <select class="form-control @error('hubungan_wali') is-invalid @enderror" id="hubungan_wali"
                      name="hubungan_wali">
                      <option value="">Pilih Hubungan</option>
                      @foreach(['Paman', 'Bibi', 'Kakek', 'Nenek', 'Lainnya'] as $hubungan)
                      <option value="{{ $hubungan }}" {{ old('hubungan_wali', $profile->hubungan_wali ?? '') ===
                        $hubungan ? 'selected' : '' }}>{{ $hubungan }}</option>
                      @endforeach
                    </select>
                    @error('hubungan_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="pekerjaan_wali">Pekerjaan Wali <span class="text-danger">*</span></label>
                    <select class="form-control @error('pekerjaan_wali') is-invalid @enderror" id="pekerjaan_wali"
                      name="pekerjaan_wali">
                      <option value="">Pilih Pekerjaan</option>
                      @foreach($pekerjaanList as $pekerjaan)
                      <option value="{{ $pekerjaan }}" {{ old('pekerjaan_wali', $profile->pekerjaan_wali ?? '') ===
                        $pekerjaan ? 'selected' : '' }}>{{ $pekerjaan }}</option>
                      @endforeach
                    </select>
                    @error('pekerjaan_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>

              <hr>

              {{-- DOKUMEN --}}
              <h5 class="mb-3">Dokumen</h5>
              <small class="text-muted d-block mb-3">Unggah dokumen sekali di profil. Dokumen akan diverifikasi oleh
                pihak terkait.</small>
              @foreach($documentGroups as $groupName => $documents)
              <h6 class="mt-3 mb-2"><i class="fas fa-folder text-primary mr-1"></i>{{ $groupName }}</h6>
              @foreach($documents as $field => $label)
              @php
                $isWaliDoc = in_array($field, $waliDocFields, true);
                // Pas foto satu-satunya berkas berupa gambar; semua dokumen
                // lain wajib PDF. `accept` dan teks bantuan mengikuti aturan
                // yang sama dengan `UpdateProfilRequest`.
                $isFoto = $field === 'foto_profil';
                $accept = $isFoto ? 'image/jpeg,image/png' : 'application/pdf';
                $bantuan = $isFoto
                    ? 'Format: JPG, JPEG, atau PNG. Ukuran maksimal 2 MB.'
                    : 'Format: PDF. Ukuran maksimal 2 MB.';
              @endphp
              {{-- Tampilan input mengikuti KRS/KHS: custom-file Bootstrap, bukan
                   input-group (permintaan pengguna). --}}
              <div class="form-group {{ $isWaliDoc ? 'wali-doc' : '' }}"
                style="{{ $isWaliDoc && old('ikut_kk', $profile->ikut_kk ?? 'ayah') !== 'famili_lain' ? 'display:none;' : '' }}">
                <label for="{{ $field }}">{{ $label }} <span class="text-danger">*</span></label>
                <div class="custom-file">
                  <input type="file"
                    class="custom-file-input @error($field) is-invalid @enderror"
                    id="{{ $field }}" name="{{ $field }}" accept="{{ $accept }}">
                  <label class="custom-file-label" for="{{ $field }}">{{ $profile?->{$field} ? basename($profile->{$field}) : 'Pilih file' }}</label>
                </div>
                @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @if($profile && $profile->{$field})
                <small class="text-muted d-block">
                  Dokumen sudah diunggah.
                  <a href="{{ route('dokumen.show', $profile->{$field}) }}" target="_blank"
                    rel="noopener noreferrer">Lihat berkas tersimpan</a>. Upload file baru untuk mengganti.
                </small>
                @endif
                <small class="text-muted d-block">{{ $bantuan }}</small>
              </div>
              @endforeach
              {{-- KRS & KHS ikut kelompok dokumen, di bawah "Berkas Kampus":
                   keduanya dokumen akademik yang diperiksa Kampus, bukan
                   isian data kampus. --}}
              @if($groupName === 'Berkas Kampus')
              @include('profil.partials.dokumen-akademik-tabel')
              @endif
              @endforeach

              <div class="form-group">
                <label for="dokumen_prestasi">Dokumen Prestasi (Pilihan, bisa lebih dari satu)</label>
                <div class="custom-file">
                  <input type="file" class="custom-file-input @error('dokumen_prestasi.*') is-invalid @enderror"
                    id="dokumen_prestasi" name="dokumen_prestasi[]" accept="application/pdf" multiple>
                  <label class="custom-file-label" for="dokumen_prestasi">Pilih file</label>
                </div>
                @error('dokumen_prestasi.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <small class="text-muted d-block">Format: PDF. Ukuran maksimal 2 MB per file.</small>
                @if($profile && $profile->dokumen_prestasi)
                <div class="mt-2">
                  @foreach($profile->dokumen_prestasi as $path)
                  <a href="{{ route('dokumen.show', $path) }}" target="_blank"
                    class="btn btn-sm btn-outline-secondary mr-1 mb-1"><i class="fas fa-file mr-1"></i>Prestasi</a>
                  @endforeach
                </div>
                @endif
              </div>

            </div>
            <div class="card-footer text-right">
              <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mr-2"><i
                  class="fas fa-arrow-left mr-1"></i> Batal</a>
              <button type="submit" class="btn btn-primary">Simpan Profil</button>
            </div>
          </form>
        </div>
      </div>
      @endif

      {{-- Kartu Informasi Akun hanya untuk akun STAF. Mahasiswa tidak butuh
           kartu ini: username/email/role-nya bukan informasi yang bisa ia
           gunakan di halaman profil (email sudah jadi field form, kata sandi
           ada di Pengaturan). --}}
      @unless ($isMahasiswa)
      <div class="col-12 col-lg-6">
        <div class="card">
          <div class="card-header">
            <h4>Informasi Akun</h4>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <strong>Username:</strong><br>
              {{ auth()->user()->username }}
            </div>
            <div class="mb-2">
              <strong>Email:</strong><br>
              {{ auth()->user()->email }}
            </div>
            <div class="mb-2">
              <strong>Role:</strong><br>
              {{ ucfirst(auth()->user()->getRoleNames()->first() ?? 'user') }}
            </div>
            <div class="mb-2">
              <strong>Terdaftar:</strong><br>
              {{ auth()->user()->created_at->translatedFormat('d F Y') }}
            </div>
            <hr>
            <div class="text-muted small">
              Akun staf tidak punya data profil.
            </div>
          </div>
        </div>
      </div>
      @endunless
    </div>
  </div>
</section>
@endsection

@if ($isMahasiswa)
@push('script')
<script src="{{ asset('assets/modules/cleave-js/dist/cleave.min.js') }}"></script>
<script>
  flatpickr(".flatpickr", {
    dateFormat: "Y-m-d",
    disableMobile: true
  });

  if (typeof Cleave !== 'undefined' && document.getElementById('nik')) {
    // NIK, No. KK, dan nomor HP hanya angka. `blocks` + `numericOnly` menahan
    // huruf sejak ketikan pertama, jadi pengguna tidak bisa melewati batas
    // digit: NIK dan No. KK berhenti di 16, nomor HP di 13. Aturan server
    // (`digits:16`, `same:nik`, dan `regex:/^08[0-9]{8,11}$/`) tetap berlaku
    // sebagai penutup jalur tanpa JS.
    new Cleave('#nik', {
      blocks: [16],
      numericOnly: true,
    });

    new Cleave('#no_kk', {
      blocks: [16],
      numericOnly: true,
    });

    new Cleave('#telepon', {
      blocks: [13],
      numericOnly: true,
    });
  }

  if (typeof Cleave !== 'undefined' && document.getElementById('ukt')) {
    // UKT adalah rupiah bulat, jadi `numeralDecimalScale: 0`.
    //
    // `numeralDecimalMark` HARUS tetap berbeda dari `delimiter`. Kalau
    // diomit, Cleave memakai default '.' yang sama persis dengan delimiter
    // ribuan, sehingga titik jadi penanda desimal sekaligus pemisah ribuan.
    // Begitu nilai ter-format seperti "1.000" melewati batas ribuan, Cleave
    // salah membaca titiknya sebagai desimal dan nilai yang sudah diformat
    // rusak atau kembali ke nol. Koma aman dipakai karena
    // `numeralDecimalScale: 0` membuat bagian desimal tidak pernah ada
    // maupun ditampilkan.
    //
    // Tiga lapis ini harus utuh: cast `decimal:0` di `UserProfile` supaya
    // Blade hanya mengirim digit (bukan "1500000.00" dari kolom
    // `decimal(10,2)`), konfigurasi Cleave di atas supaya digit yang masuk
    // dirender berformat Indonesia, dan `normalisasiRupiah()` di
    // `UpdateProfilRequest` supaya apa pun yang lolos tetap sampai ke aturan
    // `numeric` sebagai bilangan bulat.
    var uktCleave = new Cleave('#ukt', {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      delimiter: '.',
      numeralDecimalMark: ',',
      numeralIntegerScale: 15,
      numeralDecimalScale: 0,
      numeralPositiveOnly: true,
      stripLeadingZeroes: true,
    });

    // Guard terakhir: apa pun yang lolos (tempel dari sumber lain, nilai lama
    // yang belum ter-cast) tetap dirender sebagai digit murni.
    uktCleave.setRawValue(String($('#ukt').val() || '').replace(/\D/g, ''));
  }

  var desaUrlBase = "{{ url('/api/wilayah/desa') }}";

  function populateDesa(code, selectedDesa) {
    if (!code) {
      $('#desa_kelurahan').html('<option value="">Pilih Kecamatan terlebih dahulu</option>');
      return;
    }

    var $desa = $('#desa_kelurahan');

    $.getJSON(desaUrlBase + '/' + code)
      .done(function(data) {
        var options = '<option value="">Pilih Desa/Kelurahan</option>';
        $.each(data, function(i, item) {
          var selected = (selectedDesa && selectedDesa === item.village) ? ' selected' : '';
          options += '<option value="' + item.village + '"' + selected + '>' + item.village + '</option>';
        });
        $desa.html(options);
      })
      .fail(function(xhr) {
        // Tanpa cabang ini, select menganggur di "Memuat data..." selamanya
        // begitu request gagal: tidak ada daftar desa, tidak ada pesan, dan
        // user tidak tahu harus melakukan apa. Selectnya sendiri dibersihkan
        // supaya tidak menipu dengan tetap menampilkan "sedang memuat".
        $desa.html('<option value="">Gagal memuat desa. Pilih kecamatan lagi.</option>');

        Swal.fire({
          icon: xhr.status === 0 ? 'warning' : 'error',
          title: xhr.status === 0 ? 'Koneksi bermasalah' : 'Gagal memuat desa',
          text: xhr.status === 0
            ? 'Permintaan tidak terkirim. Periksa koneksi internet Anda lalu pilih kecamatan lagi.'
            : 'Daftar desa untuk kecamatan ini tidak bisa dimuat. Silakan coba lagi.'
        });
      });
  }

  var initialDesa = "{{ old('desa_kelurahan', $profile->desa_kelurahan ?? '') }}";

  if ($('#kecamatan').val()) {
    populateDesa($('#kecamatan option:selected').data('code'), initialDesa);
  }

  $('#kecamatan').change(function() {
    $('#desa_kelurahan').html('<option value="">Memuat data...</option>');
    populateDesa($(this).find('option:selected').data('code'));
  });

  var kampusData = @json($kampusJson);

  var selectedKampus = "{{ old('nama_kampus', $profile->prodi?->fakultas?->kampus?->nama_kampus ?? '') }}";
  var selectedFakultas = "{{ old('fakultas', $profile->prodi?->fakultas?->nama ?? '') }}";
  var selectedProdiId = "{{ old('prodi_id', $profile->prodi_id ?? '') }}";

  function loadFakultas(namaKampus, selected) {
    var $fak = $('#fakultas');
    $fak.html('<option value="">Pilih Fakultas</option>');
    $('#prodi').html('<option value="">Pilih Fakultas terlebih dahulu</option>');
    $('#prodi').trigger('change.select2');

    var kampus = kampusData.find(function(k) { return k.nama === namaKampus; });
    if (!kampus) return;

    kampus.fakultas.forEach(function(f) {
      var selectedAttr = (selected && selected === f.nama) ? ' selected' : '';
      $fak.append('<option value="' + f.nama + '"' + selectedAttr + '>' + f.nama + '</option>');
    });

    // Select2 membaca ulang <option> hanya saat diminta: tanpa trigger ini
    // kotaknya tetap menampilkan teks lama walau opsinya sudah berganti.
    $fak.trigger('change.select2');

    if (selected) {
      loadProdi(namaKampus, selected);
    }
  }

  function loadProdi(namaKampus, fakultas) {
    var $prodi = $('#prodi');
    $prodi.html('<option value="">Pilih Program Studi</option>');

    var kampus = kampusData.find(function(k) { return k.nama === namaKampus; });
    if (!kampus) return;

    var fak = kampus.fakultas.find(function(f) { return f.nama === fakultas; });
    if (!fak) return;

    fak.prodi.forEach(function(p) {
      var selectedAttr = (selectedProdiId && String(selectedProdiId) === String(p.id)) ? ' selected' : '';
      $prodi.append('<option value="' + p.id + '"' + selectedAttr + '>' + p.nama + '</option>');
    });

    $prodi.trigger('change.select2');
  }

  $('#nama_kampus').change(function() {
    $('#fakultas').html('<option value="">Memuat data...</option>');
    loadFakultas($(this).val(), '');
  });

  $('#fakultas').change(function() {
    loadProdi($('#nama_kampus').val(), $(this).val());
  });

  if (selectedKampus) {
    loadFakultas(selectedKampus, selectedFakultas);
  } else {
    // Halaman baru (tidak ada preselect): kosongkan tampilan kotak select2
    // supaya ikut teks placeholder dari <option value="">-nya.
    $('#nama_kampus, #fakultas, #prodi').trigger('change.select2');
  }

  function updateWaliVisibility() {
    var showWali = $('input[name="ikut_kk"]:checked').val() === 'famili_lain';
    $('#section-wali').toggle(showWali);
    $('.wali-doc').toggle(showWali);
  }

  $('input[name="ikut_kk"]').on('change', updateWaliVisibility);

  updateWaliVisibility();

  // Nama berkas terpilih tampil di label custom-file (semua input file
  // profil memakai gaya custom-file, sama dengan KRS/KHS).
  $('.custom-file-input').on('change', function() {
    var nama = (this.files && this.files.length > 1)
      ? this.files.length + ' berkas dipilih'
      : (this.files[0] ? this.files[0].name : '');
    $(this).next('.custom-file-label').text(nama || 'Pilih file');
  });

  // ── Semester mengikuti jenjang, dan baris KRS/KHS mengikuti semester ─────
  //
  // Rentang semester per jenjang dikirim dari server (satu sumber: kelas
  // JenjangPendidikan), jadi tidak ada daftar kedua yang bisa berbeda.
  var semesterMap = @json($semesterMap);
  var semesterAwal = "{{ old('semester', $profile->semester ?? '') }}";

  function isiSemester(pertahankan) {
    var jenjang = $('#jenjang').val();
    var jumlah = semesterMap[jenjang] || 0;
    var $semester = $('#semester');

    $semester.html('<option value="">-- Pilih Semester --</option>');

    if (!jumlah) {
      // Belum ada jenjang: dropdown dikunci, bukan dikosongkan diam-diam.
      $semester.prop('disabled', true);
      return;
    }

    for (var i = 1; i <= jumlah; i++) {
      var selected = (pertahankan && String(i) === String(semesterAwal)) ? ' selected' : '';
      $semester.append('<option value="' + i + '"' + selected + '>' + i + '</option>');
    }

    $semester.prop('disabled', false);

    // Semester yang sudah dipilih tidak sah lagi di jenjang baru (mis. 8 -> D1):
    // dikosongkan supaya pengguna memilih ulang, bukan diam-diam dikoreksi.
    if (pertahankan && semesterAwal && Number(semesterAwal) > jumlah) {
      $semester.val('');
    }
  }

  // Satu input diaktifkan/dinonaktifkan mengikuti apakah jenisnya masih
  // diminta. Input yang nonaktif tidak ikut terkirim, jadi semester di luar
  // batas tidak pernah sampai ke backend (yang tetap memvalidasi ulang).
  // PENGECUALIAN: input yang sudah DIISI tetap dikirim walau jenisnya sedang
  // tidak diminta -- kalau ikut dinonaktifkan, isian user hilang diam-diam
  // saat ia mengubah semester sebelum menekan Simpan.
  function aturInput($input, tampil) {
    $input.each(function() {
      var adaIsi = (this.files && this.files.length > 0) || String(this.value).trim() !== '';
      $(this).prop('disabled', !tampil && !adaIsi);
    });
  }

  function tampilkanBarisAkademik() {
    var jenjang = $('#jenjang').val();
    var jumlah = semesterMap[jenjang] || 0;
    // Semester AKTIF menentukan batas berkas yang realistis dimiliki
    // mahasiswa (permintaan pengguna): mahasiswa semester 2 memegang KRS
    // semester 2 (rencana studi yang sedang berjalan) dan KHS semester 1
    // (hasil semester sebelumnya) -- KHS semester 2 baru terbit SETELAH
    // semester itu selesai, jadi barisnya tidak diminta.
    var semesterAktif = Number($('#semester').val()) || 0;

    $('.baris-akademik').each(function() {
      var semester = Number($(this).data('semester'));
      // Tabel KRS/KHS: satu baris tabel memuat KRS DAN KHS sekaligus, jadi
      // batasnya dihitung per jenis di dalam baris yang sama -- KRS sampai
      // semester aktif, KHS sampai semester sebelumnya (KHS semester berjalan
      // belum terbit). Barisnya tampil kalau salah satu jenisnya tampil.
      var tampilKrs = jumlah > 0 && semesterAktif > 0 && semester <= semesterAktif;
      var tampilKhs = jumlah > 0 && semesterAktif > 0 && semester <= semesterAktif - 1;
      var $baris = $(this);

      $baris.toggle(tampilKrs || tampilKhs);

      aturInput($baris.find('[name^="dokumen_akademik[krs]"]'), tampilKrs);
      aturInput($baris.find('[name^="dokumen_akademik[khs]"]'), tampilKhs);
    });

    // Teks petunjuk di atas grup KRS/KHS mengikuti batas yang sama, supaya
    // mahasiswa paham KHS semester terakhir memang tidak diminta.
    var petunjuk = $('#petunjuk-akademik');
    if (petunjuk.length && jumlah > 0 && semesterAktif > 0) {
      petunjuk.text('Semester Anda ' + semesterAktif + ': unggah KRS dan isi nilai KHS semester 1 sampai ' +
        semesterAktif + '.');
    }
  }

  $('#jenjang').on('change', function() {
    // Nilai lama dilepas dulu supaya semester jenjang sebelumnya tidak
    // terpilih otomatis di jenjang baru.
    semesterAwal = '';
    isiSemester(false);
    $('#semester').val('');
    tampilkanBarisAkademik();
  });

  // Perubahan SEMESTER juga menata ulang baris KRS/KHS -- itulah inti
  // permintaan: memilih semester langsung menggeser batas berkas yang
  // diminta (KRS sampai semester itu, KHS sampai semester sebelumnya).
  $('#semester').on('change', tampilkanBarisAkademik);

  isiSemester(true);
  tampilkanBarisAkademik();

</script>
@endpush
@endif
