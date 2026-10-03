<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Support\StorefrontSeo;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StorefrontSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function category(array $attributes = []): Category
    {
        return Category::create(array_merge(['name' => 'Hoa văn CNC', 'slug' => 'hoa-van', 'status' => 'active'], $attributes));
    }

    private function product(Category $category, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id, 'name' => 'Mẫu hoa sen', 'sku' => 'SEN-001',
            'description' => 'Mẫu hoa sen CNC cho nội thất.', 'status' => 'active',
        ], $attributes));
    }

    private function xpath(TestResponse $response): DOMXPath
    {
        $response->assertOk();
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($dom);
    }

    private function value(DOMXPath $xpath, string $expression): string
    {
        return $xpath->evaluate('string('.$expression.')');
    }

    private function graph(DOMXPath $xpath): array
    {
        $json = $this->value($xpath, '//script[@type="application/ld+json"]');
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $decoded['@context']);

        return array_column($decoded['@graph'], null, '@type');
    }

    private function assertMetadata(DOMXPath $xpath, string $canonical, bool $noindex = false): void
    {
        $this->assertSame($canonical, $this->value($xpath, '//link[@rel="canonical"]/@href'));
        $this->assertSame($canonical, $this->value($xpath, '//meta[@property="og:url"]/@content'));
        $this->assertSame($noindex ? 'noindex,follow' : 'index,follow', $this->value($xpath, '//meta[@name="robots"]/@content'));
        $title = $this->value($xpath, '//title');
        $this->assertNotSame('', $title);
        $this->assertSame($title, $this->value($xpath, '//meta[@property="og:title"]/@content'));
        $this->assertSame($title, $this->value($xpath, '//meta[@name="twitter:title"]/@content'));
        $description = $this->value($xpath, '//meta[@name="description"]/@content');
        $this->assertNotSame('', $description);
        $this->assertSame($description, $this->value($xpath, '//meta[@property="og:description"]/@content'));
        $this->assertSame($description, $this->value($xpath, '//meta[@name="twitter:description"]/@content'));
        $this->assertSame(1, $xpath->query('//title')->length);
    }

    public function test_home_has_consistent_metadata_and_real_common_schema(): void
    {
        $this->product($this->category());
        $xpath = $this->xpath($this->get('/?utm_source=test&page=2'));
        $this->assertMetadata($xpath, route('home'));
        $this->assertSame('Kho mẫu CNC 3D, hoa văn và phù điêu | khomau3d', $this->value($xpath, '//title'));
                $this->assertSame(1, $xpath->query('//h1')->length);
                $this->assertSame('Kho mẫu CNC 3D dành cho xưởng điêu khắc', $this->value($xpath, '//h1'));
                $this->assertSame('high', $this->value($xpath, '//img[@class="cnc-intro__art"]/@fetchpriority'));
                $this->assertSame('image/webp', $this->value($xpath, '//picture/source/@type'));
                $this->assertSame('100vw', $this->value($xpath, '//picture/source/@sizes'));
        $this->assertSame(asset('assets/images/khomau3d-banner.png'), $this->value($xpath, '//meta[@property="og:image"]/@content'));
                $this->assertSame(asset('assets/images/khomau3d-banner.png'), $this->value($xpath, '//img[@class="cnc-intro__art"]/@src'));
                $this->assertSame(
                    asset('assets/images/khomau3d-banner-640.webp').' 640w, '.asset('assets/images/khomau3d-banner-1280.webp').' 1280w, '.asset('assets/images/khomau3d-banner-1983.webp').' 1983w',
                    $this->value($xpath, '//picture/source/@srcset'),
                );
        $this->assertSame('summary_large_image', $this->value($xpath, '//meta[@name="twitter:card"]/@content'));
        $graph = $this->graph($xpath);
        $this->assertSame('khomau3d', $graph['Organization']['name']);
        $this->assertSame('0869252228', $graph['Organization']['telephone']);
        $this->assertSame(route('home'), $graph['Organization']['url']);
        $this->assertSame(route('home'), $graph['WebSite']['url']);
        $this->assertArrayNotHasKey('address', $graph['Organization']);
        $this->assertArrayNotHasKey('potentialAction', $graph['WebSite']);
        $this->assertArrayNotHasKey('Product', $graph);
    }

    public function test_login_admin_and_not_found_pages_are_noindex(): void
    {
        $this->get(route('signin'))->assertOk()->assertSee('content="noindex,nofollow"', false);
        $this->get('/missing-seo-page')->assertNotFound()->assertSee('content="noindex,nofollow"', false);
        $admin = \App\Models\User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('content="noindex,nofollow"', false);
    }

    public function test_product_escapes_titles_and_descriptions_and_safely_encodes_real_schema(): void
    {
        $name = 'Sen "đẹp" & </title><script>alert("x")</script>';
        $description = "<b>Hoa sen</b>\n   &amp; nội thất \"đẹp\" </script><script>alert('x')</script> ".str_repeat('mẫu CNC ', 30);
        $category = $this->category();
        $product = $this->product($category, ['name' => $name, 'description' => $description, 'price' => 125000, 'tax' => 10, 'discount' => 50, 'image' => 'products/sen.jpg']);
        $response = $this->get(route('storefront.products.show', ['product' => $product, 'utm_source' => 'test']));
        $xpath = $this->xpath($response);
        $this->assertMetadata($xpath, route('storefront.products.show', $product));
        $this->assertSame($name.' — khomau3d - Kho mẫu CNC', $this->value($xpath, '//title'));
        $this->assertSame(0, $xpath->query('//script[not(@type="application/ld+json") and not(@src)]')->length);
        $response->assertDontSee('</title><script>', false)->assertDontSee('</script><script>', false);
        $metaDescription = $this->value($xpath, '//meta[@name="description"]/@content');
        $this->assertStringStartsWith('Hoa sen & nội thất "đẹp"', $metaDescription);
        $this->assertStringNotContainsString('<', $metaDescription);
        $this->assertDoesNotMatchRegularExpression('/\s{2,}/u', $metaDescription);
        $this->assertLessThanOrEqual(161, mb_strlen($metaDescription));
        $graph = $this->graph($xpath);
        $schema = $graph['Product'];
        $this->assertSame($name, $schema['name']);
        $this->assertSame('SEN-001', $schema['sku']);
        $this->assertSame('125000', $schema['offers']['price']);
        $this->assertSame('VND', $schema['offers']['priceCurrency']);
        $this->assertArrayNotHasKey('availability', $schema['offers']);
        $this->assertArrayNotHasKey('aggregateRating', $schema);
        $this->assertSame(asset('storage/products/sen.jpg'), $schema['image']);
        $this->assertSame($schema['image'], $this->value($xpath, '//meta[@property="og:image"]/@content'));
        $this->assertSame($schema['image'], $this->value($xpath, '//meta[@name="twitter:image"]/@content'));
        $this->assertStringStartsWith('Hoa sen & nội thất "đẹp"', $schema['description']);
        $crumbs = $graph['BreadcrumbList']['itemListElement'];
        $this->assertSame([1, 2, 3], array_column($crumbs, 'position'));
        $this->assertSame(route('storefront.categories.show', ['category' => $category->slug]), $crumbs[1]['item']);
        $this->assertSame($name, $crumbs[2]['name']);
        $this->assertStringContainsString('\\u003C', $this->value($xpath, '//script[@type="application/ld+json"]'));
    }

    public function test_unknown_and_zero_prices_and_inactive_category_are_not_misrepresented(): void
    {
        $product = $this->product($this->category(['status' => 'inactive']));
        $xpath = $this->xpath($this->get(route('storefront.products.show', $product)));
        $this->assertMetadata($xpath, route('storefront.products.show', $product), true);
        $schema = $this->graph($xpath)['Product'];
        $this->assertSame('0', $schema['offers']['price']);
        $this->assertArrayNotHasKey('image', $schema);

        // The current DB requires price (default zero); exercise the UI's null branch in memory.
        $product->price = null;
        $request = Request::create(route('storefront.products.show', $product));
        $request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('storefront.products.show'));
        $seo = StorefrontSeo::make($request, $product->name, $product);
        $graph = array_column(json_decode($seo['json'], true, 512, JSON_THROW_ON_ERROR)['@graph'], null, '@type');
        $this->assertArrayNotHasKey('offers', $graph['Product']);

        $product->update(['price' => 0, 'image' => 'https://images.example.org/sen.jpg']);
        $xpath = $this->xpath($this->get(route('storefront.products.show', $product)));
        $schema = $this->graph($xpath)['Product'];
        $this->assertSame('0', $schema['offers']['price']);
        $this->assertSame('https://images.example.org/sen.jpg', $schema['image']);
    }

    public function test_category_metadata_and_listing_pagination_use_clean_named_route_canonicals(): void
    {
        $category = $this->category(['name' => 'Hoa "đẹp" & <CNC>', 'description' => "<p>Bộ sưu tập</p>\n hoa   văn &amp; CNC", 'image' => 'categories/hoa.jpg']);
        foreach (range(1, 17) as $index) {
            $this->product($category, ['sku' => 'SKU-'.$index]);
        }
        foreach ([['storefront.categories.show', ['category' => $category->slug]], ['storefront.products.index', []]] as [$route, $parameters]) {
            foreach ([1, 2] as $page) {
                $xpath = $this->xpath($this->get(route($route, $parameters + ['page' => $page, 'utm_campaign' => 'test', 'unknown' => 'ignored'])));
                $this->assertMetadata($xpath, route($route, $parameters + ($page > 1 ? ['page' => $page] : [])));
                $this->assertSame($page > 1, str_ends_with($this->value($xpath, '//title'), ' — Trang 2'));
                $graph = $this->graph($xpath);
                $this->assertArrayNotHasKey('Product', $graph);
                if ($route === 'storefront.categories.show') {
                    $this->assertSame('Bộ sưu tập hoa văn & CNC', $this->value($xpath, '//meta[@name="description"]/@content'));
                    $this->assertStringStartsWith($category->name, $this->value($xpath, '//title'));
                    $this->assertSame(asset('storage/categories/hoa.jpg'), $this->value($xpath, '//meta[@property="og:image"]/@content'));
                    $this->assertSame($category->name, $graph['BreadcrumbList']['itemListElement'][1]['name']);
                }
            }
        }
    }

    public function test_search_and_filters_are_noindex_with_meaningful_self_canonicals(): void
    {
        $category = $this->category();
        foreach ([['min_price' => '0'], ['max_price' => '250000'], ['sort' => 'newest'], ['sort' => 'price_asc'], ['min_price' => '100', 'max_price' => '200000', 'sort' => 'price_desc', 'page' => 2]] as $filters) {
            $parameters = ['category' => $category->slug] + $filters;
            $xpath = $this->xpath($this->get(route('storefront.categories.show', $parameters + ['utm_source' => 'test', 'q' => 'ignored'])));
            $this->assertMetadata($xpath, route('storefront.categories.show', $parameters), true);
        }
        $xpath = $this->xpath($this->get(route('storefront.categories.show', ['category' => $category->slug, 'min_price' => '', 'sort' => '', 'page' => 'invalid'])));
        $this->assertMetadata($xpath, route('storefront.categories.show', ['category' => $category->slug]));
        foreach (['hoa sen', '</title><script>alert(1)</script>'] as $term) {
            $xpath = $this->xpath($this->get(route('storefront.products.index', ['q' => '  '.$term.'  ', 'page' => 2, 'utm_source' => 'test', 'sort' => 'newest'])));
            $this->assertMetadata($xpath, route('storefront.products.index', ['q' => $term, 'page' => 2]), true);
            $this->assertStringContainsString($term, $this->value($xpath, '//title'));
        }
        $xpath = $this->xpath($this->get(route('storefront.products.index', ['q' => '  ', 'page' => 1])));
        $this->assertMetadata($xpath, route('storefront.products.index'));
    }
}
