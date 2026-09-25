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
            @if($menu->route && \Illuminate\Support\Facades\Route::has($menu->route))
            <li class="{{ $activePatterns && request()->routeIs($activePatterns) ? 'active' : '' }}">
              <a href="{{ route($menu->route) }}" class="nav-link"><i
                  class="{{ $menu->icon }}"></i><span>{{ $menu->label }}</span></a>
            </li>
            @else
            <li>
              <span class="nav-link"><i class="{{ $menu->icon }}"></i><span>{{ $menu->label }}</span></span>
            </li>
            @endif
          @else
            <li class="dropdown {{ $menu->children->contains(fn ($child) => request()->routeIs($child->route) || ($child->scope && request()->routeIs($child->scope.'.*'))) ? 'active' : '' }}">
              <a href="#" class="nav-link has-dropdown"><i class="{{ $menu->icon }}"></i><span>{{ $menu->label }}</span></a>
              <ul class="dropdown-menu">
                @foreach($menu->children as $child)
                  @if($child->route && \Illuminate\Support\Facades\Route::has($child->route))
                  <li class="{{ request()->routeIs($child->route) || ($child->scope && request()->routeIs($child->scope.'.*')) ? 'active' : '' }}">
                    <a href="{{ route($child->route) }}" class="nav-link"><span>{{ $child->label }}</span></a>
                  </li>
                  @else
                  <li><span class="nav-link"><span>{{ $child->label }}</span></span></li>
                  @endif
                @endforeach
              </ul>
            </li>
          @endif
        @endforeach
      @endforeach
    </ul>
  </aside>
</div>