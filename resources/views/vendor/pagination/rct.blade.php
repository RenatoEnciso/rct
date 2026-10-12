@if ($paginator->hasPages() || $paginator->total() > 0)
<nav class="rct-pager" aria-label="Paginación">
  <span class="rct-pager-info">
    @if(method_exists($paginator, 'total'))
      Mostrando {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }}
    @endif
  </span>
  @if ($paginator->hasPages())
  <div class="rct-pager-links">
    @if ($paginator->onFirstPage())
      <span class="disabled" aria-label="Anterior"><i class="bi bi-chevron-left"></i></span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior"><i class="bi bi-chevron-left"></i></a>
    @endif

    @isset($elements)
      @foreach ($elements as $element)
        @if (is_string($element))
          <span class="disabled">{{ $element }}</span>
        @endif
        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <span class="active" aria-current="page">{{ $page }}</span>
            @else
              <a href="{{ $url }}">{{ $page }}</a>
            @endif
          @endforeach
        @endif
      @endforeach
    @endisset

    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></a>
    @else
      <span class="disabled" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></span>
    @endif
  </div>
  @endif
</nav>
@endif
