<div class="main-sidebar sidebar-style-2">
  <aside id="sidebar-wrapper">
    <div class="sidebar-brand">
      <a href="{{ route('dashboard') }}">SIRUSA</a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
      <a href="{{ route('dashboard') }}">SR</a>
    </div>
    <ul class="sidebar-menu">
      @php
        $userMenus = auth()->user()->sidebarMenus();
        $sectionOrder = \App\Models\Menu::sections();
      @endphp

      @foreach($sectionOrder as $section)
        @php
          $items = $userMenus->where('section', $section);
        @endphp
        @if($items->count() > 0)
          <li class="menu-header">{{ $section }}</li>
        @endif
        @foreach($items as $menu)
          @if($menu->children->isEmpty())
            @php
              $activePatterns = array_values(array_filter([$menu->route, $menu->scope ? $menu->scope.'.*' : null]));
            @endphp
            <li class="{{ $activePatterns && request()->routeIs($activePatterns) ? 'active' : '' }}">
              <a href="{{ $menu->linkUrl() }}" class="nav-link"><i
                  class="{{ $menu->icon }}"></i><span>{{ $menu->label }}</span></a>
            </li>
          @else
            <li class="dropdown {{ $menu->children->contains(fn ($child) => ($child->route && request()->routeIs($child->route)) || ($child->scope && request()->routeIs($child->scope.'.*'))) ? 'active' : '' }}">
              <a href="#" class="nav-link has-dropdown"><i class="{{ $menu->icon }}"></i><span>{{ $menu->label }}</span></a>
              <ul class="dropdown-menu">
                @foreach($menu->children as $child)
                  <li class="{{ ($child->route && request()->routeIs($child->route)) || ($child->scope && request()->routeIs($child->scope.'.*')) ? 'active' : '' }}">
                    <a href="{{ $child->linkUrl() }}" class="nav-link"><span>{{ $child->label }}</span></a>
                  </li>
                @endforeach
              </ul>
            </li>
          @endif
        @endforeach
      @endforeach
    </ul>
  </aside>
</div>