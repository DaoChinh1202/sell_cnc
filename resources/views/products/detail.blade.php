@extends('layouts.storefront')

@php
    $name = $product->name;
    $category = $product->category?->name;
    $image = data_get($product, 'image');
    $imageUrl = is_string($image) && $image !== ''
        ? (str_starts_with($image, 'http') ? $image : asset('storage/' . $image))
        : null;
    $price = data_get($product, 'price');

@endphp

@section('title', $name . ' — Duy Hoàng - Kho mẫu CNC')

@section('content')
<header class="site-header">
    <div class="container site-header__main">
        <button class="menu-toggle" aria-label="Mở menu">☰</button>
        <a href="{{ route('home') }}" class="brand"><img src="{{ asset('assets/images/logo-duy-hoang-gold-brown.svg') }}" alt="Duy Hoàng CNC"></a>
        <form class="search" method="get" action="{{ route('storefront.products.index') }}"><input type="search" name="q" maxlength="100" placeholder="Tìm kiếm mẫu CNC..." aria-label="Tìm kiếm"><button aria-label="Tìm kiếm">⌕</button></form>
        <div class="header-tools"><a href="#" aria-label="Yêu thích">♡ <small>Yêu thích</small></a><a href="#" aria-label="Tài khoản">♙ <small>Tài khoản</small></a></div>
    </div>
    <nav class="main-nav"><div class="container main-nav__inner"><a class="all-categories" href="{{ route('home') }}#categories">☷ <span>Tất cả danh mục</span></a><div class="nav-links"><a href="{{ route('home') }}">Trang chủ</a><a class="active" href="{{ route('home') }}#products">Thư viện mẫu</a><a href="{{ route('home') }}#categories">Danh mục CNC</a><a href="{{ route('home') }}#contact">Liên hệ</a></div></div></nav>
</header>

<main class="product-detail">
    <div class="container">
        <nav class="product-detail__breadcrumb" aria-label="Đường dẫn"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('home') }}#products">Thư viện mẫu</a><span>/</span><strong>{{ $name }}</strong></nav>
        <div class="product-detail__layout">
            <figure class="product-detail__gallery">@if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $name }}">@else<span class="cnc-card__placeholder">DH<small>Ảnh đang cập nhật</small></span>@endif</figure>
            <section class="product-detail__summary" aria-labelledby="product-title">
                <p class="eyebrow">{{ $category }}</p>
                <h1 id="product-title">{{ $name }}</h1>
                <div class="product-detail__price">
                    @if($price !== null)
                        <strong>{{ number_format((float) $price, 0, ',', '.') }}đ</strong>
                    @else <strong>Liên hệ</strong> @endif
                </div>
                @if($product->description)<p class="product-detail__description">{{ $product->description }}</p>@endif

            </section>
        </div>
    </div>
</main>
<footer class="footer product-detail__footer"><div class="container footer__bottom"><span>© 2024 Duy Hoàng - Kho mẫu CNC. Thủ công với yêu thương.</span><a href="{{ route('home') }}">Quay lại trang chủ ↗</a></div></footer>
@include('partials.storefront-contact')
@endsection
