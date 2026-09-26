@props(['icon', 'bg' => 'primary', 'label', 'value', 'hint' => null])

<div class="col-lg-3 col-md-6 col-sm-6 col-12">
  <div class="card card-statistic-1">
    <div class="card-icon bg-{{ $bg }}">
      <i class="fas {{ $icon }}"></i>
    </div>
    <div class="card-wrap">
      <div class="card-header">
        <h4>{{ $label }}</h4>
      </div>
      <div class="card-body">
        {{ $value }}
        @if($hint)
          <div class="text-small text-muted mt-1">{{ $hint }}</div>
        @endif
      </div>
    </div>
  </div>
</div>
