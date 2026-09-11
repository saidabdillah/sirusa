{{-- Skeleton loading screen. Pages can override via @section('skeleton'); defaults to a full-page generic. Hide on window.load (>=250ms), fallback 5s, fades out. --}}
<div class="page-loader" id="pageLoader" aria-hidden="true">
  @hasSection('skeleton')
    @yield('skeleton')
  @else
    @if(($skeleton ?? 'app') === 'landing')
      <div class="skeleton skeleton-landing">
        <div class="sk-navbar">
          <div class="sk-logo"></div>
          <div class="sk-nav"></div>
        </div>
        <div class="sk-hero">
          <div class="sk-hero-text">
            <div class="sk-line w-70 mb-2"></div>
            <div class="sk-line w-50"></div>
          </div>
          <div class="sk-hero-box"></div>
        </div>
        <div class="sk-cards">
          @for($i = 0; $i < 6; $i++)
            <div class="sk-card">
              <div class="sk-line w-60"></div>
              <div class="sk-line w-90"></div>
            </div>
          @endfor
        </div>
      </div>
    @else
      <div class="skeleton skeleton-app">
        <div class="sk-navbar">
          <div class="sk-logo"></div>
          <div class="sk-nav"></div>
        </div>
        <div class="sk-body">
          <div class="sk-sidebar">
            <div class="sk-logo-sm"></div>
            <div class="sk-menu"></div>
            <div class="sk-menu"></div>
            <div class="sk-menu"></div>
            <div class="sk-menu"></div>
            <div class="sk-menu"></div>
          </div>
          <div class="sk-main">
            <div class="sk-title">
              <div class="sk-line w-40"></div>
            </div>
            @for($i = 0; $i < 4; $i++)
              <div class="sk-card mb-4">
                <div class="sk-line w-50"></div>
                <div class="sk-line w-100"></div>
                <div class="sk-line w-85"></div>
              </div>
            @endfor
          </div>
        </div>
      </div>
    @endif
  @endif
</div>

<script>
  (function () {
    var loader = document.getElementById('pageLoader');
    if (!loader) return;

    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var start = Date.now();
    var MIN_SHOWN = 250;
    var FALLBACK = 5000;

    function hide() {
      var delay = reduced ? 0 : Math.max(0, MIN_SHOWN - (Date.now() - start));
      setTimeout(function () {
        loader.classList.add('is-hidden');
        setTimeout(function () {
          if (loader.parentNode) loader.parentNode.removeChild(loader);
        }, 400);
      }, delay);
    }

    if (document.readyState === 'complete') {
      hide();
    } else {
      window.addEventListener('load', hide);
    }

    setTimeout(hide, FALLBACK);
  })();
</script>