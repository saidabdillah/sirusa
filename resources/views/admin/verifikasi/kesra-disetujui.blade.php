@extends('layouts.app')

@php
  // Arsip keputusan Kesra. Sumber datanya sama dengan antrean di
  // `kesra.blade.php` -- baris `pendaftar` -- tapi statusnya dikunci ke
  // `diterima`, jadi halaman ini murni daftar hasil, bukan daftar kerja.
  $routePrefix = 'admin.'.\App\Models\UserProfile::verifRoutePrefixes()['kesra'];
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Pendaftaran Disetujui</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item"><a href="{{ route($routePrefix.'.index') }}">Verifikasi Kesra</a></div>
      <div class="breadcrumb-item">Disetujui</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Pendaftaran yang Sudah Disetujui</h4>
            {{-- Header sengaja tanpa tombol (permintaan pengguna): Unduh Excel ada --}}
            {{-- di halaman antrean Kesra, dan navigasi antar halaman Kesra cukup --}}
            {{-- lewat breadcrumb dan dropdown sidebar. --}}
          </div>
          <div class="card-body">
            {{-- Baris `@empty` sengaja tidak dipakai: DataTables memetakan sel --}}
            {{-- berdasarkan posisi dan mengabaikan `colspan`, jadi satu baris --}}
            {{-- berisi satu sel membuat inisialisasinya melempar error. Pesan --}}
            {{-- kosongnya ditaruh di opsi `language.emptyTable`. --}}
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
<th>Diputuskan</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendaftaran as $applicant)
                  @php
                    $profile = $applicant->user?->profile;
                  @endphp
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $profile?->nama_lengkap ?? $applicant->user?->username ?? '-' }}</td>
                    <td>{{ $profile?->nim ?? '-' }}</td>
                    <td>{{ $applicant->prodi ?? $profile?->prodi?->nama ?? '-' }}</td>
                    {{-- `Kampus` punya kolom `nama_kampus`; menulis `kampus->nama`
                         selalu membalas null. --}}
                    <td>{{ $profile?->prodi?->fakultas?->kampus?->nama_kampus ?? '-' }}</td>
                    <td>{{ $applicant->beasiswa?->nama ?? '-' }}</td>
                    <td>
                      {{ $applicant->diputuskan_at?->format('d/m/Y H:i') ?? '-' }}
                      @if($applicant->diputuskanOleh)
                      <small class="text-muted d-block">oleh {{ $applicant->diputuskanOleh->username }}</small>
                      @endif
                    </td>
<td>
                      {{-- Hapus tidak membuang baris: statusnya dikembalikan ke
                           `verifikasi`, jadi pendaftaran muncul lagi di antrean
                           putusan dan slot kuotanya ikut terbuka kembali. --}}
                      <form action="{{ route($routePrefix.'.pendaftaran.hapus', [$applicant->user_id, $applicant->id]) }}" method="POST" data-ajax-form class="m-0">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                          data-confirm-title="Hapus Pendaftaran?"
                          data-confirm-text="Pendaftaran {{ $profile?->nama_lengkap ?? $applicant->user?->username ?? 'mahasiswa' }} pada Beasiswa {{ $applicant->beasiswa?->nama ?? '-' }} akan dikembalikan ke antrean putusan. Lanjutkan?"
                          data-confirm-icon="warning"
                          data-confirm-color="#e74c3c"
                          data-confirm-button="Ya, Hapus!">
                          <i class="fas fa-trash mr-1"></i> Hapus
                        </button>
                      </form>
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
        emptyTable: "Belum ada pendaftaran yang disetujui Kesra.",
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
