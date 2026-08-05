@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        <div class="pagination__links">
            @if ($paginator->onFirstPage())<span aria-disabled="true">Sebelumnya</span>@else<a href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>@endif
            @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}">Berikutnya</a>@else<span aria-disabled="true">Berikutnya</span>@endif
        </div>
    </nav>
@endif
