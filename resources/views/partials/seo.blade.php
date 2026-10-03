@php
    $seo = \App\Support\StorefrontSeo::make(
        request(),
        $__env->yieldContent('title', 'khomau3d - Kho mẫu CNC — Vẻ đẹp lưu dấu'),
        $product ?? null,
        $category ?? null,
        $search ?? '',
    );
@endphp
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['noindex'] ? 'noindex,follow' : 'index,follow' }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta property="og:site_name" content="khomau3d">
<meta property="og:locale" content="vi_VN">
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:image" content="{{ $seo['image'] }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">
<script type="application/ld+json">{!! $seo['json'] !!}</script>
