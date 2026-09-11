{{-- Form-like skeleton body. Params: $avatar, $sections = [['label' => ..., 'rows' => [cols,...]], ...] --}}
<div class="sk-title">
  <div class="sk-line w-40"></div>
</div>
<div class="sk-card">
  @if($avatar ?? false)
  <div class="sk-avatar-row">
    <div class="sk-avatar"></div>
    <div class="sk-avatar-btn"></div>
  </div>
  @endif
  @foreach($sections as $skSection)
    <div class="sk-section">{{ $skSection['label'] }}</div>
    @foreach($skSection['rows'] as $skCols)
      <div class="sk-form-row" style="--cols: {{ $skCols }}">
        @for($c = 0; $c < $skCols; $c++)
        <div class="sk-field">
          <div class="sk-line w-40 mb-2"></div>
          <div class="sk-input"></div>
        </div>
        @endfor
      </div>
    @endforeach
  @endforeach
</div>