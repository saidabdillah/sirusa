@extends('layouts.app')

@php
$routePrefix = $stage === 'capil' ? 'admin.capil' : ($stage === 'kampus' ? 'admin.kampusverif' : 'admin.kesra');
$stageLabels = ['capil' => 'Capil', 'kampus' => 'Kampus', 'kesra' => 'Kesra'];
$stageLabel = $stageLabels[$stage] ?? ucfirst($stage);

// Kolom peruntukan per tahap verifikasi. Capil fokus data kependudukan, Kampus
// fokus data mahasiswa, Kesra (tahap akhir) menampilkan seluruh data.
$stageColumns = [
'capil' => [
['label' => 'NIK', 'value' => fn ($p) => $p->nik ?? '-'],
['label' => 'No. Kartu Keluarga', 'value' => fn ($p) => $p->no_kk ?? '-'],
['label' => 'Desil', 'value' => fn ($p) => $p->desil ? 'Desil '.$p->desil : '-'],
],
'kampus' => [
['label' => 'NIM', 'value' => fn ($p) => $p->nim ?? '-'],
['label' => 'Program Studi', 'value' => fn ($p) => $p->prodi?->nama ?? '-'],
['label' => 'IPK', 'value' => fn ($p) => $p->ipk ?? '-'],
['label' => 'NIK', 'value' => fn ($p) => $p->nik ?? '-'],
['label' => 'Tempat, Tanggal Lahir', 'value' => fn ($p) => ($p->tempat_lahir ?? '-').', '.(! blank($p->tanggal_lahir) ? $p->tanggal_lahir->translatedFormat('d M Y') : '-')],
['label' => 'Jenis Kelamin', 'value' => fn ($p) => $p->jenis_kelamin ?? '-'],
['label' => 'Agama', 'value' => fn ($p) => $p->agama ?? '-'],
],
'kesra' => [
['label' => 'NIK', 'value' => fn ($p) => $p->nik ?? '-'],
['label' => 'No. Kartu Keluarga', 'value' => fn ($p) => $p->no_kk ?? '-'],
['label' => 'Desil', 'value' => fn ($p) => $p->desil ? 'Desil '.$p->desil : '-'],
['label' => 'NIM', 'value' => fn ($p) => $p->nim ?? '-'],
['label' => 'Program Studi', 'value' => fn ($p) => $p->prodi?->nama ?? '-'],
['label' => 'Fakultas', 'value' => fn ($p) => $p->prodi?->fakultas?->nama ?? '-'],
['label' => 'Kampus', 'value' => fn ($p) => $p->prodi?->fakultas?->kampus?->nama_kampus ?? '-'],
['label' => 'IPK', 'value' => fn ($p) => $p->ipk ?? '-'],
['label' => 'Semester', 'value' => fn ($p) => $p->semester ?? '-'],
['label' => 'UKT/SPP', 'value' => fn ($p) => $p->ukt ? 'Rp '.number_format($p->ukt, 0, ',', '.') : '-'],
],
][$stage] ?? [];

$filterOptions = [
'menunggu' => 'Menunggu',
'revisi' => 'Perlu Perbaikan',
'setuju' => 'Disetujui',
'tolak' => 'Ditolak',
];
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
            <h4>Daftar Verifikasi Profil {{ $stageLabel }}</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-primary">
              <i class="fas fa-info-circle mr-1"></i>
              Verifikasi berjalan berurutan: <strong>Capil &rarr; Kampus &rarr; Kesra</strong>.
              Anda hanya dapat memverifikasi profil yang seluruh tahap sebelumnya sudah disetujui.
              Profil yang sudah Anda putuskan tetap ditampilkan agar keputusannya bisa diubah kembali.
            </div>
            <form method="GET" action="{{ route($routePrefix.'.index') }}" class="form-row align-items-end mb-3">
              <div class="col-md-4 mb-2 mb-md-0">
                <label for="filter">Status Keputusan</label>
                <select class="form-control" name="filter" id="filter">
                  <option value="">-- Semua Status --</option>
                  @foreach($filterOptions as $value => $label)
                  <option value="{{ $value }}" {{ $filter === $value ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2 mb-2 mb-md-0">
                <button type="submit" class="btn btn-primary btn-block">
                  <i class="fas fa-filter mr-1"></i> Terapkan
                </button>
              </div>
              @if($filter)
              <div class="col-md-2 mb-2 mb-md-0">
                <a href="{{ route($routePrefix.'.index') }}" class="btn btn-outline-secondary btn-block">Reset</a>
              </div>
              @endif
            </form>
            <div class="table-responsive">
              <table class="table table-striped" id="verifikasiTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    @foreach($stageColumns as $column)
                    <th>{{ $column['label'] }}</th>
                    @endforeach
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($users as $user)
                  @php
                    $p = $user->profile;
                    $decision = $p->verifStageDecision($stage);
                  @endphp
                  <tr class="{{ $decision['decided'] ? 'table-active' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $p->nama_lengkap ?? '-' }}</td>
                    @foreach($stageColumns as $column)
                    <td>{{ $column['value']($p) }}</td>
                    @endforeach
                    <td>
                      <span class="badge badge-{{ $decision['badge'] }}">{{ $decision['label'] }}</span>
                      @if($p->{'catatan_'.$stage})
                      <small class="text-muted d-block">{{ Str::limit($p->{'catatan_'.$stage}, 40) }}</small>
                      @endif
                    </td>
                    <td>
                      <a href="{{ route($routePrefix.'.lihat', $user) }}"
                        class="btn btn-sm {{ $decision['decided'] ? 'btn-outline-primary' : 'btn-info' }}">
                        <i class="fas {{ $decision['decided'] ? 'fa-edit' : 'fa-eye' }}"></i>
                        {{ $decision['decided'] ? 'Ubah Keputusan' : 'Verifikasi' }}
                      </a>
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
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        emptyTable: "Tidak ada profil dalam daftar verifikasi {{ $stageLabel }}.",
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