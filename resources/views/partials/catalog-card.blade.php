@php
    $image = $product->image;
    $imageUrl = $image ? (str_starts_with($image, 'http') ? $image : asset('storage/'.$image)) : null;
@endphp
<article class="cnc-card">
    <a class="cnc-card__image" href="{{ route('storefront.products.show', $product) }}" aria-label="Xem {{ $product->name }}">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" loading="lazy" decoding="async" width="600" height="450">
        @else
            <span class="cnc-card__placeholder">DH<small>Ảnh đang cập nhật</small></span>
        @endif
        @if($product->is_featured)<span class="cnc-card__badge">Nổi bật</span>@endif
        <span class="cnc-card__open" aria-hidden="true">↗</span>
    </a>
    <div class="cnc-card__body">
        <p class="cnc-card__category">{{ $product->category?->name }}</p>
        <h3><a href="{{ route('storefront.products.show', $product) }}">{{ $product->name }}</a></h3>
        <div class="cnc-card__meta"><span>Mã: {{ $product->sku }}</span><strong>{{ $product->price !== null ? number_format((float) $product->price, 0, ',', '.').'đ' : 'Liên hệ' }}</strong></div>
    </div>
</article>
