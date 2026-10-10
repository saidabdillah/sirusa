@extends('layouts.app')

@php
  // Catpil dan Kampus punya grant `admin.penerima` untuk MEMBACA daftar ini,
  // tapi hanya Kesra (dan super_admin) yang boleh mengembalikan penerima ke
  // antrean. Kolom Aksi + tombol Hapus hanya dirender untuk pengambil
  // keputusan, bukan pembaca daftar.
  $bisaTarik = auth()->user()->hasRole(['kesra', 'super_admin']);
  // Unduh/Cetak membawa filter yang sedang dibuka, jadi hasilnya persis
  // daftar di layar (pola yang sama dipakai halaman Status Verifikasi).
  $queryString = request()->query();
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Penerima Beasiswa</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Penerima Beasiswa</div>
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
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Daftar Penerima Beasiswa</h4>
            <div class="card-header-action">
              @if(auth()->user()->hasMenuAccess('admin.penerima.export'))
              <a href="{{ route('admin.penerima.export', $queryString) }}" class="btn btn-outline-success">
                <i class="fas fa-file-excel mr-1"></i> Unduh Excel
              </a>
              @endif
              @if(auth()->user()->hasMenuAccess('admin.penerima.cetak'))
              <a href="{{ route('admin.penerima.cetak', $queryString) }}" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-print mr-1"></i> Cetak
              </a>
              @endif
            </div>
          </div>
          <div class="card-body">
            @if($bisaTarik)
            <div class="alert alert-primary">
              <i class="fas fa-info-circle mr-1"></i>
              Daftar ini hanya memuat pendaftar yang <strong>disetujui (diterima)</strong> Kesra.
              Tombol <strong>Hapus</strong> tidak menghapus data: status pendaftaran dikembalikan ke
              <strong>Verifikasi</strong> supaya masuk lagi ke antrean keputusan Kesra.
            </div>
            @else
            <div class="alert alert-primary">
              <i class="fas fa-info-circle mr-1"></i>
              Daftar ini hanya memuat pendaftar yang <strong>disetujui (diterima)</strong> Kesra.
            </div>
            @endif

            <form method="GET" action="{{ route('admin.penerima.index') }}" class="mb-3">
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
                <div class="col-md-3 mb-2 mb-md-0">
                  <label for="filter-beasiswa">Beasiswa</label>
                  <select class="form-control" name="beasiswa_id" id="filter-beasiswa">
                    <option value="" {{ $beasiswaId === null ? 'selected' : '' }}>-- Semua Beasiswa --</option>
                    @foreach($beasiswaOptions as $beasiswa)
                    <option value="{{ $beasiswa->id }}" {{ $beasiswaId === $beasiswa->id ? 'selected' : '' }}>{{ $beasiswa->nama }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="form-row align-items-end mt-2">
                <div class="col-md-3 mb-2 mb-md-0">
                  <button type="submit" class="btn btn-primary btn-block" data-loading-text="Menerapkan filter...">
                    <i class="fas fa-filter mr-1"></i> Terapkan
                  </button>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                  <a href="{{ route('admin.penerima.index') }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-redo"></i> Reset
                  </a>
                </div>
              </div>
            </form>

            <div class="table-responsive">
              <table class="table table-striped" id="penerimaTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Kampus</th>
                    <th>Fakultas</th>
                    <th>Program Studi</th>
                    <th>Beasiswa</th>
                    <th>Tanggal Daftar</th>
                    <th>Status</th>
                    @if($bisaTarik)
                    <th>Aksi</th>
                    @endif
                  </tr>
                </thead>
                <tbody>
                  @foreach($penerima as $applicant)
                  @php
                    $profile = $applicant->user?->profile;
                  @endphp
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $profile?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $profile?->nim ?? '-' }}</td>
                    {{-- `Kampus` punya kolom `nama_kampus`; menulis `kampus->nama`
                         selalu membalas null. --}}
                    <td>{{ $profile?->prodi?->fakultas?->kampus?->nama_kampus ?? '-' }}</td>
                    <td>{{ $profile?->prodi?->fakultas?->nama ?? '-' }}</td>
                    <td>{{ $profile?->prodi?->nama ?? '-' }}</td>
                    <td>{{ $applicant->beasiswa?->nama ?? '-' }}</td>
                    <td>{{ $applicant->created_at?->translatedFormat('d M Y') ?? '-' }}</td>
                    <td><span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span></td>
                    @if($bisaTarik)
                    <td>
                      <form action="{{ route('admin.penerima.tarik', $applicant) }}" method="POST" data-ajax-form>
                        @csrf
                        @method('PUT')
                        <button type="submit"
                          class="btn btn-sm btn-outline-danger btn-delete"
                          data-confirm-title="Hapus dari Penerima?"
                          data-confirm-text="Status pendaftaran {{ $profile?->nama_lengkap ?? 'mahasiswa ini' }} akan dikembalikan ke Verifikasi dan masuk lagi ke antrean keputusan Kesra."
                          data-confirm-button="Ya, Kembalikan!"
                          data-loading-text="Memproses...">
                          <i class="fas fa-trash mr-1"></i> Hapus
                        </button>
                      </form>
                    </td>
                    @endif
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
    $('#penerimaTable').DataTable({
      order: [],
      processing: true,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        emptyTable: "Belum ada penerima beasiswa yang disetujui.",
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