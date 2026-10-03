@extends('layouts.storefront')
@section('title', 'Kho mẫu CNC 3D, hoa văn và phù điêu | khomau3d')

@section('content')
<div class="cnc-catalog">
    @include('partials.catalog-header')
    <main id="top">
        <div class="cnc-intro">
            <picture>
                <source type="image/webp" srcset="{{ asset('assets/images/banner-640.webp') }} 640w, {{ asset('assets/images/banner-1280.webp') }} 1280w, {{ asset('assets/images/banner-1983.webp') }} 1983w" sizes="100vw">
                <img class="cnc-intro__art" src="{{ asset('assets/images/banner.png') }}" alt="Kho mẫu CNC 3D với hoa văn cửa và phù điêu trang trí" width="1983" height="793" fetchpriority="high">
            </picture>
        </div>

        <section class="cnc-home-search" aria-labelledby="home-search-title">
            <div class="container">
                <h1 id="home-search-title">Kho mẫu CNC 3D dành cho xưởng điêu khắc</h1>
                <p class="cnc-home-search__description">Tìm mẫu cho xưởng của bạn — khám phá hoa văn, phù điêu và thiết kế nội thất theo tên hoặc mã sản phẩm.</p>
                <form class="cnc-search" action="{{ route('storefront.products.index') }}" method="get" role="search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
                    <label class="sr-only" for="home-search">Tìm mẫu theo tên hoặc mã sản phẩm</label>
                    <input id="home-search" type="search" name="q" maxlength="100" placeholder="Tìm theo tên mẫu hoặc mã sản phẩm…">
                    <button type="submit">Tìm mẫu <span aria-hidden="true">→</span></button>
                </form>
                @error('q')<p class="cnc-error" role="alert">{{ $message }}</p>@enderror

            </div>
        </section>

        <section class="cnc-shelf cnc-shelf--new" id="products" aria-labelledby="newest-title">
            <div class="container">
                <div class="cnc-shelf__heading">
                    <div><p class="cnc-kicker">KHÁM PHÁ THƯ VIỆN</p><h2 id="newest-title">Mẫu mới cập nhật<span class="cnc-title-dot">.</span></h2></div>
                    <a class="cnc-more" href="{{ route('storefront.products.index') }}">Xem tất cả <span aria-hidden="true">↗</span></a>
                </div>
                @if($newestProducts->isNotEmpty())
                    <div class="cnc-grid">@foreach($newestProducts as $product) @include('partials.catalog-card', ['product' => $product]) @endforeach</div>
                @else
                    <div class="cnc-empty"><h3>Thư viện đang được chuẩn bị</h3><p>Các mẫu thiết kế CNC sẽ sớm được cập nhật. Liên hệ khomau3d nếu bạn cần tìm mẫu cụ thể.</p><a class="cnc-more" href="#contact">Liên hệ hỗ trợ ↗</a></div>
                @endif
            </div>
        </section>

        <section class="cnc-shelf cnc-shelf--categories" id="categories" aria-labelledby="categories-title">
            <div class="container">
                <div class="cnc-shelf__heading">
                    <div><p class="cnc-kicker">KHÁM PHÁ THEO BỘ SƯU TẬP</p><h2 id="categories-title">Danh mục mẫu CNC<span class="cnc-title-dot">.</span></h2></div>
                    <a class="cnc-more" href="{{ route('storefront.products.index') }}">Xem tất cả mẫu <span aria-hidden="true">↗</span></a>
                </div>
                <div class="cnc-category-grid">
                    @forelse($categories->where('products_count', '>', 0) as $category)
                        <a class="cnc-category-card" href="{{ route('storefront.categories.show', $category) }}">
                            <span class="cnc-category-card__image">
                                @if($category->image)
                                    <img src="{{ asset('storage/'.$category->image) }}" alt="{{ $category->name }}" loading="lazy" decoding="async" width="600" height="450">
                                @else
                                    <span class="cnc-category-card__placeholder" aria-label="Chưa có ảnh danh mục">DH</span>
                                @endif
                                <span class="cnc-category-card__open" aria-hidden="true">↗</span>
                            </span>
                            <span class="cnc-category-card__body">
                                <span class="cnc-category-card__count">{{ $category->products_count }} mẫu</span>
                                <strong>{{ $category->name }}</strong>
                                @if($category->description)<small>{{ $category->description }}</small>@endif
                            </span>
                        </a>
                    @empty
                        <div class="cnc-empty"><h3>Danh mục đang được chuẩn bị</h3><p>Các bộ sưu tập mẫu CNC sẽ sớm được cập nhật.</p></div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
    @include('partials.catalog-footer')
</div>
@endsection
