@if ($paginator->hasPages())
    <nav class="row" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="btn btn-sm" aria-disabled="true" style="opacity:.5">← Previous</span>
        @else
            <a class="btn btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Previous</a>
        @endif
        <span class="muted small">Page {{ $paginator->currentPage() }}@if(method_exists($paginator, 'lastPage')) of {{ $paginator->lastPage() }}@endif</span>
        @if ($paginator->hasMorePages())
            <a class="btn btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Next →</a>
        @else
            <span class="btn btn-sm" aria-disabled="true" style="opacity:.5">Next →</span>
        @endif
    </nav>
@endif
