@php
  $showDownloadDocs = $showDownloadDocs ?? false;
  $profile = $profile ?? null;

  $documentSections = [
    'Dokumen Diri Sendiri' => [
      ['key' => 'foto_profil', 'label' => 'Pas Foto 3x4'],
      ['key' => 'dokumen_ktp', 'label' => 'Kartu Tanda Penduduk (KTP)'],
      ['key' => 'dokumen_kk', 'label' => 'Kartu Keluarga (KK)'],
      ['key' => 'dokumen_desil', 'label' => 'Dokumen Desil'],
      ['key' => 'dokumen_sktm', 'label' => 'Surat Keterangan Tidak Mampu (SKTM)'],
    ],
    'Dokumen untuk Kampus' => [
      ['key' => 'dokumen_transkrip', 'label' => 'Transkrip Nilai / KHS'],
      ['key' => 'dokumen_surat_aktif', 'label' => 'Surat Aktif Kuliah / KTM'],
      ['key' => 'dokumen_surat_pernyataan', 'label' => 'Surat Pernyataan Tidak Menerima Beasiswa Lain'],
      ['key' => 'dokumen_bukti_ukt', 'label' => 'Bukti Pembayaran UKT/SPP'],
    ],
    'Dokumen Orang Tua / Wali' => [
      ['key' => 'ktp_ayah', 'label' => 'KTP Ayah'],
      ['key' => 'ktp_ibu', 'label' => 'KTP Ibu'],
      ['key' => 'ktp_wali', 'label' => 'KTP Wali'],
      ['key' => 'kk_wali', 'label' => 'Kartu Keluarga Wali'],
    ],
  ];
@endphp

{{-- Card: Data Diri --}}
<div class="card">
  <div class="card-header">
    <h4>Data Diri</h4>
  </div>
  <div class="card-body">
    <div class="row">
      @php
        $dataDiri = [
          ['label' => 'Nama Lengkap', 'value' => $profile?->nama_lengkap ?? '-'],
          ['label' => 'Email', 'value' => $profile?->user?->email ?? '-'],
          ['label' => 'NIK', 'value' => $profile?->nik ?? '-'],
          ['label' => 'No. Kartu Keluarga', 'value' => $profile?->no_kk ?? '-'],
          ['label' => 'Jenis Kelamin', 'value' => $profile?->jenis_kelamin ?? '-'],
          ['label' => 'Tempat, Tanggal Lahir', 'value' => ($profile?->tempat_lahir ?? '-').', '.(! blank($profile?->tanggal_lahir) ? $profile->tanggal_lahir->translatedFormat('d F Y') : '-')],
          ['label' => 'Agama', 'value' => $profile?->agama ?? '-'],
          ['label' => 'Telepon', 'value' => $profile?->telepon ?? '-'],
          ['label' => 'Desil', 'value' => $profile?->desil ? 'Desil '.$profile->desil : '-'],
          ['label' => 'Kabupaten', 'value' => $profile?->kabupaten_kota ?: 'Balangan'],
          ['label' => 'Kecamatan', 'value' => $profile?->kecamatan ?? '-'],
          ['label' => 'Desa/Kelurahan', 'value' => $profile?->desa_kelurahan ?? '-'],
        ];
      @endphp
      @foreach($dataDiri as $field)
      <div class="col-lg-4 col-md-6">
        <div class="mb-3">
          <div class="text-muted small">{{ $field['label'] }}</div>
          <div class="font-weight-bold">{{ $field['value'] }}</div>
        </div>
      </div>
      @endforeach
      <div class="col-12">
        <div class="mb-0">
          <div class="text-muted small">Alamat</div>
          <div class="font-weight-bold">{{ $profile?->alamat ?? '-' }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Card: Data Kampus --}}
<div class="card">
  <div class="card-header">
    <h4>Data Kampus</h4>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-4">
        <div class="mb-3">
          <div class="text-muted small">Nama Kampus</div>
          <div class="font-weight-bold">{{ $profile?->prodi?->fakultas?->kampus?->nama_kampus ?? '-' }}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mb-3">
          <div class="text-muted small">Fakultas</div>
          <div class="font-weight-bold">{{ $profile?->prodi?->fakultas?->nama ?? '-' }}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mb-3">
          <div class="text-muted small">Program Studi</div>
          <div class="font-weight-bold">{{ $profile?->prodi?->nama ?? '-' }}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mb-3">
          <div class="text-muted small">IPK</div>
          <div class="font-weight-bold">{{ $profile?->ipk ?? '-' }}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mb-3">
          <div class="text-muted small">Semester</div>
          <div class="font-weight-bold">{{ $profile?->semester ?? '-' }}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="mb-3">
          <div class="text-muted small">NIM</div>
          <div class="font-weight-bold">{{ $profile?->nim ?? '-' }}</div>
        </div>
      </div>
      <div class="col-12">
        <div class="mb-0">
          <div class="text-muted small">UKT</div>
          <div class="font-weight-bold">{{ $profile?->ukt ? 'Rp '.number_format($profile->ukt, 0, ',', '.') : '-' }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Card: Data Orang Tua & Wali --}}
<div class="card">
  <div class="card-header">
    <h4>Data Orang Tua &amp; Wali</h4>
  </div>
  <div class="card-body">
    <div class="mb-3">
      <div class="text-muted small">Ikut KK</div>
      <div class="d-flex flex-wrap">
        @foreach(['ayah' => 'KK Ayah', 'ibu' => 'KK Ibu', 'wali' => 'KK Wali'] as $value => $label)
        <div class="custom-control custom-checkbox mr-4 mb-1">
          <input type="checkbox" class="custom-control-input" id="kk-{{ $value }}" disabled {{ ($profile?->ikut_kk ?? null) === $value ? 'checked' : '' }}>
          <label class="custom-control-label" for="kk-{{ $value }}">{{ $label }}</label>
        </div>
        @endforeach
      </div>
    </div>
    <div class="mb-3">
      <h6 class="text-muted mb-2"><i class="fas fa-user mr-1"></i>Ayah</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="text-muted small">Nama</div>
          <div class="font-weight-bold">{{ $profile?->nama_ayah ?? '-' }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small">NIK</div>
          <div class="font-weight-bold">{{ $profile?->nik_ayah ?? '-' }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small">Pekerjaan</div>
          <div class="font-weight-bold">{{ $profile?->pekerjaan_ayah ?? '-' }}</div>
        </div>
      </div>
    </div>
    <hr>
    <div class="mb-3">
      <h6 class="text-muted mb-2"><i class="fas fa-user mr-1"></i>Ibu</h6>
      <div class="row">
        <div class="col-md-4">
          <div class="text-muted small">Nama</div>
          <div class="font-weight-bold">{{ $profile?->nama_ibu ?? '-' }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small">NIK</div>
          <div class="font-weight-bold">{{ $profile?->nik_ibu ?? '-' }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small">Pekerjaan</div>
          <div class="font-weight-bold">{{ $profile?->pekerjaan_ibu ?? '-' }}</div>
        </div>
      </div>
    </div>
    @if($profile?->kk_ikut_wali)
    <hr>
    <div class="mb-0">
      <h6 class="text-muted mb-2"><i class="fas fa-user mr-1"></i>Wali</h6>
      <div class="row">
        <div class="col-md-3">
          <div class="text-muted small">Nama</div>
          <div class="font-weight-bold">{{ $profile->nama_wali ?? '-' }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">NIK</div>
          <div class="font-weight-bold">{{ $profile->nik_wali ?? '-' }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Hubungan</div>
          <div class="font-weight-bold">{{ $profile->hubungan_wali ?? '-' }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Pekerjaan</div>
          <div class="font-weight-bold">{{ $profile->pekerjaan_wali ?? '-' }}</div>
        </div>
      </div>
    </div>
    @else
    <div class="alert alert-secondary mb-0"><i class="fas fa-info-circle"></i> Data wali tidak diikutsertakan (tidak aktif pada kartu keluarga).</div>
    @endif
  </div>
</div>

{{-- Card: Dokumen Pendukung --}}
<div class="card">
  <div class="card-header">
    <h4>Dokumen Pendukung</h4>
  </div>
  <div class="card-body">
    <small class="text-muted d-block mb-3">Dokumen diambil dari data profil.</small>
    @foreach($documentSections as $sectionName => $docs)
    @unless($loop->first)
    <hr>
    @endunless
    <h6 class="mt-3 mb-3"><i class="fas fa-folder text-primary mr-1"></i>{{ $sectionName }}</h6>
    <div class="row">
      @foreach($docs as $doc)
      <div class="col-lg-6 mb-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <span class="font-weight-bold mr-2">{{ $doc['label'] }}</span>
          <span>
            @if($profile && $profile->{$doc['key']})
            <a href="{{ route('dokumen.show', $profile->{$doc['key']}) }}" target="_blank"
              class="btn btn-sm btn-primary">
              <i class="fas fa-eye"></i> Lihat
            </a>
            @if($showDownloadDocs)
            <a href="{{ route('dokumen.show', $profile->{$doc['key']}) }}" download
              class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-download"></i> Download
            </a>
            @endif
            @else
            <span class="text-muted">Tidak ada</span>
            @endif
          </span>
        </div>
      </div>
      @endforeach
    </div>
    @endforeach

    <hr>
    <div class="mb-3">
      <h6 class="mb-0"><i class="fas fa-trophy text-primary mr-1"></i>Sertifikat Prestasi</h6>
    </div>
    @if($profile && $profile->dokumen_prestasi && count($profile->dokumen_prestasi) > 0)
    <div class="row">
      @foreach($profile->dokumen_prestasi as $index => $dokumen)
      <div class="col-lg-6 mb-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <span class="text-muted font-weight-bold mr-2">Prestasi {{ $index + 1 }}</span>
          <span>
            <a href="{{ route('dokumen.show', $dokumen) }}" target="_blank" class="btn btn-sm btn-primary">
              <i class="fas fa-eye"></i> Lihat
            </a>
            @if($showDownloadDocs)
            <a href="{{ route('dokumen.show', $dokumen) }}" download class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-download"></i> Download
            </a>
            @endif
          </span>
        </div>
      </div>
      @endforeach
    </div>
    @else
    <span class="text-muted">Tidak ada</span>
    @endif
  </div>
</div>