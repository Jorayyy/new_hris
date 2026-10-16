@if ($paginator->hasPages())
    <div class="pagination row-between">
        <span class="small muted">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </span>
        <span class="btn-row">
            @if ($paginator->onFirstPage())
                <span class="btn sm" style="opacity:.45">Prev</span>
            @else
                <a class="btn sm" href="{{ $paginator->previousPageUrl() }}">Prev</a>
            @endif
            @if ($paginator->hasMorePages())
                <a class="btn sm" href="{{ $paginator->nextPageUrl() }}">Next</a>
            @else
                <span class="btn sm" style="opacity:.45">Next</span>
            @endif
        </span>
    </div>
@endif
