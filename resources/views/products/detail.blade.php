@extends('layouts.storefront')

@php
    $name = $product->name;
    $category = $product->category?->name;
    $image = data_get($product, 'image');
    $imageUrl = is_string($image) && $image !== ''
        ? (str_starts_with($image, 'http') ? $image : asset('storage/' . $image))
        : null;
    $price = data_get($product, 'price');
    $specifications = collect([
        'Định dạng tệp' => $product->file_format,
        'Phần mềm tương thích' => $product->compatible_software,
        'Dung lượng tệp' => $product->file_size,
        'Kích thước thiết kế' => $product->design_dimensions,
    ])->filter(fn ($value) => filled($value));
@endphp

@section('title', $name . ' — khomau3d - Kho mẫu CNC')

@section('content')
<div class="cnc-catalog product-detail-page" data-product-detail>
    @include('partials.catalog-header')
    <main class="product-detail">
        <div class="container">
            <div class="product-detail__browse">
                <a class="cnc-more" href="{{ route('home') }}#categories">Tất cả danh mục <span aria-hidden="true">↗</span></a>
                <form class="cnc-search" method="get" action="{{ route('storefront.products.index') }}" role="search">
                    <label class="sr-only" for="detail-search">Tìm theo tên mẫu hoặc mã sản phẩm</label>
                    <input id="detail-search" type="search" name="q" maxlength="100" placeholder="Tìm kiếm mẫu CNC...">
                    <button type="submit">Tìm mẫu</button>
                </form>
            </div>
            <nav class="product-detail__breadcrumb" aria-label="Đường dẫn">
                <a href="{{ route('home') }}">Trang chủ</a><span aria-hidden="true">/</span>
                <a href="{{ route('storefront.products.index') }}">Kho mẫu</a><span aria-hidden="true">/</span>
                @if($product->category?->status === 'active')
                    <a href="{{ route('storefront.categories.show', $product->category) }}">{{ $category }}</a><span aria-hidden="true">/</span>
                @endif
                <strong aria-current="page">{{ $name }}</strong>
            </nav>

            <div class="product-detail__layout">
                <div class="product-detail__visual">
                    <figure class="product-detail__gallery">
                        @if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $name }}" fetchpriority="high" data-detail-image>@endif
                        <span class="cnc-card__placeholder" data-image-fallback @if($imageUrl) hidden @endif>DH<small>Ảnh đang cập nhật</small></span>
                    </figure>
                    @if($imageUrl)
                        <div class="product-detail__image-tools">
                            <span>Ảnh tham khảo thiết kế</span>
                            <button type="button" data-image-open aria-haspopup="dialog" aria-controls="product-image-dialog" hidden>Phóng to ảnh <span aria-hidden="true">↗</span></button>
                        </div>
                        <dialog id="product-image-dialog" class="product-detail__dialog" aria-labelledby="product-image-title">
                            <div class="product-detail__dialog-heading">
                                <h2 id="product-image-title">{{ $name }}</h2>
                                <button type="button" data-image-close autofocus aria-label="Đóng ảnh phóng to">Đóng <span aria-hidden="true">×</span></button>
                            </div>
                            <div class="product-detail__dialog-image">
                                <img src="{{ $imageUrl }}" alt="Ảnh phóng to: {{ $name }}" loading="lazy" data-detail-image>
                                <span class="cnc-card__placeholder" data-image-fallback hidden>DH<small>Không thể tải ảnh. Vui lòng thử lại sau.</small></span>
                            </div>
                        </dialog>
                    @endif
                </div>

                <section class="product-detail__summary" aria-labelledby="product-title">
                    <p class="cnc-kicker">{{ filled($category) ? $category : 'THƯ VIỆN THIẾT KẾ CNC' }}</p>
                    <h1 id="product-title">{{ $name }}</h1>
                    @if(filled($product->sku))
                        <div class="product-detail__sku">
                            <span>Mã mẫu: <strong>{{ $product->sku }}</strong></span>
                            <button type="button" data-copy-sku="{{ $product->sku }}" hidden>Sao chép mã</button>
                        </div>
                        <p class="product-detail__copy-status" data-copy-status role="status" aria-live="polite" aria-atomic="true"></p>
                    @endif
                    <div class="product-detail__price">
                        <span>Giá mẫu</span>
                        <strong>{{ $price !== null ? number_format((float) $price, 0, ',', '.') . 'đ' : 'Liên hệ' }}</strong>
                    </div>

                    @if($specifications->isNotEmpty())
                        <dl class="product-detail__specifications">
                            @foreach($specifications as $label => $value)
                                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                    @endif

                    <div class="product-detail__download">
                        @if(filled($product->free_download_url))
                            <a class="product-detail__action product-detail__action--primary" href="{{ $product->free_download_url }}" target="_blank" rel="noopener noreferrer">Tải miễn phí <span aria-hidden="true">↗</span></a>
                        @else
                            <a class="product-detail__action product-detail__action--primary" href="https://zalo.me/0869252228" target="_blank" rel="noopener noreferrer">Liên hệ nhận mẫu <span aria-hidden="true">↗</span></a>
                        @endif
                        @if(filled($product->download_note))<p class="product-detail__download-note">{{ $product->download_note }}</p>@endif
                        <p class="product-detail__guidance">{{ filled($product->free_download_url) ? 'Liên kết tải mở trong tab mới. Kiểm tra định dạng và nội dung tệp trước khi sử dụng.' : 'Gửi mã mẫu qua Zalo để hỏi thông tin tệp và cách nhận mẫu.' }}</p>
                    </div>
                    <div class="product-detail__contact-actions">
                        <a class="product-detail__action" href="https://zalo.me/0869252228" target="_blank" rel="noopener noreferrer">Hỏi qua Zalo ↗</a>
                        <a class="product-detail__action" href="tel:0869252228">Gọi 0869 252 228</a>
                    </div>
                    <aside class="product-detail__service" aria-label="Hỗ trợ thiết kế">
                        <strong>Duy Hoang · Hỗ trợ mẫu CNC</strong>
                        <p>Cần chỉnh sửa hoặc tìm mẫu? Gửi mã mẫu, kích thước và yêu cầu thực tế qua Zalo để trao đổi.</p>
                    </aside>
                </section>
            </div>

            @if(filled($product->description))
                <section class="product-detail__information" aria-labelledby="product-description-title">
                    <div><p class="cnc-kicker">VỀ THIẾT KẾ</p><h2 id="product-description-title">Chi tiết mẫu</h2></div>
                    <div class="product-detail__description">{{ $product->description }}</div>
                </section>
            @endif

            @if(!empty($product->tags))
                <section class="product-detail__tags" aria-labelledby="product-tags-title">
                    <h2 id="product-tags-title">Từ khóa</h2>
                    <ul>
                        @foreach($product->tags as $tag)
                            <li><a href="{{ route('storefront.products.index', ['tag' => $tag]) }}">{{ $tag }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
                <section class="product-detail__related cnc-shelf" aria-labelledby="related-products-title">
                    <div class="cnc-shelf__heading">
                        <div><p class="cnc-kicker">KHÁM PHÁ THÊM</p><h2 id="related-products-title">Mẫu liên quan</h2></div>
                        <a class="cnc-more" href="{{ route('storefront.products.index') }}">Xem kho mẫu ↗</a>
                    </div>
                    <div class="cnc-grid">
                        @foreach($relatedProducts as $relatedProduct)
                            @include('partials.catalog-card', ['product' => $relatedProduct])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
    @include('partials.catalog-footer')
</div>
@endsection
