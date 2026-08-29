<div class="main-sidebar sidebar-style-2">
  <aside id="sidebar-wrapper">
    <div class="sidebar-brand">
      <a href="{{ route('dashboard') }}">SIRUSA</a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
      <a href="{{ route('dashboard') }}">SR</a>
    </div>
    <ul class="sidebar-menu">
      <li class="menu-header">Menu Utama</li>
      <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <a href="{{ route('dashboard') }}" class="nav-link"><i class="fas fa-fire"></i><span>Dasbor</span></a>
      </li>

      @if(auth()->user()->hasRole(['super_admin', 'admin']))
        <li class="menu-header">Manajemen</li>
        <li class="{{ request()->routeIs('admin.beasiswa.*') ? 'active' : '' }}">
          <a href="{{ route('admin.beasiswa.index') }}" class="nav-link"><i class="fas fa-award"></i><span>Beasiswa</span></a>
        </li>
        <li class="{{ request()->routeIs('admin.pendaftar.*') ? 'active' : '' }}">
          <a href="{{ route('admin.pendaftar.index') }}" class="nav-link"><i class="fas fa-users"></i><span>Pendaftar</span></a>
        </li>
        <li class="{{ request()->routeIs('admin.pengguna.*') ? 'active' : '' }}">
          <a href="{{ route('admin.pengguna.index') }}" class="nav-link"><i class="fas fa-user-cog"></i><span>Pengguna</span></a>
        </li>
      @endif

      @if(auth()->user()->hasRole('admin'))
        <li class="menu-header">Pengaturan</li>
        <li class="{{ request()->routeIs('admin.template.*') ? 'active' : '' }}">
          <a href="{{ route('admin.template.index') }}" class="nav-link"><i class="fas fa-file-word"></i><span>Template Surat</span></a>
        </li>
      @endif

      @if(auth()->user()->hasRole('user'))
        <li class="menu-header">Beasiswa</li>
        <li class="{{ request()->routeIs('user.beasiswa.*') ? 'active' : '' }}">
          <a href="{{ route('user.beasiswa.index') }}" class="nav-link"><i class="fas fa-award"></i><span>Daftar Beasiswa</span></a>
        </li>
        <li class="{{ request()->routeIs('user.pendaftaran.*') ? 'active' : '' }}">
          <a href="{{ route('user.pendaftaran.index') }}" class="nav-link"><i class="fas fa-file-alt"></i><span>Pendaftaran Saya</span></a>
        </li>
      @endif
    </ul>
  </aside>
</div>
