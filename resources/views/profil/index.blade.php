@extends('layouts.app')

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
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if(! $profileComplete && count($missingFields) > 0)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <strong><i class="fas fa-exclamation-triangle"></i> Profil belum lengkap!</strong><br>
      Anda harus melengkapi data berikut sebelum bisa mendaftar beasiswa:
      <ul class="mb-0 mt-2">
        @foreach($missingFields as $field)
        <li>{{ $field }}</li>
        @endforeach
      </ul>
      <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    @if($profileComplete)
    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i> Profil Anda sudah lengkap. Anda bisa mendaftar beasiswa.
    </div>
    @endif

    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h4>Edit Profil</h4>
          </div>
          <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profilForm">
            @csrf
            @method('PUT')
            <div class="card-body">

              {{-- FOTO PROFIL --}}
              <div class="form-group">
                <label>Foto Profil</label>
                <div class="d-flex align-items-center">
                  <div class="mr-3">
                    @if($profile && $profile->foto_profil)
                    <img src="{{ route('profile.photo', $profile->foto_profil) }}" alt="Foto Profil"
                      class="rounded-circle" width="100" height="100" id="previewImg" style="object-fit:cover;">
                    @else
                    <img src="{{ asset('assets/img/avatar/avatar-1.png') }}" alt="Avatar" class="rounded-circle"
                      width="100" height="100" id="previewImg">
                    @endif
                  </div>
                  <div>
                    <input type="file" class="form-control @error('foto_profil') is-invalid @enderror"
                      name="foto_profil" id="foto_profil" accept="image/*">
                    @error('foto_profil')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Format: JPG, JPEG, PNG. Maks 2MB.</small>
                    @if($profile && $profile->foto_profil)
                    <div class="mt-2">
                      <button type="button" class="btn btn-sm btn-danger" id="hapusFotoBtn"
                        data-info-url="{{ route('profile.photo.info') }}"
                        data-action="{{ route('profile.photo.delete') }}">
                        <i class="fas fa-trash"></i> Hapus Foto
                      </button>
                    </div>
                    @endif
                  </div>
                </div>
              </div>

              {{-- DATA DIRI --}}
              <h5 class="mb-3">Data Diri</h5>
              <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror" id="nama_lengkap"
                  name="nama_lengkap" value="{{ old('nama_lengkap', $profile->nama_lengkap ?? '') }}">
                @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nik">NIK <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('nik') is-invalid @enderror" id="nik" name="nik"
                    value="{{ old('nik', $profile->nik ?? '') }}" maxlength="16">
                  @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="telepon">Telepon <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('telepon') is-invalid @enderror" id="telepon"
                    name="telepon" value="{{ old('telepon', $profile->telepon ?? '') }}">
                  @error('telepon')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                  <label for="jenis_kelamin">Jenis Kelamin <span class="text-danger">*</span></label>
                  <select class="form-control @error('jenis_kelamin') is-invalid @enderror" id="jenis_kelamin"
                    name="jenis_kelamin">
                    <option value="">Pilih Jenis Kelamin</option>
                    <option value="Laki-laki" {{ old('jenis_kelamin', $profile->jenis_kelamin ?? '') === 'Laki-laki' ?
                      'selected' : '' }}>Laki-laki</option>
                    <option value="Perempuan" {{ old('jenis_kelamin', $profile->jenis_kelamin ?? '') === 'Perempuan' ?
                      'selected' : '' }}>Perempuan</option>
                  </select>
                  @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="agama">Agama <span class="text-danger">*</span></label>
                  <select class="form-control @error('agama') is-invalid @enderror" id="agama" name="agama">
                    <option value="">Pilih Agama</option>
                    <option value="Islam" {{ old('agama', $profile->agama ?? '') === 'Islam' ? 'selected' : '' }}>Islam
                    </option>
                    <option value="Kristen" {{ old('agama', $profile->agama ?? '') === 'Kristen' ? 'selected' : ''
                      }}>Kristen</option>
                    <option value="Katholik" {{ old('agama', $profile->agama ?? '') === 'Katholik' ? 'selected' : ''
                      }}>Katholik</option>
                    <option value="Hindu" {{ old('agama', $profile->agama ?? '') === 'Hindu' ? 'selected' : '' }}>Hindu
                    </option>
                    <option value="Buddha" {{ old('agama', $profile->agama ?? '') === 'Buddha' ? 'selected' : ''
                      }}>Buddha</option>
                    <option value="Konghucu" {{ old('agama', $profile->agama ?? '') === 'Konghucu' ? 'selected' : ''
                      }}>Konghucu</option>
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
                  <select class="form-control @error('nama_kampus') is-invalid @enderror" id="nama_kampus"
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
                  <select class="form-control @error('fakultas') is-invalid @enderror" id="fakultas" name="fakultas">
                    <option value="">Pilih Kampus terlebih dahulu</option>
                  </select>
                  @error('fakultas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="prodi">Program Studi <span class="text-danger">*</span></label>
                  <select class="form-control @error('prodi_id') is-invalid @enderror" id="prodi" name="prodi_id">
                    <option value="">Pilih Fakultas terlebih dahulu</option>
                  </select>
                  @error('prodi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="ipk">IPK <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" min="0" max="4"
                    class="form-control @error('ipk') is-invalid @enderror" id="ipk" name="ipk"
                    value="{{ old('ipk', $profile->ipk ?? '') }}">
                  @error('ipk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="semester">Semester <span class="text-danger">*</span></label>
                  <input type="number" min="1" max="14" class="form-control @error('semester') is-invalid @enderror"
                    id="semester" name="semester" value="{{ old('semester', $profile->semester ?? '') }}">
                  @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <hr>

              {{-- DATA ORANG TUA --}}
              <h5 class="mb-3">Data Orang Tua</h5>
              <div class="form-group">
                <label for="status_orang_tua">Status Orang Tua <span class="text-danger">*</span></label>
                <select class="form-control @error('status_orang_tua') is-invalid @enderror" id="status_orang_tua"
                  name="status_orang_tua">
                  <option value="">Pilih Status</option>
                  <option value="Lengkap" {{ old('status_orang_tua', $profile->status_orang_tua ?? '') === 'Lengkap' ?
                    'selected' : '' }}>Lengkap (Ayah & Ibu)</option>
                  <option value="Yatim" {{ old('status_orang_tua', $profile->status_orang_tua ?? '') === 'Yatim' ?
                    'selected' : '' }}>Yatim (Ayah Meninggal)</option>
                  <option value="Piatu" {{ old('status_orang_tua', $profile->status_orang_tua ?? '') === 'Piatu' ?
                    'selected' : '' }}>Piatu (Ibu Meninggal)</option>
                  <option value="Yatim Piatu" {{ old('status_orang_tua', $profile->status_orang_tua ?? '') === 'Yatim
                    Piatu' ? 'selected' : '' }}>Yatim Piatu (Tinggal dengan Wali)</option>
                </select>
                @error('status_orang_tua')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <h6 class="text-muted mb-2">Ayah</h6>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nama_ayah">Nama Ayah @if(in_array($profile->status_orang_tua ?? '', ['Lengkap',
                    'Piatu']))<span class="text-danger">*</span>@endif</label>
                  <input type="text" class="form-control @error('nama_ayah') is-invalid @enderror" id="nama_ayah"
                    name="nama_ayah" value="{{ old('nama_ayah', $profile->nama_ayah ?? '') }}">
                  @error('nama_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="nik_ayah">NIK Ayah @if(in_array($profile->status_orang_tua ?? '', ['Lengkap',
                    'Piatu']))<span class="text-danger">*</span>@endif</label>
                  <input type="text" class="form-control @error('nik_ayah') is-invalid @enderror" id="nik_ayah"
                    name="nik_ayah" value="{{ old('nik_ayah', $profile->nik_ayah ?? '') }}" maxlength="16">
                  @error('nik_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="pekerjaan_ayah">Pekerjaan Ayah @if(in_array($profile->status_orang_tua ?? '', ['Lengkap',
                    'Piatu']))<span class="text-danger">*</span>@endif</label>
                  <select class="form-control @error('pekerjaan_ayah') is-invalid @enderror" id="pekerjaan_ayah"
                    name="pekerjaan_ayah">
                    <option value="">Pilih Pekerjaan</option>
                    @foreach(['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'] as $pekerjaan)
                    <option value="{{ $pekerjaan }}" {{ old('pekerjaan_ayah', $profile->pekerjaan_ayah ?? '') ===
                      $pekerjaan ? 'selected' : '' }}>{{ $pekerjaan }}</option>
                    @endforeach
                  </select>
                  @error('pekerjaan_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="penghasilan_ayah">Penghasilan Ayah @if(in_array($profile->status_orang_tua ?? '',
                    ['Lengkap', 'Piatu']))<span class="text-danger">*</span>@endif</label>
                  <select class="form-control @error('penghasilan_ayah') is-invalid @enderror" id="penghasilan_ayah"
                    name="penghasilan_ayah">
                    <option value="">Pilih Penghasilan</option>
                    @foreach(['< 1jt', '1-3jt', '3-5jt', '5-10jt', '> 10jt'] as $penghasilan)
                    <option value="{{ $penghasilan }}" {{ old('penghasilan_ayah', $profile->penghasilan_ayah ?? '') ===
                      $penghasilan ? 'selected' : '' }}>{{ $penghasilan }}</option>
                      @endforeach
                  </select>
                  @error('penghasilan_ayah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <h6 class="text-muted mb-2 mt-3">Ibu</h6>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nama_ibu">Nama Ibu @if(in_array($profile->status_orang_tua ?? '', ['Lengkap',
                    'Yatim']))<span class="text-danger">*</span>@endif</label>
                  <input type="text" class="form-control @error('nama_ibu') is-invalid @enderror" id="nama_ibu"
                    name="nama_ibu" value="{{ old('nama_ibu', $profile->nama_ibu ?? '') }}">
                  @error('nama_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="nik_ibu">NIK Ibu @if(in_array($profile->status_orang_tua ?? '', ['Lengkap',
                    'Yatim']))<span class="text-danger">*</span>@endif</label>
                  <input type="text" class="form-control @error('nik_ibu') is-invalid @enderror" id="nik_ibu"
                    name="nik_ibu" value="{{ old('nik_ibu', $profile->nik_ibu ?? '') }}" maxlength="16">
                  @error('nik_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="pekerjaan_ibu">Pekerjaan Ibu @if(in_array($profile->status_orang_tua ?? '', ['Lengkap',
                    'Yatim']))<span class="text-danger">*</span>@endif</label>
                  <select class="form-control @error('pekerjaan_ibu') is-invalid @enderror" id="pekerjaan_ibu"
                    name="pekerjaan_ibu">
                    <option value="">Pilih Pekerjaan</option>
                    @foreach(['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'] as $pekerjaan)
                    <option value="{{ $pekerjaan }}" {{ old('pekerjaan_ibu', $profile->pekerjaan_ibu ?? '') ===
                      $pekerjaan ? 'selected' : '' }}>{{ $pekerjaan }}</option>
                    @endforeach
                  </select>
                  @error('pekerjaan_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="penghasilan_ibu">Penghasilan Ibu @if(in_array($profile->status_orang_tua ?? '',
                    ['Lengkap', 'Yatim']))<span class="text-danger">*</span>@endif</label>
                  <select class="form-control @error('penghasilan_ibu') is-invalid @enderror" id="penghasilan_ibu"
                    name="penghasilan_ibu">
                    <option value="">Pilih Penghasilan</option>
                    @foreach(['< 1jt', '1-3jt', '3-5jt', '5-10jt', '> 10jt'] as $penghasilan)
                    <option value="{{ $penghasilan }}" {{ old('penghasilan_ibu', $profile->penghasilan_ibu ?? '') ===
                      $penghasilan ? 'selected' : '' }}>{{ $penghasilan }}</option>
                      @endforeach
                  </select>
                  @error('penghasilan_ibu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <hr>

              {{-- DATA WALI (hanya jika yatim piatu) --}}
              <h6 class="text-muted mb-2">Wali @if(($profile->status_orang_tua ?? '') === 'Yatim Piatu')<span
                  class="text-danger">*</span>@endif</h6>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="nama_wali">Nama Wali @if(($profile->status_orang_tua ?? '') === 'Yatim Piatu')<span
                      class="text-danger">*</span>@endif</label>
                  <input type="text" class="form-control @error('nama_wali') is-invalid @enderror" id="nama_wali"
                    name="nama_wali" value="{{ old('nama_wali', $profile->nama_wali ?? '') }}">
                  @error('nama_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="nik_wali">NIK Wali @if(($profile->status_orang_tua ?? '') === 'Yatim Piatu')<span
                      class="text-danger">*</span>@endif</label>
                  <input type="text" class="form-control @error('nik_wali') is-invalid @enderror" id="nik_wali"
                    name="nik_wali" value="{{ old('nik_wali', $profile->nik_wali ?? '') }}" maxlength="16">
                  @error('nik_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="hubungan_wali">Hubungan @if(($profile->status_orang_tua ?? '') === 'Yatim Piatu')<span
                      class="text-danger">*</span>@endif</label>
                  <select class="form-control @error('hubungan_wali') is-invalid @enderror" id="hubungan_wali"
                    name="hubungan_wali">
                    <option value="">Pilih Hubungan</option>
                    @foreach(['Paman', 'Bibi', 'Kakek', 'Nenek', 'Lainnya'] as $hubungan)
                    <option value="{{ $hubungan }}" {{ old('hubungan_wali', $profile->hubungan_wali ?? '') === $hubungan
                      ? 'selected' : '' }}>{{ $hubungan }}</option>
                    @endforeach
                  </select>
                  @error('hubungan_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="pekerjaan_wali">Pekerjaan Wali @if(($profile->status_orang_tua ?? '') === 'Yatim
                    Piatu')<span class="text-danger">*</span>@endif</label>
                  <select class="form-control @error('pekerjaan_wali') is-invalid @enderror" id="pekerjaan_wali"
                    name="pekerjaan_wali">
                    <option value="">Pilih Pekerjaan</option>
                    @foreach(['PNS/TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Buruh', 'Tidak Bekerja', 'Lainnya'] as $pekerjaan)
                    <option value="{{ $pekerjaan }}" {{ old('pekerjaan_wali', $profile->pekerjaan_wali ?? '') ===
                      $pekerjaan ? 'selected' : '' }}>{{ $pekerjaan }}</option>
                    @endforeach
                  </select>
                  @error('pekerjaan_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="penghasilan_wali">Penghasilan Wali @if(($profile->status_orang_tua ?? '') === 'Yatim
                    Piatu')<span class="text-danger">*</span>@endif</label>
                  <select class="form-control @error('penghasilan_wali') is-invalid @enderror" id="penghasilan_wali"
                    name="penghasilan_wali">
                    <option value="">Pilih Penghasilan</option>
                    @foreach(['< 1jt', '1-3jt', '3-5jt', '5-10jt', '> 10jt'] as $penghasilan)
                    <option value="{{ $penghasilan }}" {{ old('penghasilan_wali', $profile->penghasilan_wali ?? '') ===
                      $penghasilan ? 'selected' : '' }}>{{ $penghasilan }}</option>
                      @endforeach
                  </select>
                  @error('penghasilan_wali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

            </div>
            <div class="card-footer text-right">
              <button type="submit" class="btn btn-primary">Simpan Profil</button>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-4">
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
              Lengkapi profil Anda untuk mempermudah proses pendaftaran beasiswa.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('script')
<script>
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#hapusFotoBtn');
    if (!btn) return;

    e.preventDefault();

    fetch(btn.dataset.infoUrl, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        Swal.fire({
          title: data.title,
          text: data.text,
          icon: data.icon,
          showCancelButton: true,
          confirmButtonColor: data.confirmButtonColor,
          cancelButtonColor: '#6c757d',
          confirmButtonText: data.confirmButtonText,
          cancelButtonText: 'Batal',
        }).then(function (result) {
          if (result.isConfirmed) {
            fetch(btn.dataset.action, {
              method: 'DELETE',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
              },
            }).then(function () {
              window.location.reload();
            });
          }
        });
      })
      .catch(function () {
        Swal.fire('Error', 'Gagal memuat data. Silakan coba lagi.', 'error');
      });
  });

  document.getElementById('foto_profil').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file) {
      var reader = new FileReader();
      reader.onload = function(event) {
        document.getElementById('previewImg').src = event.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  flatpickr(".flatpickr", {
    dateFormat: "Y-m-d"
  });

  var desaUrlBase = "{{ url('/api/wilayah/desa') }}";

  function populateDesa(code, selectedDesa) {
    if (!code) {
      $('#desa_kelurahan').html('<option value="">Pilih Kecamatan terlebih dahulu</option>');
      return;
    }
    $.getJSON(desaUrlBase + '/' + code, function(data) {
      var options = '<option value="">Pilih Desa/Kelurahan</option>';
      $.each(data, function(i, item) {
        var selected = (selectedDesa && selectedDesa === item.village) ? ' selected' : '';
        options += '<option value="' + item.village + '"' + selected + '>' + item.village + '</option>';
      });
      $('#desa_kelurahan').html(options);
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

    var kampus = kampusData.find(function(k) { return k.nama === namaKampus; });
    if (!kampus) return;

    kampus.fakultas.forEach(function(f) {
      var selectedAttr = (selected && selected === f.nama) ? ' selected' : '';
      $fak.append('<option value="' + f.nama + '"' + selectedAttr + '>' + f.nama + '</option>');
    });

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
  }
</script>
@endpush