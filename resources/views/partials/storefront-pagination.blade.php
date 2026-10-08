@if ($paginator->hasPages())
    <nav class="storefront-pagination" aria-label="Phân trang sản phẩm">
        <p class="storefront-pagination__summary">Hiển thị {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} trong {{ $paginator->total() }} mẫu</p>
        <ul class="storefront-pagination__list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="storefront-pagination__item" aria-disabled="true" aria-label="Trang trước">‹</span>
                @else
                    <a class="storefront-pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước">‹</a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="storefront-pagination__ellipsis">{{ $element }}</span></li>
                @else
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="storefront-pagination__item" aria-current="page" aria-label="Trang {{ $page }}">{{ $page }}</span>
                            @else
                                <a class="storefront-pagination__item" href="{{ $url }}" aria-label="Trang {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a class="storefront-pagination__item" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau">›</a>
                @else
                    <span class="storefront-pagination__item" aria-disabled="true" aria-label="Trang sau">›</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
