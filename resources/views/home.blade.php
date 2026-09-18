@extends('layouts.storefront')
@section('title', 'Duy Hoàng — Kho mẫu thiết kế CNC')

@section('content')
<div class="cnc-catalog">
    @include('partials.catalog-header')
    <main id="top">
        <section class="cnc-intro" aria-labelledby="catalog-title">
            <div class="container">
                <p class="cnc-kicker">Ý TƯỞNG CỦA BẠN. ĐƯỜNG NÉT CỦA CHÚNG TÔI.</p>
                <h1 id="catalog-title">Khám phá kho mẫu<br>thiết kế <span>CNC.</span></h1>
                <p class="cnc-intro__description">Tìm mẫu phù hợp cho từng ý tưởng — từ nội thất,<br class="cnc-desktop-break"> hoa văn đến phù điêu trang trí.</p>
                <form class="cnc-search" action="{{ route('storefront.products.index') }}" method="get" role="search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
                    <label class="sr-only" for="home-search">Tìm mẫu theo tên hoặc mã sản phẩm</label>
                    <input id="home-search" type="search" name="q" maxlength="100" placeholder="Tìm theo tên mẫu hoặc mã sản phẩm…">
                    <button type="submit">Tìm mẫu <span aria-hidden="true">→</span></button>
                </form>
                @error('q')<p class="cnc-error" role="alert">{{ $message }}</p>@enderror
                <p class="cnc-intro__note">Khám phá thiết kế. Xem chi tiết. Chọn mẫu cho xưởng.</p>
            </div>
        </section>

        <nav class="cnc-categories container" id="categories" aria-label="Danh mục thiết kế CNC">
            <a class="cnc-category-chip cnc-category-chip--all" href="{{ route('storefront.products.index') }}">Tất cả mẫu <span aria-hidden="true">↗</span></a>
            @foreach($categories->where('products_count', '>', 0) as $category)
                <a class="cnc-category-chip" href="{{ route('storefront.categories.show', $category) }}">{{ $category->name }} <small>{{ $category->products_count }}</small></a>
            @endforeach
        </nav>

        <section class="cnc-shelf cnc-shelf--new" id="products" aria-labelledby="newest-title">
            <div class="container">
                <div class="cnc-shelf__heading">
                    <div><p class="cnc-kicker">KHÁM PHÁ THƯ VIỆN</p><h2 id="newest-title">Mẫu mới cập nhật<span class="cnc-title-dot">.</span></h2></div>
                    <a class="cnc-more" href="{{ route('storefront.products.index') }}">Xem tất cả <span aria-hidden="true">↗</span></a>
                </div>
                @if($newestProducts->isNotEmpty())
                    <div class="cnc-grid">@foreach($newestProducts as $product) @include('partials.catalog-card', ['product' => $product]) @endforeach</div>
                @else
                    <div class="cnc-empty"><h3>Thư viện đang được chuẩn bị</h3><p>Các mẫu thiết kế CNC sẽ sớm được cập nhật. Liên hệ Duy Hoàng nếu bạn cần tìm mẫu cụ thể.</p><a class="cnc-more" href="#contact">Liên hệ hỗ trợ ↗</a></div>
                @endif
            </div>
        </section>

        @foreach($categorySections as $category)
            <section class="cnc-shelf cnc-shelf--category" aria-labelledby="category-{{ $category->id }}-title">
                <div class="container">
                    <div class="cnc-shelf__heading">
                        <div><p class="cnc-kicker">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} / BỘ SƯU TẬP · {{ $category->products_count }} MẪU</p><h2 id="category-{{ $category->id }}-title">{{ $category->name }}</h2>@if($category->description)<p class="cnc-shelf__description">{{ $category->description }}</p>@endif</div>
                        <a class="cnc-more" href="{{ route('storefront.categories.show', $category) }}">Xem tất cả <span aria-hidden="true">↗</span></a>
                    </div>
                    <div class="cnc-grid">@foreach($category->products as $product) @include('partials.catalog-card', ['product' => $product]) @endforeach</div>
                </div>
            </section>
        @endforeach
    </main>
    @include('partials.catalog-footer')
</div>
@endsection
