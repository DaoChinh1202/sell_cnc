@extends('layouts.storefront')
@section('title', ($search !== '' ? 'Tìm mẫu: '.$search : 'Tất cả mẫu CNC').' — Duy Hoàng')
@section('content')
<div class="cnc-catalog">
    @include('partials.catalog-header')
    <main class="cnc-search-page container">
        <a class="cnc-more" href="{{ route('home') }}">← Về trang chủ</a>
        <p class="cnc-kicker">THƯ VIỆN THIẾT KẾ CNC</p>
        <h1>{{ $search !== '' ? 'Kết quả tìm kiếm' : 'Tất cả mẫu CNC' }}</h1>
        <form class="cnc-search cnc-search--light" method="get" action="{{ route('storefront.products.index') }}" role="search">
            <label class="sr-only" for="catalog-search">Tìm theo tên mẫu hoặc mã sản phẩm</label>
            <input id="catalog-search" name="q" type="search" maxlength="100" value="{{ $search }}" placeholder="Tìm theo tên mẫu hoặc mã sản phẩm…">
            <button type="submit">Tìm mẫu <span aria-hidden="true">→</span></button>
        </form>
        @error('q')<p class="cnc-error" role="alert">{{ $message }}</p>@enderror
        <p class="cnc-result-count">{{ number_format($products->total(), 0, ',', '.') }} mẫu{{ $search !== '' ? ' cho “'.$search.'”' : ' trong thư viện' }}</p>
        @if($products->isNotEmpty())
            <div class="cnc-grid">@foreach($products as $product) @include('partials.catalog-card', ['product' => $product]) @endforeach</div>
            <div class="category-pagination">{{ $products->links() }}</div>
        @else
            <div class="cnc-empty"><h2>{{ $search !== '' ? 'Chưa tìm thấy mẫu phù hợp' : 'Thư viện đang được cập nhật' }}</h2><p>Thử tên ngắn hơn, mã mẫu khác hoặc liên hệ để được hỗ trợ.</p><a class="cnc-more" href="{{ route('storefront.products.index') }}">Xem tất cả mẫu ↗</a></div>
        @endif
    </main>
    @include('partials.catalog-footer')
</div>
@endsection
