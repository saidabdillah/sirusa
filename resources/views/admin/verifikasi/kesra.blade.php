@extends('layouts.app')

@php
  // Kesra punya bentuk data yang berbeda dari Catpil dan Kampus, jadi halaman ini
  // terpisah dari `index.blade.php`: yang diantrekan bukan profil melainkan baris
  // `pendaftar`, dan yang diputuskan adalah pendaftaran bukan tahap verifikasi
  // profil. Kolom, aksi, dan filter status di bawah semuanya mengikuti itu.
  $routePrefix = 'admin.'.\App\Models\UserProfile::verifRoutePrefixes()[$stage];

  // Label dan warna status diambil dari peta di `Applicant`, bukan ditulis ulang,
  // supaya istilah status tidak pernah berbeda antara daftar ini, badge di baris,
  // dan halaman mahasiswa.
  $statusOptions = \App\Models\Applicant::STATUS_LABELS;
  $semuaStatus = \App\Models\Applicant::FILTER_ALL;
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Verifikasi Kesra</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Verifikasi Kesra</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
<h4>Daftar Pendaftaran</h4>
            {{-- Unduh Excel di sini mengikuti pola Catpil/Kampus: unduhan berisi --}}
            {{-- status yang sedang dibuka (`request()->only('filter')`). Tanpa --}}
            {{-- filter berarti semua status (permintaan pengguna; dulu default --}}
            {{-- antrean `verifikasi`). Tombol "Disetujui" tetap tidak ada: arsip --}}
            {{-- keputusan dibuka lewat dropdown sidebar Kesra (menu "Keputusan"). --}}
            <div class="card-header-action">
              @if(auth()->user()->hasMenuAccess($routePrefix.'.export'))
              <a href="{{ route($routePrefix.'.export', request()->only('filter')) }}" class="btn btn-outline-success">
                <i class="fas fa-file-excel mr-1"></i> Unduh Excel
              </a>
              @endif
            </div>
          </div>
          <div class="card-body">
            <form method="GET" action="{{ route($routePrefix.'.index') }}" class="mb-3">
              <div class="form-row align-items-end">
                <div class="col-md-4 mb-2 mb-md-0">
                  <label for="filter">Status Pendaftaran</label>
                  <select class="form-control" name="filter" id="filter">
{{-- "Semua status" harus punya nilai sendiri, bukan `value=""`.
                       `ConvertEmptyStringsToNull` membuat `?filter=` menjadi
                       `null`, yang artinya "tanpa filter" (= semua status). --}}
                    <option value="{{ $semuaStatus }}" {{ $filter === null ? 'selected' : '' }}>-- Semua Status --</option>
                    @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ $filter === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
{{-- Filter cuma satu, jadi tombol sejajar dengan select (bukan baris
                     terpisah bawah) supaya form tidak memakan tinggi. --}}
                <div class="col-auto mb-2 mb-md-0">
                  <button type="submit" class="btn btn-primary" data-loading-text="Menerapkan filter...">
                    <i class="fas fa-filter mr-1"></i> Terapkan
                  </button>
                </div>
{{-- Reset selalu ada, sama seperti di daftar pendaftar, dan tidak
                     bergantung pada filter aktif. Default halaman kini semua
                     status, jadi menyembunyikan tombol berdasarkan nilai filter
                     akan membuat tombol hilang tepat saat paling dibutuhkan. --}}
                <div class="col-auto mb-2 mb-md-0">
                  <a href="{{ route($routePrefix.'.index') }}" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                  </a>
                </div>
              </div>
            </form>
            <div class="table-responsive">
              <table class="table table-striped" id="verifikasiTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Program Studi</th>
                    <th>Kampus</th>
                    <th>Beasiswa</th>
                    <th>IPK</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendaftaran as $applicant)
                  @php
                    $profile = $applicant->user?->profile;
                    $sudahDiputuskan = $applicant->isDecided();
                  @endphp
                  <tr class="{{ $sudahDiputuskan ? 'table-active' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $profile?->nama_lengkap ?? $applicant->user?->username ?? '-' }}</td>
                    <td>{{ $profile?->nim ?? '-' }}</td>
                    <td>{{ $applicant->prodi ?? $profile?->prodi?->nama ?? '-' }}</td>
                    {{-- `Kampus` punya kolom `nama_kampus`; menulis `kampus->nama`
                         selalu membalas null. --}}
                    <td>{{ $profile?->prodi?->fakultas?->kampus?->nama_kampus ?? '-' }}</td>
                    <td>{{ $applicant->beasiswa?->nama ?? '-' }}</td>
                    <td>{{ $applicant->ipk ?? $profile?->ipk ?? '-' }}</td>
<td>
                      <span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span>
                    </td>
                    <td>
                      {{-- Pendaftaran `dibatalkan` bukan keputusan Kesra dan
                           formnya tidak dibuka di halaman detail, jadi tidak ada
                           tombol yang diarahkan ke sana. --}}
                      @if(! $applicant->isCancelled())
                      <a href="{{ route($routePrefix.'.lihat', $applicant->user_id) }}"
                        class="btn btn-sm {{ $sudahDiputuskan ? 'btn-outline-primary' : 'btn-info' }}">
                        <i class="fas {{ $sudahDiputuskan ? 'fa-edit' : 'fa-eye' }}"></i>
                        {{ $sudahDiputuskan ? 'Ubah Keputusan' : 'Putuskan' }}
                      </a>
                      @else
                      <span class="text-muted small">Dibatalkan mahasiswa</span>
                      @endif
                    </td>
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
    $('#verifikasiTable').DataTable({
      order: [],
      processing: true,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        emptyTable: "Tidak ada pendaftaran dengan status ini.",
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
