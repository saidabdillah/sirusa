@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
      <h1>Template Surat Permohonan</h1>
      <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Template</div>
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
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header">
              <h4>Unggah Template</h4>
            </div>
            <div class="card-body">
              <p class="text-muted">
                Unggah file template surat permohonan (Word/PDF). File ini akan diunduh oleh pendaftar sebelum diisi dan diupload kembali.
              </p>

              @if($templateExists)
                <div class="alert alert-success">
                  <i class="fas fa-check-circle"></i> Template saat ini sudah tersedia.
                </div>
              @else
                <div class="alert alert-warning">
                  <i class="fas fa-exclamation-triangle"></i> Belum ada template yang diunggah.
                </div>
              @endif

              <form action="{{ route('admin.template.perbarui') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-group">
                  <label for="template">Pilih File Template</label>
                  <input type="file" class="form-control @error('template') is-invalid @enderror" id="template" name="template" accept=".docx,.doc,.pdf">
                  @error('template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <small class="text-muted">Format: DOCX, DOC, atau PDF. Maksimal 10MB.</small>
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-upload"></i> Unggah Template
                </button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-header">
              <h4>Informasi</h4>
            </div>
            <div class="card-body">
              <div class="alert alert-info">
                <strong>Cara Kerja:</strong>
                <ol class="mt-2 mb-0">
                  <li>Admin mengunggah template surat permohonan di halaman ini</li>
                  <li>Pendaftar mengunduh template yang sudah diunggah</li>
                  <li>Pendaftar mengisi data pada template</li>
                  <li>Pendaftar menandatangani dan mengscan/memfoto</li>
                  <li>Pendaftar mengunggah file yang sudah diisi ke form pendaftaran</li>
                </ol>
              </div>

              @if($templateExists)
                <div class="mt-3 d-flex gap-2">
                  <a href="{{ route('preview.application-letter') }}" class="btn btn-info" target="_blank">
                    <i class="fas fa-eye"></i> Preview Template
                  </a>
                  <a href="{{ route('download.application-letter') }}" class="btn btn-secondary" target="_blank">
                    <i class="fas fa-download"></i> Download Template
                  </a>
                  <form action="{{ route('admin.template.hapus') }}" method="POST" class="d-inline btn-delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-danger btn-delete">
                      <i class="fas fa-trash"></i> Hapus Template
                    </button>
                  </form>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
</section>
@endsection
