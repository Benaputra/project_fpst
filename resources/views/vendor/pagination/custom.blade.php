@if ($paginator->hasPages())
    <nav class="pagination-wrapper" role="navigation" aria-label="Navigasi Halaman">
        <div class="pagination-info">
            Menampilkan <span class="pagination-info-bold">{{ $paginator->firstItem() ?? 0 }}</span> sampai <span class="pagination-info-bold">{{ $paginator->lastItem() ?? 0 }}</span> dari <span class="pagination-info-bold">{{ $paginator->total() }}</span> data
        </div>

        <div class="pagination-controls">
            {{-- Tombol Sebelumnya (Previous) --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" aria-label="Halaman Sebelumnya">
                    <svg class="pagination-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="16" height="16">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="pagination-btn-text">Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn" aria-label="Halaman Sebelumnya">
                    <svg class="pagination-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="16" height="16">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="pagination-btn-text">Sebelumnya</span>
                </a>
            @endif

            {{-- Nomor-nomor Halaman --}}
            <div class="pagination-pages">
                @foreach ($elements as $element)
                    {{-- Separator Tiga Titik "..." --}}
                    @if (is_string($element))
                        <span class="pagination-page-item pagination-dots" aria-disabled="true">{{ $element }}</span>
                    @endif

                    {{-- Link Nomor Halaman --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="pagination-page-item pagination-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="pagination-page-item" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Tombol Berikutnya (Next) --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn" aria-label="Halaman Berikutnya">
                    <span class="pagination-btn-text">Berikutnya</span>
                    <svg class="pagination-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="16" height="16">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" aria-label="Halaman Berikutnya">
                    <span class="pagination-btn-text">Berikutnya</span>
                    <svg class="pagination-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="16" height="16">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
