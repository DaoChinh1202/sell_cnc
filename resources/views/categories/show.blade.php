@extends('layouts.storefront')

@php
    $categoryName = data_get($category, 'name') ?: 'Danh mục CNC';
    $categoryDescription = data_get($category, 'description');
    $productCount = method_exists($products, 'total') ? $products->total() : collect($products)->count();
@endphp

@section('title', $categoryName . ' — Duy Hoàng - Kho mẫu CNC')

@section('content')
<div class="announcement"><div class="container announcement__inner"><span>✦ Thư viện mẫu CNC dành cho xưởng</span><span class="announcement__right">Thiết kế rõ nét <i></i> Sẵn sàng để tham khảo</span></div></div>
<header class="site-header">
    <div class="container site-header__main"><button class="menu-toggle" aria-label="Mở menu">☰</button><a href="{{ route('home') }}" class="brand"><img src="{{ asset('assets/images/logo-duy-hoang-gold-brown.svg') }}" alt="Duy Hoàng CNC"></a><form class="search" method="get" action="{{ route('storefront.products.index') }}"><input type="search" name="q" maxlength="100" placeholder="Tìm kiếm mẫu CNC..." aria-label="Tìm kiếm"><button aria-label="Tìm kiếm">⌕</button></form></div>
    <nav class="main-nav"><div class="container main-nav__inner"><a class="all-categories" href="{{ route('home') }}#categories">☷ <span>Tất cả danh mục</span></a><div class="nav-links"><a href="{{ route('home') }}">Trang chủ</a><a class="active" href="{{ route('home') }}#products">Thư viện mẫu</a><a href="{{ route('home') }}#categories">Danh mục CNC</a><a href="{{ route('home') }}#contact">Liên hệ</a></div></div></nav>
</header>

<main class="category-page"><div class="container">
    <header class="category-page__intro"><div><nav class="category-page__breadcrumb" aria-label="Đường dẫn"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('home') }}#categories">Danh mục CNC</a><span>/</span><strong>{{ $categoryName }}</strong></nav><h1>{{ $categoryName }}</h1></div>@if($categoryDescription)<p class="category-page__description">{{ $categoryDescription }}</p>@endif</header>
    <div class="category-results">
        <aside class="category-filters" aria-labelledby="category-filters-title">
            <div class="category-filters__heading">
                <p class="eyebrow">Tinh chỉnh thư viện</p>
                <h2 id="category-filters-title">Lọc mẫu</h2>
            </div>
            <form id="category-filters-form" class="category-filters__form" method="get" action="{{ route('storefront.categories.show', $category) }}">
                <fieldset>
                    <legend>Khoảng giá</legend>
                    <div class="category-filters__prices">
                        <label for="min-price">Từ</label>
                        <input id="min-price" name="min_price" type="number" min="0" step="1000" inputmode="numeric" placeholder="0" value="{{ request('min_price') }}">
                        <span aria-hidden="true">—</span>
                        <label class="sr-only" for="max-price">Đến</label>
                        <input id="max-price" name="max_price" type="number" min="0" step="1000" inputmode="numeric" placeholder="Không giới hạn" value="{{ request('max_price') }}">
                    </div>
                </fieldset>
                <div class="category-filters__actions">
                    <button class="category-filters__apply" type="submit">Áp dụng <span aria-hidden="true">↗</span></button>
                    <a class="category-filters__reset" href="{{ route('storefront.categories.show', $category) }}">Đặt lại</a>
                </div>
            </form>
        </aside>

        <div class="category-results__products">
            <div class="category-results__toolbar"><p>{{ number_format($productCount, 0, ',', '.') }} {{ $productCount === 1 ? 'mẫu tham khảo' : 'mẫu tham khảo' }}</p><label for="sort-products">Sắp xếp theo</label><select id="sort-products" name="sort" form="category-filters-form"><option value="newest" @selected(request('sort', 'newest') === 'newest')>Mới nhất</option><option value="price_asc" @selected(request('sort') === 'price_asc')>Giá: thấp đến cao</option><option value="price_desc" @selected(request('sort') === 'price_desc')>Giá: cao đến thấp</option></select></div>
            @if(collect($products)->isEmpty())
                <div class="empty-state category-page__empty"><span class="empty-state__mark">◌</span><p class="eyebrow">Danh mục đang được cập nhật</p><h2>Chưa có mẫu trong bộ sưu tập</h2><p>Những thiết kế mới sẽ sớm được bổ sung. Bạn có thể xem các danh mục khác để tiếp tục tham khảo.</p><a class="text-link" href="{{ route('home') }}#categories">Xem danh mục khác ↗</a></div>
            @else
                <div class="category-product-grid">@foreach($products as $product)@php $image = data_get($product, 'image'); $name = data_get($product, 'name') ?: 'Mẫu thiết kế CNC'; $productCategory = data_get($product, 'category.name') ?: $categoryName; $price = data_get($product, 'price'); $imageUrl = $image ? (str_starts_with($image, 'http') ? $image : asset('storage/' . $image)) : asset('assets/storefront/prod-nislconuat.jpg'); $productUrl = route('storefront.products.show', $product); @endphp
                     <article class="category-product-card"><a class="category-product-card__image" href="{{ $productUrl }}" aria-label="Xem chi tiết {{ $name }}"><img src="{{ $imageUrl }}" alt="{{ $name }}" loading="lazy"></a><div class="category-product-card__info"><p>{{ $productCategory }}</p><h2><a href="{{ $productUrl }}">{{ $name }}</a></h2><strong>{{ $price !== null ? number_format((float) $price, 0, ',', '.') . 'đ' : 'Liên hệ' }}</strong><a class="category-product-card__link" href="{{ $productUrl }}">Xem chi tiết <span>↗</span></a></div></article>
                 @endforeach</div>
                 @if(method_exists($products, 'links'))<div class="category-pagination">{{ $products->links() }}</div>@endif
            @endif
        </div>
    </div>
</div></main>
<footer class="footer product-detail__footer"><div class="container footer__bottom"><span>© 2024 Duy Hoàng - Kho mẫu CNC. Thủ công với yêu thương.</span><a href="{{ route('home') }}">Quay lại trang chủ ↗</a></div></footer>
@include('partials.storefront-contact')
@endsection
