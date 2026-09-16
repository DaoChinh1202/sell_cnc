@extends('layouts.storefront')

@php
    $name = data_get($product, 'name') ?: 'Sản phẩm trang sức';
    $category = data_get($product, 'category.name') ?: 'Trang sức';
    $image = data_get($product, 'image');
    $imageUrl = is_string($image) && $image !== ''
        ? (str_starts_with($image, 'http') ? $image : asset('storage/' . $image))
        : asset('assets/storefront/prod-nislconuat.jpg');
    $price = data_get($product, 'price');
    $discount = max(0, min(100, (float) (data_get($product, 'discount') ?: 0)));
    $currentPrice = $price !== null ? (float) $price * (1 - $discount / 100) : null;
    $quantity = max(0, (int) (data_get($product, 'quantity') ?: 0));
    $available = $quantity > 0 && data_get($product, 'status', 'active') === 'active';
@endphp

@section('title', $name . ' — Lumière Jewelry')

@section('content')
<div class="announcement"><div class="container announcement__inner"><span>✦ Miễn phí vận chuyển cho đơn từ 2.000.000đ</span><span class="announcement__right">Hotline: 1900 6868 <i></i> Theo dõi chúng tôi trên Instagram</span></div></div>
<header class="site-header">
    <div class="container site-header__main">
        <button class="menu-toggle" aria-label="Mở menu">☰</button>
        <a href="{{ route('home') }}" class="brand"><img src="{{ asset('assets/images/duy-hoang-cnc-logo-vector.svg') }}" alt="Duy Hoàng CNC"></a>
        <form class="search"><input type="search" placeholder="Tìm kiếm trang sức..." aria-label="Tìm kiếm"><button aria-label="Tìm kiếm">⌕</button></form>
        <div class="header-tools"><a href="#" aria-label="Yêu thích">♡ <small>Yêu thích</small></a><a href="#" aria-label="Tài khoản">♙ <small>Tài khoản</small></a></div>
    </div>
    <nav class="main-nav"><div class="container main-nav__inner"><button class="all-categories">☷ <span>Tất cả danh mục</span></button><div class="nav-links"><a href="{{ route('home') }}">Trang chủ</a><a class="active" href="{{ route('home') }}#products">Cửa hàng</a><a href="{{ route('home') }}#gifts">Quà tặng</a><a href="{{ route('home') }}#categories">Bông tai</a><a href="{{ route('home') }}#products">Nhẫn</a><a href="{{ route('home') }}#stories">Về chúng tôi</a><a href="{{ route('home') }}#newsletter">Liên hệ</a></div></div></nav>
</header>

<main class="product-detail">
    <div class="container">
        <nav class="product-detail__breadcrumb" aria-label="Đường dẫn"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('home') }}#products">Cửa hàng</a><span>/</span><strong>{{ $name }}</strong></nav>
        <div class="product-detail__layout">
            <figure class="product-detail__gallery"><img src="{{ $imageUrl }}" alt="{{ $name }}"></figure>
            <section class="product-detail__summary" aria-labelledby="product-title">
                <p class="eyebrow">{{ $category }}</p>
                <h1 id="product-title">{{ $name }}</h1>
                <div class="product-detail__price">
                    @if($currentPrice !== null)
                        @if($discount > 0)<del>{{ number_format((float) $price, 0, ',', '.') }}đ</del><span class="product-detail__discount">-{{ rtrim(rtrim(number_format($discount, 2, '.', ''), '0'), '.') }}%</span>@endif
                        <strong>{{ number_format($currentPrice, 0, ',', '.') }}đ</strong>
                    @else <strong>Liên hệ</strong> @endif
                </div>
                <p class="product-detail__description">{{ data_get($product, 'description') ?: 'Một thiết kế tinh tế được tuyển chọn để đồng hành cùng những khoảnh khắc đáng nhớ.' }}</p>
                <p class="product-detail__stock {{ $available ? 'is-available' : 'is-unavailable' }}"><span></span>{{ $available ? 'Còn hàng' : 'Tạm hết hàng' }}@if($available) <small>{{ $quantity }} sản phẩm có sẵn</small>@endif</p>
            </section>
        </div>
    </div>
</main>
<footer class="footer product-detail__footer"><div class="container footer__bottom"><span>© 2024 Lumière Jewelry. Thủ công với yêu thương.</span><a href="{{ route('home') }}">Quay lại trang chủ ↗</a></div></footer>
@include('partials.storefront-contact')
@endsection
