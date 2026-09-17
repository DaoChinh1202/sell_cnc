@extends('layouts.storefront')

@section('title', 'Duy Hoàng — Kho mẫu CNC')

@section('content')
<header class="site-header">
    <div class="container site-header__main">
        <button class="menu-toggle" aria-label="Mở menu">☰</button>
        <a href="#" class="brand"><img src="{{ asset('assets/images/logo-duy-hoang-gold-brown.svg') }}" alt="Duy Hoàng CNC"></a>
        <form class="search"><input type="search" placeholder="Tìm kiếm trang sức..." aria-label="Tìm kiếm"><button aria-label="Tìm kiếm">⌕</button></form>
    </div>
    <nav class="main-nav"><div class="container main-nav__inner"><button class="all-categories">☷ <span>Tất cả danh mục</span></button><div class="nav-links"><a class="active" href="#top">Trang chủ</a><a href="#products">Thư viện mẫu</a><a href="#categories">Danh mục CNC</a><a href="#products">Mẫu nổi bật</a><a href="#stories">Về chúng tôi</a><a href="#newsletter">Liên hệ</a></div></div></nav>
</header>

<main id="top">
<section class="hero" aria-label="Mẫu thiết kế CNC nổi bật">
    <div class="hero__slides">
        <article class="hero__slide is-active">
            <img src="{{ asset('assets/storefront/hero-cnc-hoanh-phi.jpg') }}" alt="Mẫu CNC hoành phi với hoa và chim phượng" width="2560" height="1126" fetchpriority="high">
        </article>
        <article class="hero__slide">
            <img src="{{ asset('assets/storefront/hero-cnc-rong-nghe.jpg') }}" alt="Mẫu CNC rồng và nghê trang trí" width="2560" height="1224" decoding="async">
        </article>
        <article class="hero__slide">
            <img src="{{ asset('assets/storefront/hero-cnc-hoa-van.jpg') }}" alt="Mẫu CNC hoa văn cổ điển với khung và họa tiết đối xứng" width="2560" height="1658" decoding="async">
        </article>
    </div>
    <button class="hero__arrow hero__arrow--prev" aria-label="Ảnh trước">←</button>
    <button class="hero__arrow hero__arrow--next" aria-label="Ảnh tiếp">→</button>
    <div class="hero__dots"></div>
</section>

@php $categoryCollection = collect($categories ?? []); @endphp
<section class="section categories" id="categories"><div class="container"><div class="section-heading"><p class="eyebrow">Thư viện thiết kế</p><h2>Danh mục thiết kế <em>CNC</em></h2><p>Chọn nhanh nhóm mẫu phù hợp với ý tưởng và xưởng của bạn.</p></div>@if($categoryCollection->isEmpty())<div class="empty-state empty-state--categories"><span class="empty-state__mark">◎</span><p class="eyebrow">Chưa có danh mục</p><h3>Thư viện đang được chuẩn bị</h3><p>Các nhóm thiết kế CNC sẽ được cập nhật sớm.</p></div>@else<div class="category-grid">@foreach($categoryCollection as $category)@php $categoryProducts = data_get($category, 'products'); $activeProductCount = data_get($category, 'products_count'); if ($activeProductCount === null && $categoryProducts !== null) { $activeProductCount = collect($categoryProducts)->where('status', 'active')->count(); } $activeProductCount = $activeProductCount ?? 0; @endphp<a class="category-card" href="{{ route('storefront.categories.show', $category) }}"><div class="category-card__index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div><div class="category-card__body"><span class="category-card__name">{{ data_get($category, 'name', 'Danh mục thiết kế') }}</span>@if(data_get($category, 'description'))<p>{{ data_get($category, 'description') }}</p>@endif</div><div class="category-card__meta"><small>{{ $activeProductCount }} {{ $activeProductCount === 1 ? 'mẫu đang hoạt động' : 'mẫu đang hoạt động' }}</small><b>↗</b></div></a>@endforeach</div>@endif</div></section>

@php $featuredCollection = collect($featuredProducts ?? []); @endphp
<section class="section products" id="products"><div class="container"><div class="section-heading section-heading--row"><div><p class="eyebrow">Được xưởng quan tâm</p><h2>Mẫu CNC <em>nổi bật</em></h2></div><a class="text-link" href="#categories">Duyệt danh mục ↗</a></div>@if($featuredCollection->isEmpty())<div class="empty-state"><span class="empty-state__mark">+</span><p class="eyebrow">Đang cập nhật thư viện</p><h3>Chưa có mẫu nổi bật</h3><p>Những thiết kế CNC được chọn lọc sẽ sớm xuất hiện tại đây.</p></div>@else<div class="product-grid">@foreach($featuredCollection as $product)@php $image = data_get($product, 'image'); $name = data_get($product, 'name', 'Mẫu thiết kế CNC'); $category = data_get($product, 'category.name', 'Thiết kế CNC'); $price = data_get($product, 'price'); $discount = (float) data_get($product, 'discount', 0); $imageUrl = $image ? (str_starts_with($image, 'http') ? $image : asset('storage/'.$image)) : asset('assets/storefront/prod-nislconuat.jpg'); $productUrl = route('storefront.products.show', $product); @endphp<article class="product-card"><div class="product-card__image"><a href="{{ $productUrl }}" aria-label="Xem {{ $name }}"><img src="{{ $imageUrl }}" alt="{{ $name }}" loading="lazy"></a>@if($discount > 0)<span class="badge">Ưu đãi</span>@endif<button class="heart" aria-label="Thêm {{ $name }} vào yêu thích">♡</button><div class="product-card__actions"><a href="{{ $productUrl }}">Xem chi tiết</a></div></div><div class="product-card__info"><p>{{ $category }}</p><h3><a href="{{ $productUrl }}">{{ $name }}</a></h3><strong>{{ $price !== null ? number_format((float) $price, 0, ',', '.') . 'đ' : 'Liên hệ' }}</strong></div></article>@endforeach</div>@endif</div></section>

<section class="trust"><div class="container trust__grid"><div><b>100%</b><span>Chứng nhận<br>chất lượng</span></div><div><b>15</b><span>Ngày đổi trả<br>miễn phí</span></div><div><b>∞</b><span>Đổi trọn đời<br>miễn phí</span></div><div><b>01</b><span>Năm bảo hành<br>tận tâm</span></div></div></section>

@php $newestCollection = collect($newestProducts ?? []); @endphp
<section class="section products products--new"><div class="container"><div class="section-heading section-heading--row"><div><p class="eyebrow">Vừa thêm vào thư viện</p><h2>Mẫu mới <em>cập nhật</em></h2></div><a class="text-link" href="#categories">Khám phá danh mục ↗</a></div>@if($newestCollection->isEmpty())<div class="empty-state"><span class="empty-state__mark">◌</span><p class="eyebrow">Chưa có cập nhật mới</p><h3>Mẫu CNC mới sẽ sớm ra mắt</h3><p>Quay lại trong ít ngày tới để xem các thiết kế vừa được bổ sung.</p></div>@else<div class="product-grid product-grid--four">@foreach($newestCollection as $product)@php $image = data_get($product, 'image'); $name = data_get($product, 'name', 'Mẫu thiết kế CNC'); $price = data_get($product, 'price'); $imageUrl = $image ? (str_starts_with($image, 'http') ? $image : asset('storage/'.$image)) : asset('assets/storefront/prod-nislconuat.jpg'); $productUrl = route('storefront.products.show', $product); @endphp<article class="product-card"><div class="product-card__image"><a href="{{ $productUrl }}" aria-label="Xem {{ $name }}"><img src="{{ $imageUrl }}" alt="{{ $name }}" loading="lazy"></a><span class="badge">Mới</span><button class="heart" aria-label="Thêm {{ $name }} vào yêu thích">♡</button><div class="product-card__actions"><a href="{{ $productUrl }}">Xem chi tiết</a></div></div><div class="product-card__info"><p>{{ data_get($product, 'category.name', 'Thiết kế CNC') }}</p><h3><a href="{{ $productUrl }}">{{ $name }}</a></h3><strong>{{ $price !== null ? number_format((float) $price, 0, ',', '.') . 'đ' : 'Liên hệ' }}</strong></div></article>@endforeach</div>@endif</div></section>

<section class="stories section" id="stories"><div class="container"><div class="section-heading"><p class="eyebrow">Lời thì thầm của khách hàng</p><h2>Câu chuyện <em>từ bạn</em></h2></div><div class="stories-grid">
    @foreach([['"Mẫu CNC chuẩn tỉ lệ, mở trên ArtCAM gần như không cần chỉnh lại. Xưởng tiết kiệm được rất nhiều thời gian dựng file."','Anh Minh · Xưởng mộc Bình Dương'],
    ['"File rõ layer, đường dao sạch và có kích thước đầy đủ. Tôi đưa vào máy chạy thử ổn ngay từ lần đầu."','Anh Quân · Cơ khí Hà Nội'],
    ['"Thư viện có nhiều mẫu phù hợp khách đặt hàng. Tìm đúng thiết kế nhanh hơn nên việc chốt đơn ở xưởng cũng thuận lợi hơn."','Anh Thành · Xưởng nội thất Đà Nẵng'],
    ['"Khi cần đổi tỉ lệ chi tiết, bên hỗ trợ hướng dẫn rất đúng trọng tâm kỹ thuật. Làm theo vài bước là xử lý được."','Anh Hùng · Xưởng CNC TP. Hồ Chí Minh']] as $story)<blockquote><span>"</span><p>{{ $story[0] }}</p><footer>{{ $story[1] }} <b>★★★★★</b></footer></blockquote>@endforeach</div></div></section>

<section class="cnc-details" aria-labelledby="cnc-details-title">
    <div class="container">
        <div class="section-heading">
            <p class="eyebrow">Khám phá hoa văn CNC</p>
            <h2 id="cnc-details-title">Chi tiết làm nên <em>vẻ đẹp</em></h2>
            <p>Từ hoa lá mềm mại đến linh vật truyền thống, khám phá những đường nét tạo nên dấu ấn cho từng mẫu thiết kế.</p>
        </div>
        <div class="cnc-details__grid">
            <a class="cnc-details__card" href="#categories">
                <div class="cnc-details__image">
                    <img src="{{ asset('assets/storefront/hero-cnc-hoanh-phi.jpg') }}" alt="Chi tiết hoa lá và chim phượng trên mẫu hoành phi CNC" width="2560" height="1126" loading="lazy" decoding="async">
                </div>
                <div class="cnc-details__copy">
                    <span class="eyebrow">01 · Hoa lá & chim phượng</span>
                    <h3>Đường nét mềm mại <span aria-hidden="true">↗</span></h3>
                    <p>Hoa lá đan xen cùng chim phượng, tạo điểm nhấn cho các mẫu hoành phi và trang trí.</p>
                </div>
            </a>
            <a class="cnc-details__card" href="#categories">
                <div class="cnc-details__image">
                    <img src="{{ asset('assets/storefront/hero-cnc-rong-nghe.jpg') }}" alt="Chi tiết rồng và nghê trong mẫu thiết kế CNC truyền thống" width="2560" height="1224" loading="lazy" decoding="async">
                </div>
                <div class="cnc-details__copy">
                    <span class="eyebrow">02 · Linh vật truyền thống</span>
                    <h3>Dấu ấn rồng nghê <span aria-hidden="true">↗</span></h3>
                    <p>Những hình khối sinh động và họa tiết uyển chuyển mang đậm nét trang trí truyền thống.</p>
                </div>
            </a>
            <a class="cnc-details__card" href="#categories">
                <div class="cnc-details__image">
                    <img src="{{ asset('assets/storefront/hero-cnc-hoa-van.jpg') }}" alt="Chi tiết hoa văn đối xứng và khung trang trí trên mẫu CNC cổ điển" width="2560" height="1658" loading="lazy" decoding="async">
                </div>
                <div class="cnc-details__copy">
                    <span class="eyebrow">03 · Hoa văn cổ điển</span>
                    <h3>Vẻ đẹp cân xứng <span aria-hidden="true">↗</span></h3>
                    <p>Bố cục đối xứng cùng đường viền uốn lượn gợi ý cho các mẫu khung và tấm trang trí.</p>
                </div>
            </a>
        </div>
        <div class="cnc-details__action">
            <a class="button button--dark" href="#categories">Khám phá danh mục CNC <span aria-hidden="true">↗</span></a>
        </div>
    </div>
</section>
<section class="services"><div class="container services__grid"><div><b>✧</b><span><strong>Miễn phí vận chuyển</strong>Cho đơn từ 2.000.000đ</span></div><div><b>◈</b><span><strong>Thanh toán linh hoạt</strong>An tâm với mọi lựa chọn</span></div><div><b>↻</b><span><strong>Đổi trả dễ dàng</strong>Trong vòng 15 ngày</span></div><div><b>♧</b><span><strong>Hỗ trợ tận tâm</strong>Luôn sẵn sàng lắng nghe</span></div></div></section>
<section class="newsletter" id="newsletter"><div class="container newsletter__inner"><div><p class="eyebrow">Lumière journal</p><h2>Đăng ký nhận <em>tin mới</em></h2><p>Đừng bỏ lỡ những câu chuyện đẹp và ưu đãi dành riêng cho bạn.</p></div><form><input type="email" placeholder="Địa chỉ email của bạn" aria-label="Địa chỉ email"><button class="button button--light">Đăng ký <span>↗</span></button></form></div></section>
</main>
<footer class="footer"><div class="container footer__grid"><div class="footer__brand"><div class="footer__logo-panel"><img src="{{ asset('assets/images/logo-duy-hoang-gold-brown.svg') }}" alt="Duy Hoàng CNC"></div><p>Trang sức kể câu chuyện riêng của bạn.</p><address>28 Lê Thái Tổ, Hoàn Kiếm<br>Hà Nội, Việt Nam</address><div class="social">Instagram &nbsp; Facebook &nbsp; Pinterest</div></div><div><h4>Công ty</h4><a href="#">Về Lumière</a><a href="#">Câu chuyện thương hiệu</a><a href="#">Tuyển dụng</a><a href="#">Liên hệ</a></div><div><h4>Hỗ trợ</h4><a href="#">Hướng dẫn mua hàng</a><a href="#">Chính sách đổi trả</a><a href="#">Bảo hành</a><a href="#">Câu hỏi thường gặp</a></div><div><h4>Theo dõi</h4><a href="#">Instagram</a><a href="#">Facebook</a><a href="#">Pinterest</a><a href="#">Zalo</a></div></div><div class="container footer__bottom"><span>© 2024 Duy Hoàng - Kho mẫu CNC. Thủ công với yêu thương.</span><span>Điều khoản &nbsp; Chính sách bảo mật</span></div></footer>
@include('partials.storefront-contact')
@endsection
