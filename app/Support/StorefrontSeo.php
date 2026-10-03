<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StorefrontSeo
{
    public static function make(Request $request, string $title, mixed $product = null, mixed $category = null, string $search = ''): array
    {
        $route = $request->route()->getName();
        // Listing loops leave a $product variable behind; detail also replaces $category with a name.
        $product = $route === 'storefront.products.show' && $product instanceof Product ? $product : null;
        $category = $product ? $product->category : ($route === 'storefront.categories.show' && $category instanceof Category ? $category : null);
        $parameters = [];
        $noindex = false;
        $description = 'khomau3d — khám phá mẫu thiết kế CNC, hoa văn, nội thất và phù điêu. Tìm mẫu theo tên hoặc mã sản phẩm.';
        $image = self::image(null);
        // Inline Blade sections are already escaped; decode once before escaping at output.
        $title = self::whitespace(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($product) {
            $parameters['product'] = $product->getRouteKey();
            $description = self::text($product->description) ?: 'Khám phá mẫu '.$product->name.($category ? ' thuộc danh mục '.$category->name : '').' tại khomau3d.';
            $image = self::image($product->image);
            $noindex = $category?->status !== 'active';
        } elseif ($category) {
            $parameters['category'] = $category->slug;
            $description = self::text($category->description) ?: 'Khám phá các mẫu '.$category->name.' tại khomau3d — Kho mẫu thiết kế CNC.';
            $image = self::image($category->image);
            foreach (['min_price', 'max_price'] as $key) {
                $value = $request->query($key);
                if (is_scalar($value) && is_numeric($value) && is_finite((float) $value)) {
                    $parameters[$key] = (string) $value;
                }
            }
            if (in_array($request->query('sort'), ['newest', 'price_asc', 'price_desc'], true)) {
                $parameters['sort'] = $request->query('sort');
            }
            $noindex = count($parameters) > 1;
        } elseif ($route === 'storefront.products.index') {
            // Use the validated controller value without changing the search's literal meaning.
            if ($search !== '') {
                $parameters['q'] = $search;
                $noindex = true;
                $description = 'Tìm mẫu CNC theo tên hoặc mã sản phẩm: '.$search.' tại khomau3d.';
            } else {
                $description = 'Khám phá tất cả mẫu thiết kế CNC tại khomau3d. Tra cứu mẫu theo tên hoặc mã sản phẩm.';
            }
        }

        if (in_array($route, ['storefront.products.index', 'storefront.categories.show'], true)) {
            $page = filter_var($request->query('page'), FILTER_VALIDATE_INT);
            if ($page !== false && $page > 1) {
                $parameters['page'] = $page;
                $title .= ' — Trang '.$page;
            }
        }

        $canonical = route($route, $parameters);
        $description = Str::limit(self::text($description), 160, '…');
        $home = route('home');
        $graph = [
            ['@type' => 'Organization', '@id' => $home.'#organization', 'name' => 'khomau3d', 'url' => $home, 'telephone' => '0869252228'],
            ['@type' => 'WebSite', '@id' => $home.'#website', 'name' => 'khomau3d', 'url' => $home, 'publisher' => ['@id' => $home.'#organization']],
        ];

        if ($category || $product) {
            $crumbs = [['name' => 'Trang chủ', 'item' => $home]];
            if ($category && $category->status === 'active') {
                $crumbs[] = ['name' => $category->name, 'item' => route('storefront.categories.show', ['category' => $category->slug])];
            }
            if ($product) {
                $crumbs[] = ['name' => $product->name, 'item' => $canonical];
            }
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => array_map(
                fn ($crumb, $index) => ['@type' => 'ListItem', 'position' => $index + 1] + $crumb,
                $crumbs, array_keys($crumbs)
            )];
        }

        if ($product) {
            $schema = ['@type' => 'Product', 'name' => $product->name, 'sku' => $product->sku, 'description' => self::text($product->description) ?: $description, 'url' => $canonical];
                        if ($product->image) {
                            $schema['image'] = $image;
                        }
            if ($product->price !== null) {
                // Match the displayed whole-dong price; tax/discount are not applied by the storefront.
                $schema['offers'] = ['@type' => 'Offer', 'url' => $canonical, 'priceCurrency' => 'VND', 'price' => number_format((float) $product->price, 0, '.', '')];
            }
            $graph[] = $schema;
        }

        return compact('title', 'description', 'canonical', 'image', 'noindex') + [
            'type' => $product ? 'product' : 'website',
            'json' => json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR),
        ];
    }

    private static function whitespace(string $value): string
    {
        return trim(preg_replace('/[\s\p{Z}]+/u', ' ', $value));
    }

    private static function text(?string $value): string
    {
        return self::whitespace(strip_tags(html_entity_decode($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private static function image(?string $path): string
    {
        if (! $path) {
            return asset('assets/images/khomau3d-banner.png');
        }

        return preg_match('~^https?://~i', $path) ? $path : asset('storage/'.ltrim($path, '/'));
    }
}
