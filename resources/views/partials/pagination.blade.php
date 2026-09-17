@if($paginator->hasPages())
<nav class="pagination" aria-label="Paginación">
    <span>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} registros</span>
    <div>
        @if($paginator->onFirstPage())<span class="btn disabled" aria-disabled="true">Anterior</span>
        @else<a class="btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>@endif
        <span aria-current="page">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())<a class="btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
        @else<span class="btn disabled" aria-disabled="true">Siguiente</span>@endif
    </div>
</nav>
@endif
