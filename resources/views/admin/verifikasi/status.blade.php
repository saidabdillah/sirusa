@extends('layouts.app')

@php
  // Halaman status untuk peran Kesra, super_admin, Catpil, dan Kampus (menu
  // leaf mandiri di section "Administrasi"). Bukan antrean -- hanya rekap
  // status profil (Catpil, Kampus, Kesra), tanpa kolom aksi. Filter terbagi
  // dua grup dengan jarak: status per tahap (verif_catpil/verif_kampus) lalu
  // lokasi Kampus/Fakultas/Jurusan yang semuanya aktif dengan daftar penuh.
  // Sumber data di `VerifikasiController::status()`.
  $routePrefix = 'admin.'.\App\Models\UserProfile::verifRoutePrefixes()['kesra'];
  $statusOptions = [
      'menunggu' => 'Menunggu',
      'revisi' => 'Perlu Perbaikan',
      'setuju' => 'Disetujui',
      'tolak' => 'Ditolak',
  ];
  $semuaStatus = \App\Models\Applicant::FILTER_ALL;
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Status Verifikasi</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Status Verifikasi</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Status Verifikasi Profil Mahasiswa</h4>
          </div>
          <div class="card-body">
            {{-- Dua grup filter yang diberi jarak (tanpa garis pemisah): status
                 per tahap di baris pertama, lokasi (Kampus/Fakultas/Jurusan) di
                 baris kedua. Ketiga filter lokasi JANGAN disabled dan opsinya
                 dinamis -- Fakultas hanya memuat milik kampus yang terpilih,
                 Jurusan hanya milik fakultas yang terpilih, dan tanpa induk
                 terpilih daftarnya tampil penuh. --}}
            <form method="GET" action="{{ route($routePrefix.'.status') }}" class="mb-3">
              <div class="form-row align-items-end">
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="filter-verif-catpil">Verifikasi Catpil</label>
                  <select class="form-control" name="verif_catpil" id="filter-verif-catpil">
                    <option value="{{ $semuaStatus }}" {{ $filterCatpil === null ? 'selected' : '' }}>-- Semua Status --</option>
                    @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ $filterCatpil === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="filter-verif-kampus">Verifikasi Kampus</label>
                  <select class="form-control" name="verif_kampus" id="filter-verif-kampus">
                    <option value="{{ $semuaStatus }}" {{ $filterKampus === null ? 'selected' : '' }}>-- Semua Status --</option>
                    @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ $filterKampus === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                  <button type="submit" class="btn btn-primary btn-block" data-loading-text="Menerapkan filter...">
                    <i class="fas fa-filter mr-1"></i> Terapkan
                  </button>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                  <a href="{{ route($routePrefix.'.status') }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-redo"></i> Reset
                  </a>
                </div>
              </div>
              <div class="form-row align-items-end mt-3">
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="filter-kampus">Kampus</label>
                  <select class="form-control" name="kampus_id" id="filter-kampus">
                    <option value="" {{ $kampusId === null ? 'selected' : '' }}>-- Semua Kampus --</option>
                    @foreach($kampusOptions as $kampus)
                    <option value="{{ $kampus->id }}" {{ $kampusId === $kampus->id ? 'selected' : '' }}>{{ $kampus->nama_kampus }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="filter-fakultas">Fakultas</label>
                  <select class="form-control" name="fakultas_id" id="filter-fakultas">
                    <option value="" {{ $fakultasId === null ? 'selected' : '' }}>-- Semua Fakultas --</option>
                    @foreach($fakultasOptions as $fakultas)
                    <option value="{{ $fakultas->id }}" {{ $fakultasId === $fakultas->id ? 'selected' : '' }}>{{ $fakultas->nama }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="filter-jurusan">Jurusan</label>
                  <select class="form-control" name="jurusan_id" id="filter-jurusan">
                    <option value="" {{ $jurusanId === null ? 'selected' : '' }}>-- Semua Jurusan --</option>
                    @foreach($jurusanOptions as $jurusan)
                    <option value="{{ $jurusan->id }}" {{ $jurusanId === $jurusan->id ? 'selected' : '' }}>{{ $jurusan->nama }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </form>
            <div class="table-responsive">
              <table class="table table-striped" id="statusTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Kampus</th>
                    <th>Fakultas</th>
                    <th>Jurusan</th>
                    <th>Verifikasi Catpil</th>
                    <th>Verifikasi Kampus</th>
                    <th>Verifikasi Kesra</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($users as $user)
                  @php
                    $profile = $user->profile;
                    $catpil = $profile->verifStageDecision('catpil');
                    $kampus = $profile->verifStageDecision('kampus');
                    // Tahap Kesra diturunkan dari baris `pendaftar` (diterima ->
                    // Disetujui, ditolak -> Ditolak), BUKAN dari `verif_kesra`
                    // yang selalu `setuju` walau pendaftarannya ditolak.
                    $kesra = $user->kesraDecision();
                  @endphp
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $profile->nama_lengkap }}</td>
                    <td>{{ $profile->nim ?? '-' }}</td>
                    {{-- `Kampus` punya kolom `nama_kampus`; menulis `kampus->nama`
                         selalu membalas null. --}}
                    <td>{{ $profile->prodi?->fakultas?->kampus?->nama_kampus ?? '-' }}</td>
                    <td>{{ $profile->prodi?->fakultas?->nama ?? '-' }}</td>
                    {{-- Tidak ada tabel Jurusan; level ketiga filter lokasi adalah
                         Prodi, dan kolomnya berjudul "Jurusan" biar sejalan. --}}
                    <td>{{ $profile->prodi?->nama ?? '-' }}</td>
                    <td><span class="badge badge-{{ $catpil['badge'] }}">{{ $catpil['label'] }}</span></td>
                    <td><span class="badge badge-{{ $kampus['badge'] }}">{{ $kampus['label'] }}</span></td>
                    <td><span class="badge badge-{{ $kesra['badge'] }}">{{ $kesra['label'] }}</span></td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
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
  $(document).ready(function() {
    $('#statusTable').DataTable({
      order: [],
      processing: true,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        emptyTable: "Belum ada profil mahasiswa.",
        infoFiltered: "(disaring dari _MAX_ total data)",
        zeroRecords: "Tidak ada data yang cocok",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Selanjutnya",
          previous: "Sebelumnya"
        }
      }
    });
  });
</script>
@endpush