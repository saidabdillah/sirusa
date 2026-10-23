@extends('layouts.app')

@php
  $stageLabel = $stage === 'kampus' ? 'Kampus' : 'Kesra';
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Verifikasi Semester — {{ $stageLabel }}</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Verifikasi Semester</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Paket Semester Penerima Beasiswa</h4>
            @if($bisaPilihTahap)
            <div class="card-header-action">
              {{-- Hanya super_admin: peran lain tahapnya sudah ditentukan dari
                   perannya sendiri, jadi pilihan ini tidak ada gunanya bagi mereka. --}}
              <a href="{{ route('admin.semester.index', ['tahap' => 'kampus']) }}"
                class="btn btn-sm {{ $stage === 'kampus' ? 'btn-primary' : 'btn-outline-primary' }}">Kampus</a>
              <a href="{{ route('admin.semester.index', ['tahap' => 'kesra']) }}"
                class="btn btn-sm {{ $stage === 'kesra' ? 'btn-primary' : 'btn-outline-primary' }}">Kesra</a>
            </div>
            @endif
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle mr-1"></i>
              @if($stage === 'kampus')
              Paket semester yang dokumennya sudah lengkap. Setelah Anda setujui, paket masuk antrean Kesra.
              @else
              Paket yang sudah disetujui Kampus. Keputusan Anda menutup semester itu, atau mencabut beasiswa bila mahasiswa tidak memperbaiki dokumennya.
              @endif
            </div>

            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Mahasiswa</th>
                    <th>Beasiswa</th>
                    <th>Semester</th>
                    <th>Status Kampus</th>
                    <th>Status Kesra</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($paket as $baris)
                  <tr>
                    <td>
                      <div class="font-weight-bold">{{ $baris->pendaftar?->user?->profile?->nama_lengkap ?? '-' }}</div>
                      <div class="text-muted small">{{ $baris->pendaftar?->user?->profile?->prodi?->nama ?? '-' }}</div>
                    </td>
                    <td>{{ $baris->pendaftar?->beasiswa?->nama ?? '-' }}</td>
                    <td class="font-weight-bold">{{ $baris->semester }}</td>
                    <td>
                      <span class="badge badge-{{ $baris->badgeKampus() }}">{{ $baris->labelKampus() }}</span>
                    </td>
                    <td>
                      <span class="badge badge-{{ $baris->badgeKesra() }}">{{ $baris->labelKesra() }}</span>
                    </td>
                    <td>
                      <a href="{{ route('admin.semester.lihat', $baris) }}"
                        class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted">Tidak ada paket semester yang menunggu.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            {{ $paket->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
