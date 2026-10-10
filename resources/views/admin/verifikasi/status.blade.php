@extends('layouts.app')

@php
  // Halaman status untuk peran Kesra, super_admin, Catpil, dan Kampus (menu
  // leaf mandiri di section "Administrasi"). Bukan antrean -- hanya rekap
  // status profil (Catpil, Kampus, Kesra), tanpa kolom aksi. Filter lokasi
  // (Kampus/Fakultas/Program Studi) semuanya aktif dengan daftar penuh;
  // filter status per tahap (verif_catpil/verif_kampus) DIHAPUS atas
  // permintaan pengguna. Sumber data di `VerifikasiController::status()`.
  $routePrefix = 'admin.'.\App\Models\UserProfile::verifRoutePrefixes()['kesra'];
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
            {{-- Filter lokasi (Kampus/Fakultas/Program Studi), semuanya aktif
                 dengan daftar penuh dan opsi dinamis -- Fakultas hanya memuat
                 milik kampus terpilih, Program Studi hanya milik fakultas terpilih.
                 Filter status per tahap (verif_catpil/verif_kampus) DIHAPUS atas
                 permintaan pengguna. Select dibagi rata `col-md-3` (kisi 4 kolom,
                 sama dengan halaman Penerima Beasiswa) dan tombol Terapkan/Reset
                 dibuat SEJAJAR di dalam `form-row` yang sama (col-auto, rata bawah). --}}
            <form method="GET" action="{{ route($routePrefix.'.status') }}" class="mb-3">
              <div class="form-row align-items-end">
                <div class="col-md-3 mb-2 mb-md-0">
                  <label for="filter-kampus">Kampus</label>
                  <select class="form-control" name="kampus_id" id="filter-kampus">
                    <option value="" {{ $kampusId === null ? 'selected' : '' }}>-- Semua Kampus --</option>
                    @foreach($kampusOptions as $kampus)
                    <option value="{{ $kampus->id }}" {{ $kampusId === $kampus->id ? 'selected' : '' }}>{{ $kampus->nama_kampus }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                  <label for="filter-fakultas">Fakultas</label>
                  <select class="form-control" name="fakultas_id" id="filter-fakultas">
                    <option value="" {{ $fakultasId === null ? 'selected' : '' }}>-- Semua Fakultas --</option>
                    @foreach($fakultasOptions as $fakultas)
                    <option value="{{ $fakultas->id }}" {{ $fakultasId === $fakultas->id ? 'selected' : '' }}>{{ $fakultas->nama }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                  <label for="filter-jurusan">Program Studi</label>
                  <select class="form-control" name="jurusan_id" id="filter-jurusan">
                    <option value="" {{ $jurusanId === null ? 'selected' : '' }}>-- Semua Program Studi --</option>
                    @foreach($jurusanOptions as $jurusan)
                    <option value="{{ $jurusan->id }}" {{ $jurusanId === $jurusan->id ? 'selected' : '' }}>{{ $jurusan->nama }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-auto mb-2 mb-md-0">
                  <button type="submit" class="btn btn-primary" data-loading-text="Menerapkan filter...">
                    <i class="fas fa-filter mr-1"></i> Terapkan
                  </button>
                </div>
                <div class="col-auto mb-2 mb-md-0">
                  <a href="{{ route($routePrefix.'.status') }}" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                  </a>
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
                    <th>Program Studi</th>
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
                         Prodi, dan kolomnya berjudul "Program Studi". Param
                         query-nya tetap `jurusan_id` supaya tidak memutus
                         aturan filter lokasi yang sudah ada. --}}
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
    // Tabel hanya boleh diinisialisasi SEKALI. Tanpa penjaga ini, skrip yang
    // berjalan dua kali (mis. halaman dipulihkan dari cache peramban) menanam
    // toolbar "Tampilkan N data"/"Cari:" dobel di atas tabel -- keluhan
    // "perpage pencarian double".
    if ($.fn.dataTable.isDataTable('#statusTable')) {
      return;
    }

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