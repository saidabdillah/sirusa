{{-- Skeleton loading screen. Hide on window.load (>=250ms), fallback 5s, fades out. --}}
@if(($skeleton ?? 'app') === 'landing')
<div class="page-loader" id="pageLoader" aria-hidden="true">
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
      <div class="sk-card"><div class="sk-line w-60"></div><div class="sk-line w-90"></div></div>
      <div class="sk-card"><div class="sk-line w-60"></div><div class="sk-line w-90"></div></div>
      <div class="sk-card"><div class="sk-line w-60"></div><div class="sk-line w-90"></div></div>
    </div>
  </div>
</div>
@else
<div class="page-loader" id="pageLoader" aria-hidden="true">
  <div class="skeleton skeleton-app">
    <div class="sk-navbar">
      <div class="sk-logo"></div>
      <div class="sk-nav"></div>
    </div>
    <div class="sk-body">
      <div class="sk-title">
        <div class="sk-line w-40"></div>
      </div>
      <div class="sk-flex">
        <div class="sk-main">
          <div class="sk-card">
            <div class="sk-line w-50"></div>
            <div class="sk-line w-100"></div>
            <div class="sk-line w-85"></div>
          </div>
        </div>
        <div class="sk-side">
          <div class="sk-card">
            <div class="sk-line w-70 mb-2"></div>
            <div class="sk-line w-90"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

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