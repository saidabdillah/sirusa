{{-- Reusable full-page app skeleton: navbar + sidebar + main content (param: $content) --}}
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
      {!! $content !!}
    </div>
  </div>
</div>