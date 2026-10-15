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
              {{-- Hanya satu filter, jadi tombol sejajar dengan select. --}}
              <div class="col-auto mb-2 mb-md-0">
                <button type="submit" class="btn btn-primary" data-loading-text="Memuat...">
                  <i class="fas fa-search"></i> Cari
                </button>
              </div>
              <div class="col-auto mb-2 mb-md-0">
                <a href="{{ route('admin.menu.index') }}" class="btn btn-secondary">
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
            <div class="mt-2">
              Untuk menu yang punya baris <strong>Aksi yang diizinkan</strong>, centang aksinya secara
              terpisah: role bisa diberi <strong>Lihat</strong> saja tanpa boleh menambah, mengubah,
              atau menghapus.
            </div>
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
                          <input type="checkbox" class="custom-control-input" id="section-{{ $loop->index }}"
                            data-section-toggle>
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
                        <input type="checkbox" class="custom-control-input" id="menu-{{ $menu->id }}" name="menus[]"
                          value="{{ $menu->id }}" data-parent-menu="{{ $menu->id }}" {{ in_array($menu->id, $grants,
                        true) || $menuLocked ? 'checked' : '' }}
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
                        <input type="checkbox" class="custom-control-input" id="menu-{{ $child->id }}" name="menus[]"
                          value="{{ $child->id }}" data-child-of="{{ $menu->id }}" {{ in_array($child->id, $grants,
                        true) || $childLocked ? 'checked' : '' }}
                        {{ $childLocked ? 'disabled' : '' }}>
                        <label class="custom-control-label" for="menu-{{ $child->id }}"></label>
                      </div>
                    </td>
                  </tr>
                  @if(in_array($child->scope, $actionModules, true))
                  {{-- Baris aksi hanya untuk modul CRUD, dan modul-modul itu
                       adalah baris ANAK (Pengguna/Role/Kelola Menu di bawah
                       "Kelola Akses"). Super Admin dikunci penuh -- dan
                       `User::hasMenuAction()` mematok itu juga -- jadi
                       centangnya disabled supaya tidak ada yang mencoba
                       mencabutnya lewat halaman ini. --}}
                  @php($aksiLocked = $childLocked || $activeRole->name === 'super_admin')
                  <tr class="table-light" data-section-body="{{ $loop->parent->parent->index }}">
                    <td class="pl-5">
                      <span class="text-muted pl-4"><i class="fas fa-key mr-2"></i>Aksi yang diizinkan</span>
                      @if($aksiLocked)
                      <span class="badge badge-info ml-1">semua aksi</span>
                      @endif
                    </td>
                    <td class="text-center">
                      @foreach($actionLabels as $actionKey => $actionLabel)
                      @php($actionChecked = $aksiLocked || isset($rolePermissions[$child->scope.'.'.$actionKey]))
                      <div class="custom-control custom-checkbox d-inline-block mr-3">
                        <input type="checkbox" class="custom-control-input"
                          id="aksi-{{ $child->id }}-{{ $actionKey }}" name="aksi[{{ $child->id }}][]"
                          value="{{ $actionKey }}" data-aksi-menu="{{ $child->id }}"
                          {{ $actionChecked ? 'checked' : '' }} {{ $aksiLocked ? 'disabled' : '' }}>
                        <label class="custom-control-label" for="aksi-{{ $child->id }}-{{ $actionKey }}">{{
                          $actionLabel }}</label>
                      </div>
                      @endforeach
                    </td>
                  </tr>
                  @endif
                  @endforeach
                  @endforeach
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
              <a href="{{ route('admin.role.index') }}" class="btn btn-outline-secondary"><i
                  class="fas fa-arrow-left mr-1"></i> Kembali</a>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Simpan Akses untuk {{ $roleLabels[$activeRole->name] ??
                ucfirst($activeRole->name) }}
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

      // Menu modul dicari lewat `value`-nya (bisa baris induk atau anak),
      // bukan lewat `data-parent-menu`, karena modul CRUD ada di baris anak.
      const menuCheckboxOf = function(menuId) {
        return form.querySelector('input[name="menus[]"][value="' + menuId + '"]');
      };

      const aksiCheckboxesOf = function(menuId) {
        return Array.from(form.querySelectorAll('input[data-aksi-menu="' + menuId + '"]:not(:disabled)'));
      };

      // Aksi mengikuti menu modulnya: mencentang aksi otomatis mencentang
      // menunya (tanpa menu, permission-nya tidak akan tersimpan).
      form.querySelectorAll('input[data-aksi-menu]').forEach(function(box) {
        box.addEventListener('change', function() {
          if (box.disabled) {
            return;
          }

          const menuId = box.getAttribute('data-aksi-menu');
          const menuBox = menuCheckboxOf(menuId);

          if (menuBox && !menuBox.disabled && box.checked) {
            menuBox.checked = true;
          }

          syncSectionToggles();
        });
      });

      // Kebalikannya: mencentang menu modul otomatis mencentang aksi "Lihat"
      // supaya role yang baru diberi menu tidak langsung 403 saat diklik, dan
      // mencabut menunya ikut mencabut semua aksinya supaya tidak ada
      // permission menggantung.
      form.querySelectorAll('input[name="menus[]"]').forEach(function(menuBox) {
        menuBox.addEventListener('change', function() {
          const boxes = aksiCheckboxesOf(menuBox.value);

          if (boxes.length === 0) {
            return;
          }

          if (!menuBox.checked) {
            boxes.forEach(function(box) { box.checked = false; });
            return;
          }

          if (!boxes.some(function(box) { return box.checked; })) {
            const lihat = boxes.find(function(box) { return box.value === 'read'; });

            if (lihat) {
              lihat.checked = true;
            }
          }
        });
      });

      syncSectionToggles();
    }
  });
</script>
@endpush