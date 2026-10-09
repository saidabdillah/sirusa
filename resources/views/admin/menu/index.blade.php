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

  <div class="section-body">
    <div class="col-12 p-0">
      <div class="card">
        <div class="card-header">
          <h4>Akses Menu per Role</h4>
          <div class="card-header-action ml-3">
            <a href="{{ route('admin.menukelola.index') }}" class="btn btn-info">
              <i class="fas fa-sitemap"></i> Kelola Menu
            </a>
          </div>
        </div>
        <div class="card-body">
          <form method="GET" action="{{ route('admin.menu.index') }}" class="mb-3">
            <div class="form-row align-items-end">
              <div class="col-md-6 mb-2 mb-md-0">
                <label for="roleFilter">Role</label>
                <select name="role" id="roleFilter" class="form-control">
                  <option value="">-- Pilih Role --</option>
                  @foreach($roles as $role)
                  <option value="{{ $role->id }}" {{ $activeRole && $activeRole->id === $role->id ? 'selected' : '' }}>
                    {{ $roleLabels[$role->name] ?? ucfirst($role->name) }}
                  </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3 mb-2 mb-md-0">
                <button type="submit" class="btn btn-primary btn-block" data-loading-text="Memuat...">
                  <i class="fas fa-search"></i> Cari
                </button>
              </div>
              <div class="col-md-3">
                <a href="{{ route('admin.menu.index') }}" class="btn btn-secondary btn-block">
                  <i class="fas fa-redo"></i> Reset
                </a>
              </div>
            </div>
          </form>

          @if($activeRole)
          <div class="alert alert-primary">
            <i class="fas fa-info-circle mr-1"></i>
            Anda sedang mengatur akses untuk role
            <strong>{{ $roleLabels[$activeRole->name] ?? ucfirst($activeRole->name) }}</strong>.
            Mencentang sebuah menu berarti menu itu <strong>tampil di sidebar</strong> sekaligus
            <strong>memberikan akses ke halaman modul</strong> terkait. Mencentang sub-menu otomatis
            mencentang menu induknya. Dasbor bersifat wajib dan selalu aktif. Perubahan hanya
            disimpan untuk role ini; role lain tidak terpengaruh.
          </div>

          <form action="{{ route('admin.menu.grants') }}" method="POST" id="formMenuGrants" data-ajax-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="role_id" value="{{ $activeRole->id }}">

            <div class="table-responsive">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Menu</th>
                    <th class="text-center" style="width: 130px;">Akses</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($menus->groupBy('section') as $section => $sectionMenus)
                  <tr class="table-active menu-section-row" data-section-header="{{ $loop->index }}">
                    <td colspan="2">
                      <div class="d-flex align-items-center">
                        <strong class="ml-1">{{ $section }}</strong>
                        <div class="custom-control custom-checkbox ml-4">
                          <input type="checkbox" class="custom-control-input" id="section-{{ $loop->index }}" data-section-toggle>
                          <label class="custom-control-label" for="section-{{ $loop->index }}">Pilih semua</label>
                        </div>
                      </div>
                    </td>
                  </tr>
                  @foreach($sectionMenus as $menu)
                  @php($menuLocked = in_array($menu->id, $lockedMenuIds, true))
                  <tr data-section-body="{{ $loop->parent->index }}">
                    <td>
                      <i class="{{ $menu->icon }} text-primary mr-1"></i>
                      <strong>{{ $menu->label }}</strong>
                      @if($menu->wajib)<span class="badge badge-warning ml-1">wajib</span>@endif
                      @if(! $menu->aktif)<span class="badge badge-danger ml-1">nonaktif</span>@endif
                    </td>
                    <td class="text-center">
                      <div class="custom-control custom-checkbox d-inline-block">
                        <input type="checkbox"
                          class="custom-control-input"
                          id="menu-{{ $menu->id }}"
                          name="menus[]"
                          value="{{ $menu->id }}"
                          data-parent-menu="{{ $menu->id }}"
                          {{ in_array($menu->id, $grants, true) || $menuLocked ? 'checked' : '' }}
                          {{ $menuLocked ? 'disabled' : '' }}>
                        <label class="custom-control-label" for="menu-{{ $menu->id }}"></label>
                      </div>
                    </td>
                  </tr>
                  @foreach($menu->children as $child)
                  @php($childLocked = in_array($child->id, $lockedMenuIds, true))
                  <tr class="table-light" data-section-body="{{ $loop->parent->parent->index }}">
                    <td class="pl-5 text-muted">
                      <i class="{{ $child->icon ?? 'fas fa-angle-right' }} mr-2"></i>
                      {{ $child->label }}
                      @if(! $child->aktif)<span class="badge badge-danger ml-1">nonaktif</span>@endif
                    </td>
                    <td class="text-center">
                      <div class="custom-control custom-checkbox d-inline-block">
                        <input type="checkbox"
                          class="custom-control-input"
                          id="menu-{{ $child->id }}"
                          name="menus[]"
                          value="{{ $child->id }}"
                          data-child-of="{{ $menu->id }}"
                          {{ in_array($child->id, $grants, true) || $childLocked ? 'checked' : '' }}
                          {{ $childLocked ? 'disabled' : '' }}>
                        <label class="custom-control-label" for="menu-{{ $child->id }}"></label>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                  @endforeach
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
              <a href="{{ route('admin.role.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Simpan Akses untuk {{ $roleLabels[$activeRole->name] ?? ucfirst($activeRole->name) }}
              </button>
            </div>
          </form>
          @else
          <div class="alert alert-warning mb-0">
            <i class="fas fa-info-circle mr-1"></i> Pilih role terlebih dahulu untuk mengatur akses menunya.
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('script')
<script>
  $(document).ready(function() {
    const form = document.getElementById('formMenuGrants');

    if (form) {
      const enabledMenus = function(rows) {
        return Array.from(rows).flatMap(function(row) {
          return Array.from(row.querySelectorAll('input[name="menus[]"]:not(:disabled)'));
        });
      };

      const childCheckboxesOf = function(parentId) {
        return Array.from(form.querySelectorAll('input[data-child-of="' + parentId + '"]:not(:disabled)'));
      };

      const bodyRowsOf = function(header) {
        const id = header.getAttribute('data-section-header');
        return Array.from(form.querySelectorAll('[data-section-body="' + id + '"]'));
      };

      const syncSectionToggles = function() {
        form.querySelectorAll('[data-section-header]').forEach(function(header) {
          const toggle = header.querySelector('[data-section-toggle]');
          if (!toggle) {
            return;
          }

          const boxes = enabledMenus(bodyRowsOf(header));
          const checked = boxes.filter(function(box) { return box.checked; }).length;

          toggle.checked = boxes.length > 0 && checked === boxes.length;
          toggle.indeterminate = checked > 0 && checked < boxes.length;
        });
      };

      form.querySelectorAll('input[data-parent-menu]').forEach(function(parent) {
        parent.addEventListener('change', function() {
          childCheckboxesOf(parent.getAttribute('data-parent-menu')).forEach(function(child) {
            child.checked = parent.checked;
          });
          syncSectionToggles();
        });
      });

      form.querySelectorAll('input[data-child-of]').forEach(function(child) {
        child.addEventListener('change', function() {
          const parent = form.querySelector('input[data-parent-menu="' + child.getAttribute('data-child-of') + '"]');
          if (!parent || parent.disabled) {
            syncSectionToggles();
            return;
          }

          if (child.checked) {
            parent.checked = true;
          } else {
            parent.checked = childCheckboxesOf(parent.getAttribute('data-parent-menu'))
              .some(function(box) { return box.checked; });
          }
          syncSectionToggles();
        });
      });

      form.querySelectorAll('[data-section-toggle]').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
          const header = toggle.closest('tr[data-section-header]');
          enabledMenus(bodyRowsOf(header)).forEach(function(box) {
            box.checked = toggle.checked;
          });
          syncSectionToggles();
        });
      });

      syncSectionToggles();
    }
  });
</script>
@endpush
