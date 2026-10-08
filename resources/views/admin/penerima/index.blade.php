@extends('layouts.app')

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
              <a href="{{ route('admin.penerima.export') }}" class="btn btn-outline-success">
                <i class="fas fa-file-excel mr-1"></i> Unduh Excel
              </a>
              @endif
              @if(auth()->user()->hasMenuAccess('admin.penerima.cetak'))
              <a href="{{ route('admin.penerima.cetak') }}" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-print mr-1"></i> Cetak
              </a>
              @endif
            </div>
          </div>
          <div class="card-body">
            <div class="alert alert-primary">
              <i class="fas fa-info-circle mr-1"></i>
              Daftar ini hanya memuat pendaftar yang <strong>disetujui (diterima)</strong> Kesra.
              Tombol <strong>Hapus</strong> tidak menghapus data: status pendaftaran dikembalikan ke
              <strong>Verifikasi</strong> supaya masuk lagi ke antrean keputusan Kesra.
            </div>
            <div class="table-responsive">
              <table class="table table-striped" id="penerimaTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>NIM</th>
                    <th>Beasiswa</th>
                    <th>Program Studi</th>
                    <th>Kampus</th>
                    <th>Tanggal Daftar</th>
                    <th>Status</th>
                    @if(auth()->user()->hasMenuAccess('admin.penerima.index'))
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
                    <td>{{ $profile?->nik ?? '-' }}</td>
                    <td>{{ $profile?->nim ?? '-' }}</td>
                    <td>{{ $applicant->beasiswa?->nama ?? '-' }}</td>
                    <td>{{ $profile?->prodi?->nama ?? '-' }}</td>
                    <td>{{ $profile?->prodi?->fakultas?->kampus?->nama_kampus ?? '-' }}</td>
                    <td>{{ $applicant->created_at?->translatedFormat('d M Y') ?? '-' }}</td>
                    <td><span class="badge badge-{{ $applicant->statusBadge() }}">{{ $applicant->statusLabel() }}</span></td>
                    @if(auth()->user()->hasMenuAccess('admin.penerima.index'))
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
