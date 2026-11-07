<header class="cnc-header">
    <div class="container cnc-header__inner">
        <a class="cnc-brand" href="{{ route('home') }}" aria-label="khomau3d CNC — Trang chủ">
            <img src="{{ asset('assets/images/khomau3d-logo.webp') }}" alt="" width="132" height="88" style="object-fit: contain;">
            <span>KHOMAU3D<small>THƯ VIỆN THIẾT KẾ CNC</small></span>
        </a>
        <nav class="cnc-nav" aria-label="Điều hướng chính">
            <a href="{{ route('storefront.products.index') }}" @if(request()->routeIs('storefront.products.index')) aria-current="page" @endif>Kho mẫu</a>
            <a href="{{ route('home') }}#categories">Danh mục</a>
            <a class="cnc-nav__contact" href="#contact">Liên hệ <span aria-hidden="true">↗</span></a>
        </nav>
    </div>
</header>
