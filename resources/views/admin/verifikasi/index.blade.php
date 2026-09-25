@extends('layouts.app')

@php
  $routePrefix = $stage === 'capil' ? 'admin.capil' : ($stage === 'kampus' ? 'admin.kampusverif' : 'admin.kesra');
  $stageLabel = ['capil' => 'Capil', 'kampus' => 'Kampus', 'kesra' => 'Kesra'][$stage] ?? ucfirst($stage);
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Verifikasi Profil - {{ $stageLabel }}</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Verifikasi {{ $stageLabel }}</div>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
  @endif

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Daftar Profil Menunggu Verifikasi {{ $stageLabel }}</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-primary">
              <i class="fas fa-info-circle mr-1"></i>
              Verifikasi berjalan berurutan: <strong>Capil &rarr; Kampus &rarr; Kesra</strong>.
              Anda hanya dapat memverifikasi profil yang seluruh tahap sebelumnya sudah disetujui
              dan tahap Anda sendiri belum disetujui.
            </div>
            <div class="table-responsive">
              <table class="table table-striped" id="verifikasiTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($users as $user)
                  @php $p = $user->profile; @endphp
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $p->nama_lengkap ?? '-' }}</td>
                    <td>{{ $p->nik ?? '-' }}</td>
                    <td>
                      @php $status = $p->{'verif_'.$stage}; @endphp
                      @if($status === 'revisi')
                      <span class="badge badge-warning">Perlu Perbaikan</span>
                      @elseif($status === 'setuju')
                      <span class="badge badge-success">Disetujui</span>
                      @elseif($status === 'tolak')
                      <span class="badge badge-danger">Ditolak</span>
                      @else
                      <span class="badge badge-warning">Menunggu</span>
                      @endif
                    </td>
                    <td>
                      <a href="{{ route($routePrefix.'.lihat', $user) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> Verifikasi
                      </a>
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted">Tidak ada profil yang menunggu verifikasi {{ $stageLabel }}.</td>
                  </tr>
                  @endforelse
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
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
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