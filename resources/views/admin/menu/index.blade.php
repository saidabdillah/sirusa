@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Akses Menu &amp; Halaman</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('admin.role.index') }}">Role</a></div>
      <div class="breadcrumb-item">Akses Menu</div>
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
          <h4>Centang menu untuk setiap role</h4>
          <div class="card-header-action ml-3">
            <a href="{{ route('admin.menuKelola.index') }}" class="btn btn-info">
              <i class="fas fa-sitemap"></i> Kelola Menu
            </a>
          </div>
        </div>
        <div class="card-body">
          <div class="alert alert-primary">
            <i class="fas fa-info-circle mr-1"></i>
            Mencentang sebuah menu berarti menu tersebut <strong>tampil di sidebar</strong> sekaligus
            <strong>memberikan akses ke halaman modul</strong> terkait untuk role itu. Role yang tidak memiliki menu
            tidak dapat membuka halaman terkait (403). Dasbor bersifat wajib dan selalu aktif. Menu disusun sebagai
            pohon — klik ikon
            <i class="fas fa-chevron-down"></i>
            pada baris menu utama untuk membuka/tutup sub-menunya. Untuk menambah/mengubah/menghapus menu, buka
            <strong>Kelola Menu</strong>.
          </div>
          <form action="{{ route('admin.menu.grants') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="thead-light">
                  <tr>
                    <th class="border-right-0" style="min-width: 240px;">Menu</th>
                    @foreach($roles as $role)
                    <th class="border-right-0 text-center">
                      <span class="badge {{ $role->name === 'super_admin' ? 'badge-danger' : 'badge-info' }}">
                        {{ $role->name === 'super_admin' ? 'Super Admin' : ucfirst(str_replace('admin_', '', $role->name)) }}
                      </span>
                    </th>
                    @endforeach
                  </tr>
                </thead>
                @foreach($menus as $menu)
                <tbody>
                  <tr class="tree-parent-{{ $menu->id }}" data-tree-parent="{{ $menu->id }}">
                    <td>
                      <button type="button"
                        class="btn btn-link btn-sm p-0 mr-1 tree-toggle {{ $menu->children->isEmpty() ? 'invisible' : '' }}"
                        data-tree-toggle="{{ $menu->id }}" title="Buka/Tutup sub-menu">
                        <i class="fas fa-chevron-down"></i>
                      </button>
                      <i class="{{ $menu->icon }} text-primary mr-1"></i>
                      <strong>{{ $menu->label }}</strong>
                      @if($menu->wajib)<span class="badge badge-warning ml-1">wajib</span>@endif
                      @if(! $menu->aktif)<span class="badge badge-danger ml-1">nonaktif</span>@endif
                      <span class="text-muted small ml-1">({{ $menu->section }})</span>
                    </td>
                    @foreach($roles as $role)
                    <td class="text-center">
                      <div class="custom-control custom-checkbox d-inline-block">
                        <input type="checkbox"
                          class="custom-control-input {{ $menu->wajib ? 'menu-wajib' : '' }}"
                          id="menu-{{ $menu->id }}-role-{{ $role->id }}"
                          name="grants[{{ $role->id }}][]"
                          value="{{ $menu->id }}"
                          {{ in_array($menu->id, $grants[$role->id] ?? [], true) || $menu->wajib ? 'checked' : '' }}
                          {{ $menu->wajib ? 'disabled' : '' }}>
                        <label class="custom-control-label" for="menu-{{ $menu->id }}-role-{{ $role->id }}"></label>
                      </div>
                    </td>
                    @endforeach
                  </tr>
                </tbody>
                @foreach($menu->children as $child)
                <tbody class="tree-child-{{ $menu->id }}" data-tree-child-of="{{ $menu->id }}">
                  <tr>
                    <td class="pl-5 text-muted">
                      <i class="{{ $child->icon ?? 'fas fa-angle-right' }} mr-2"></i>
                      {{ $child->label }}
                      @if(! $child->aktif)<span class="badge badge-danger ml-1">nonaktif</span>@endif
                    </td>
                    @foreach($roles as $role)
                    <td class="text-center">
                      <div class="custom-control custom-checkbox d-inline-block">
                        <input type="checkbox"
                          class="custom-control-input"
                          id="menu-{{ $child->id }}-role-{{ $role->id }}"
                          name="grants[{{ $role->id }}][]"
                          value="{{ $child->id }}"
                          {{ in_array($child->id, $grants[$role->id] ?? [], true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="menu-{{ $child->id }}-role-{{ $role->id }}"></label>
                      </div>
                    </td>
                    @endforeach
                  </tr>
                </tbody>
                @endforeach
                @endforeach
              </table>
            </div>
            <div class="text-right mt-3">
              <a href="{{ route('admin.role.index') }}" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan Akses Menu</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('script')
<script>
  $(document).ready(function() {
    document.querySelectorAll('.tree-toggle').forEach(function(button) {
      button.addEventListener('click', function() {
        const parentId = button.getAttribute('data-tree-toggle');
        const children = document.querySelectorAll('[data-tree-child-of="' + parentId + '"]');
        const icon = button.querySelector('i');

        const collapsed = children[0] && children[0].style.display === 'none';

        children.forEach(function(group) {
          group.style.display = collapsed ? '' : 'none';
        });

        icon.classList.toggle('fa-chevron-right', !collapsed);
        icon.classList.toggle('fa-chevron-down', collapsed);
      });
    });
  });
</script>
@endpush