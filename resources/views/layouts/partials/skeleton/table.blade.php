{{-- DataTable-like skeleton body: toolbar + thead + rows + pagination. Params: $rows, $cols, $count, $hideToolbar --}}
<div class="sk-title">
  <div class="sk-line w-40"></div>
</div>
<div class="sk-card">
  @if(($hideToolbar ?? false) !== true)
  <div class="sk-toolbar">
    <div class="sk-search"></div>
    @if(($count ?? null) !== null)
    <span class="sk-count">{{ $count }} data</span>
    @endif
    <span class="sk-btn sk-btn-danger"></span>
  </div>
  @endif
  <div class="sk-table" style="--cols: {{ $cols }};">
    <div class="sk-thead">
      @for($c = 0; $c < $cols; $c++)
      <div class="sk-tcell"></div>
      @endfor
    </div>
    @for($r = 0; $r < $rows; $r++)
    <div class="sk-trow">
      @for($c = 0; $c < $cols; $c++)
      <div class="sk-tcell"></div>
      @endfor
    </div>
    @endfor
  </div>
  <div class="sk-pagination">
    <div class="sk-pager sk-pager-wide"></div>
    <div class="sk-pager"></div>
    <div class="sk-pager"></div>
    <div class="sk-pager"></div>
    <div class="sk-pager sk-pager-wide"></div>
  </div>
</div>