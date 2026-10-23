@extends('layouts.app')

@php
  $stageLabel = $stage === 'kampus' ? 'Kampus' : 'Kesra';
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Verifikasi Semester {{ $paket->semester }}</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active">
        <a href="{{ route('admin.semester.index') }}">Verifikasi Semester</a>
      </div>
      <div class="breadcrumb-item">Semester {{ $paket->semester }}</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-lg-8">
        {{-- Data mahasiswa: hanya ringkasan, karena fase seleksi sudah selesai.
             Yang diperiksa di sini paket akademik semester itu. --}}
        <div class="card">
          <div class="card-header">
            <h4>Data Mahasiswa</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <div class="mb-3">
                  <div class="text-muted small">Nama Lengkap</div>
                  <div class="font-weight-bold">{{ $profile?->nama_lengkap ?? '-' }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3">
                  <div class="text-muted small">NIM</div>
                  <div class="font-weight-bold">{{ $profile?->nim ?? '-' }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3">
                  <div class="text-muted small">Program Studi</div>
                  <div class="font-weight-bold">{{ $profile?->prodi?->nama ?? '-' }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3">
                  <div class="text-muted small">Fakultas</div>
                  <div class="font-weight-bold">{{ $profile?->prodi?->fakultas?->nama ?? '-' }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3">
                  <div class="text-muted small">Semester Aktif</div>
                  <div class="font-weight-bold">{{ $profile?->semester ?? '-' }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3">
                  <div class="text-muted small">Beasiswa</div>
                  <div class="font-weight-bold">{{ $pendaftar->beasiswa?->nama ?? '-' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Paket akademik semester ini saja. --}}
        <div class="card">
          <div class="card-header">
            <h4>Dokumen Semester {{ $paket->semester }}</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Dokumen</th>
                    <th>Berkas</th>
                    <th>Nilai</th>
                  </tr>
                </thead>
                <tbody>
                  @php
                    $dokumen = $profile?->dokumenAkademik
                        ?->where('semester', $paket->semester)
                        ->keyBy('jenis') ?? collect();
                  @endphp
                  <tr>
                    <td class="font-weight-bold">KRS</td>
                    <td>
                      @if($dokumen->get('krs')?->file)
                      <a href="{{ route('dokumen.show', $dokumen->get('krs')->file) }}" target="_blank"
                        rel="noopener noreferrer" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                      <span class="small text-muted ml-1">{{ basename($dokumen->get('krs')->file) }}</span>
                      @else
                      <span class="text-muted">Berkas tidak ada</span>
                      @endif
                    </td>
                    <td><span class="text-muted">&mdash;</span></td>
                  </tr>
                  <tr>
                    <td class="font-weight-bold">KHS</td>
                    <td>
                      @if($dokumen->get('khs')?->file)
                      <a href="{{ route('dokumen.show', $dokumen->get('khs')->file) }}" target="_blank"
                        rel="noopener noreferrer" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Lihat
                      </a>
                      <span class="small text-muted ml-1">{{ basename($dokumen->get('khs')->file) }}</span>
                      @else
                      <span class="text-muted">Berkas tidak ada</span>
                      @endif
                    </td>
                    <td class="font-weight-bold">{{ $dokumen->get('khs')?->nilai ?? '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card">
          <div class="card-header">
            <h4>Keputusan {{ $stage === 'kampus' ? 'Kampus' : 'Kesra' }}</h4>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <div class="text-muted small">Status sekarang</div>
              <div>
                <span class="badge badge-{{ $paket->badgeKampus() }}">{{ $paket->labelKampus() }}</span>
                <span class="badge badge-{{ $paket->badgeKesra() }}">{{ $paket->labelKesra() }}</span>
              </div>
            </div>

            @if($paket->catatan_kampus)
            <div class="alert alert-light mb-3">
              <div class="text-muted small">Catatan Kampus</div>
              {{ $paket->catatan_kampus }}
            </div>
            @endif

            @if($paket->catatan_kesra)
            <div class="alert alert-light mb-3">
              <div class="text-muted small">Catatan Kesra</div>
              {{ $paket->catatan_kesra }}
            </div>
            @endif

            <form action="{{ route('admin.semester.verifikasi', $paket) }}"
              method="POST" data-ajax-form>
              @csrf
              @method('PUT')

              <div class="form-group">
                <label for="status">Keputusan <span class="text-danger">*</span></label>
                <select class="form-control @error('status') is-invalid @enderror" name="status" id="status">
                  <option value="">&mdash; Pilih &mdash;</option>
                  @if($stage === 'kampus')
                  <option value="setuju">Setujui</option>
                  <option value="revisi">Minta Perbaikan</option>
                  @else
                  <option value="setuju">Selesai</option>
                  <option value="tahan">Tahan Pencairan</option>
                  <option value="dicabut">Cabut Beasiswa</option>
                  @endif
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <label for="catatan">Catatan</label>
                <textarea class="form-control @error('catatan') is-invalid @enderror" name="catatan" id="catatan" rows="4"
                  placeholder="Wajib diisi untuk Minta Perbaikan, Tahan, atau Cabut">{{ old('catatan') }}</textarea>
                @error('catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <button type="submit" class="btn btn-primary btn-block" data-loading-text="Menyimpan keputusan...">
                <i class="fas fa-save"></i> Simpan Keputusan
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
