@php
    echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xml = fn (string $url): string => htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8');
@endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{!! $xml(route('home')) !!}</loc></url>
    <url><loc>{!! $xml(route('storefront.products.index')) !!}</loc></url>
@foreach ($categories as $category)
    <url><loc>{!! $xml(route('storefront.categories.show', ['category' => $category->slug])) !!}</loc></url>
@endforeach
@foreach ($products as $product)
    <url><loc>{!! $xml(route('storefront.products.show', ['product' => $product->getKey()])) !!}</loc></url>
@endforeach
</urlset>
