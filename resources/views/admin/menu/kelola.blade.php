@extends('layouts.app')

@section('content')
@php
  $sections = \App\Models\Menu::sections();
@endphp
<section class="section">
  <div class="section-header">
    <h1>Kelola Menu</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item">Kelola Menu</div>
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
    <div class="col-12 p-0">
      <div class="card">
        <div class="card-header">
          <h4>Daftar Menu Sidebar</h4>
          <div class="card-header-action ml-3">
            <a href="{{ route('admin.menu.index') }}" class="btn btn-info mr-1">
              <i class="fas fa-th-list"></i> Akses Menu
            </a>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-tambah-menu">
              <i class="fas fa-plus"></i> Tambah Menu
            </button>
          </div>
        </div>
        <div class="card-body">
          <div class="alert alert-primary">
            <i class="fas fa-info-circle mr-1"></i>
            Menu di bawah tersusun sesuai pohon sidebar. Menu baru otomatis digrant ke role <strong>Super Admin</strong>.
            Pemberian akses ke role lain dilakukan di halaman <strong>Akses Menu</strong>.
          </div>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Menu</th>
                  <th>Route</th>
                  <th>Scope</th>
                  <th>Section</th>
                  <th>Urutan</th>
                  <th class="text-center">Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($menus as $menu)
                <tr>
                  <td>
                    <i class="{{ $menu->icon }} text-primary mr-1"></i>
                    <strong>{{ $menu->label }}</strong>
                    @if($menu->wajib)<span class="badge badge-warning ml-1">wajib</span>@endif
                    @if(! $menu->aktif)<span class="badge badge-danger ml-1">nonaktif</span>@endif
                  </td>
                  <td><code>{{ $menu->route ?? '-' }}</code></td>
                  <td><code>{{ $menu->scope ?? '-' }}</code></td>
                  <td>{{ $menu->section }}</td>
                  <td>{{ $menu->urutan }}</td>
                  <td class="text-center">{{ $menu->aktif ? 'Aktif' : 'Nonaktif' }}</td>
                  <td class="text-nowrap">
                    <button type="button" class="btn btn-primary btn-sm mr-1" title="Ubah"
                      data-toggle="modal" data-target="#modal-ubah-menu-{{ $menu->id }}">
                      <i class="fas fa-edit"></i>
                    </button>
                    @if(! $menu->wajib)
                    <form action="{{ route('admin.menuKelola.hapus', $menu) }}" method="POST" class="d-inline-block align-middle btn-delete-form">
                      @csrf
                      @method('DELETE')
                      <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus"
                        data-confirm-title="Hapus Menu?"
                        data-confirm-text="Apakah Anda yakin ingin menghapus menu '{{ $menu->label }}'? Sub-menu tidak boleh ada."
                        data-confirm-button="Ya, Hapus!">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                    @endif
                  </td>
                </tr>
                @foreach($menu->children as $child)
                <tr class="table-light">
                  <td class="pl-5 text-muted">
                    <i class="{{ $child->icon ?? 'fas fa-angle-right' }} mr-2"></i>
                    {{ $child->label }}
                    @if(! $child->aktif)<span class="badge badge-danger ml-1">nonaktif</span>@endif
                  </td>
                  <td><code>{{ $child->route ?? '-' }}</code></td>
                  <td><code>{{ $child->scope ?? '-' }}</code></td>
                  <td>{{ $child->section }}</td>
                  <td>{{ $child->urutan }}</td>
                  <td class="text-center">{{ $child->aktif ? 'Aktif' : 'Nonaktif' }}</td>
                  <td class="text-nowrap">
                    <button type="button" class="btn btn-primary btn-sm mr-1" title="Ubah"
                      data-toggle="modal" data-target="#modal-ubah-menu-{{ $child->id }}">
                      <i class="fas fa-edit"></i>
                    </button>
                    @if(! $child->wajib)
                    <form action="{{ route('admin.menuKelola.hapus', $child) }}" method="POST" class="d-inline-block align-middle btn-delete-form">
                      @csrf
                      @method('DELETE')
                      <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus"
                        data-confirm-title="Hapus Menu?"
                        data-confirm-text="Apakah Anda yakin ingin menghapus menu '{{ $child->label }}'?"
                        data-confirm-button="Ya, Hapus!">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                    @endif
                  </td>
                </tr>
                @endforeach
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Modal Tambah & Ubah Menu (dirender di level body lewat @stack('modal')) --}}
  @push('modal')
    @include('admin.menu.modal-tambah', ['parents' => $parents, 'sections' => $sections])

    @foreach($menus as $menu)
      @include('admin.menu.modal-ubah', ['menu' => $menu, 'parents' => $parents, 'sections' => $sections])
      @foreach($menu->children as $child)
        @include('admin.menu.modal-ubah', ['menu' => $child, 'parents' => $parents, 'sections' => $sections])
      @endforeach
    @endforeach
  @endpush
</section>
@endsection

@push('script')
<script>
  $(document).ready(function() {
    const openModal = @json(old('modal_target'));
    if (openModal && document.getElementById(openModal)) {
      $('#' + openModal).modal('show');
    }

    const btnDelete = document.querySelectorAll('.btn-delete');
    btnDelete.forEach(function(button) {
      button.addEventListener('click', function() {
        button.closest('.btn-delete-form').submit();
      });
    });
  });
</script>
@endpush