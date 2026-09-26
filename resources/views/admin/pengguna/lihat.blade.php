@extends('layouts.app')

@php
  $roleName = $user->getRoleNames()->first();
  $roleLabel = $roleLabels[$roleName] ?? $roleName;
  $profile = $user->profile;
  $displayName = $profile?->nama_lengkap ?: $user->username;
  // Target super_admin tidak punya aksi penyuntingan sama sekali, sama seperti
  // di tabel pengguna — meski secara teknis `PenggunaController::edit()` hanya
  // menolak bila pemanggilnya bukan super_admin.
  $canEdit = auth()->user()->canManageUsers() && $roleName !== 'super_admin';
@endphp

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Detail Pengguna</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.pengguna.index') }}">Pengguna</a></div>
      <div class="breadcrumb-item">{{ $displayName }}</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-lg-8">
        @if($profile)
        @include('partials.profil-detail', ['profile' => $profile, 'stage' => 'diri'])
        @else
        <div class="card">
          <div class="card-header">
            <h4>Profil</h4>
          </div>
          <div class="card-body">
            <div class="text-muted mb-0">
              <i class="fas fa-info-circle"></i> Akun {{ $roleLabel }} tidak membuat data profil, jadi tidak ada data pribadi untuk ditampilkan.
            </div>
          </div>
        </div>
        @endif
      </div>

      <div class="col-lg-4 sticky-sidebar">
        <div class="card">
          <div class="card-header">
            <h4>Informasi Akun</h4>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <strong>Username:</strong><br>
              {{ $user->username }}
            </div>
            <div class="mb-2">
              <strong>Email:</strong><br>
              {{ $user->email ?: '-' }}
            </div>
            <div class="mb-2">
              <strong>Role:</strong><br>
              <span class="badge {{ $roleName === 'super_admin' ? 'badge-danger' : 'badge-info' }}">{{ $roleLabel }}</span>
            </div>
            <div class="mb-2">
              <strong>Status:</strong><br>
              @if($user->status === 'aktif')
              <span class="badge badge-success">Aktif</span>
              @else
              <span class="badge badge-secondary">Nonaktif</span>
              @endif
            </div>
            @if($user->kampus)
            <div class="mb-2">
              <strong>Kampus:</strong><br>
              {{ $user->kampus->nama_kampus }}
            </div>
            @endif
            <div class="mb-2">
              <strong>Terdaftar:</strong><br>
              {{ $user->created_at->translatedFormat('d F Y') }}
            </div>
          </div>
          @if($canEdit)
          <div class="card-footer text-right">
            <a href="{{ route('admin.pengguna.ubah', $user) }}" class="btn btn-primary">
              <i class="fas fa-user-tag mr-1"></i> Ubah
            </a>
          </div>
          @endif
        </div>

        @if($profile)
        <div class="card">
          <div class="card-header">
            <h4>Status Verifikasi</h4>
          </div>
          <div class="card-body">
            @foreach($profile->verifStageLabels() as $s => $label)
            @php
              $verifStatus = $profile->{'verif_'.$s};
              $badge = match ($verifStatus) { 'setuju' => 'success', 'revisi' => 'danger', 'tolak' => 'danger', default => 'warning' };
              $text = match ($verifStatus) { 'setuju' => 'Disetujui', 'revisi' => 'Perlu Perbaikan', 'tolak' => 'Ditolak', default => 'Menunggu' };
            @endphp
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>{{ $label }}</span>
              <span class="badge badge-{{ $badge }}">{{ $text }}</span>
            </div>
            @endforeach
            @if($profile->verifStatus() === 'terverifikasi')
            <div class="alert alert-success mb-0 mt-2"><i class="fas fa-check-circle"></i> Profil telah terverifikasi lengkap.</div>
            @endif
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</section>
@endsection
