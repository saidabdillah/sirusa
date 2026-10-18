@extends('layouts.app')

@php
  // Halaman kerja pengumuman hasil seleksi. Satu baris per beasiswa; tombol
  // "Umumkan" hanya hidup kalau semua pendaftar beasiswa itu sudah diputuskan.
  // Halaman ini SENGAJA terpisah dari "Keputusan" (arsip): mengumumkan adalah
  // pekerjaan tersendiri, bukan efek samping dari membaca daftar keputusan.
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Pengumuman Hasil Seleksi</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Pengumuman</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Daftar Beasiswa</h4>
          </div>
          <div class="card-body">
            {{-- DataTables memetakan sel berdasarkan posisi dan mengabaikan
                 `colspan`, jadi baris `@empty` tidak dipakai; pesan kosongnya
                 ditaruh di opsi `language.emptyTable`. --}}
            <div class="table-responsive">
              <table class="table table-striped" id="pengumumanTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Beasiswa</th>
                    <th>Kampus</th>
                    <th>Pendaftar</th>
                    <th>Diputuskan</th>
                    <th>Belum Diputuskan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($beasiswaRingkasan as $beasiswa)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $beasiswa->nama }}</td>
                    <td>{{ $beasiswa->kampus }}</td>
                    <td>{{ $beasiswa->total_pendaftar }}</td>
                    <td>{{ $beasiswa->total_diputuskan }}</td>
                    <td>
                      @if($beasiswa->total_menunggu > 0)
                        <span class="badge badge-warning">{{ $beasiswa->total_menunggu }}</span>
                      @else
                        <span class="badge badge-success">0</span>
                      @endif
                    </td>
                    <td>
                      @if($beasiswa->result_announced_at)
                        <span class="badge badge-success">
                          <i class="fas fa-bullhorn mr-1"></i>
                          Diumumkan {{ $beasiswa->result_announced_at->translatedFormat('d M Y H:i') }}
                        </span>
                      @elseif($beasiswa->total_menunggu > 0)
                        <span class="badge badge-secondary">Menunggu keputusan</span>
                      @elseif($beasiswa->total_diputuskan === 0)
                        <span class="badge badge-secondary">Belum ada keputusan</span>
                      @else
                        <span class="badge badge-info">Siap diumumkan</span>
                      @endif
                    </td>
                    <td>
                      @if($beasiswa->result_announced_at)
                        {{-- Pengumuman bisa dibatalkan: keputusan kembali
                             tersembunyi dari mahasiswa dan seluruh hasil
                             beasiswa ini bisa dinilai ulang. --}}
                        <form action="{{ route('admin.kesra.pengumuman.batalkan', $beasiswa) }}" method="POST" data-ajax-form class="m-0">
                          @csrf
                          @method('DELETE')
                          <button type="button" class="btn btn-sm btn-outline-warning btn-confirm-toggle"
                            data-confirm-title="Batalkan Pengumuman?"
                            data-confirm-text="Hasil seleksi Beasiswa {{ $beasiswa->nama }} akan ditarik kembali. Mahasiswa tidak lagi melihat hasilnya dan keputusan bisa diubah. Lanjutkan?"
                            data-confirm-icon="warning"
                            data-confirm-color="#ffa426"
                            data-confirm-button="Ya, Batalkan!">
                            <i class="fas fa-undo mr-1"></i> Batalkan Pengumuman
                          </button>
                        </form>
                      @elseif($beasiswa->total_menunggu > 0)
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                          title="Putuskan semua pendaftar dulu">
                          Umumkan
                        </button>
                      @elseif($beasiswa->total_diputuskan === 0)
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                          Umumkan
                        </button>
                      @else
                        <form action="{{ route('admin.kesra.pengumuman.umumkan', $beasiswa) }}" method="POST" data-ajax-form class="m-0">
                          @csrf
                          <button type="button" class="btn btn-sm btn-info btn-confirm-toggle"
                            data-confirm-title="Umumkan Hasil Seleksi?"
                            data-confirm-text="Hasil seleksi Beasiswa {{ $beasiswa->nama }} akan diumumkan sekaligus kepada {{ $beasiswa->total_diputuskan }} pendaftar. Lanjutkan?"
                            data-confirm-icon="question"
                            data-confirm-color="#3abaf4"
                            data-confirm-button="Ya, Umumkan!">
                            <i class="fas fa-bullhorn mr-1"></i> Umumkan
                          </button>
                        </form>
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
    if ($.fn.dataTable.isDataTable('#pengumumanTable')) {
      return;
    }

    $('#pengumumanTable').DataTable({
      order: [],
      processing: true,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        emptyTable: "Belum ada beasiswa dengan pendaftar.",
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
