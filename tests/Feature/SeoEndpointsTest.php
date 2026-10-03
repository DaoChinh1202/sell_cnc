<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_contains_only_public_catalog_urls(): void
    {
        $active = Category::query()->create(['name' => 'Active', 'slug' => 'active', 'status' => 'active']);
        $empty = Category::query()->create(['name' => 'Empty', 'slug' => 'empty', 'status' => 'active']);
        $inactive = Category::query()->create(['name' => 'Hidden', 'slug' => 'hidden', 'status' => 'inactive']);

        $visible = $this->product($active, 'VISIBLE', 'active');
        $this->product($active, 'INACTIVE', 'inactive');
        $this->product($active, 'DRAFT', 'draft');
        $this->product($inactive, 'HIDDEN-CATEGORY', 'active');

        $response = $this->get(route('sitemap'));
        $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $this->assertSame([
            route('home'),
            route('storefront.products.index'),
            route('storefront.categories.show', ['category' => $active->slug]),
            route('storefront.categories.show', ['category' => $empty->slug]),
            route('storefront.products.show', ['product' => $visible->id]),
        ], $this->locations($response->getContent()));
        $response->assertDontSee('<lastmod>', false)
            ->assertDontSee('/admin', false)
            ->assertDontSee('?q=', false)
            ->assertDontSee('<html', false);
    }

    public function test_empty_catalog_still_has_home_and_products_index(): void
    {
        $response = $this->get(route('sitemap'))->assertOk();

        $this->assertSame([
            route('home'),
            route('storefront.products.index'),
        ], $this->locations($response->getContent()));
    }

    public function test_sitemap_escapes_xml_and_uses_the_request_origin(): void
    {
        $category = Category::query()->create([
            'name' => 'Special characters',
            'slug' => 'wood&metal',
            'status' => 'active',
        ]);

        $response = $this->get('https://catalog.example.test/sitemap.xml')->assertOk();

        $this->assertSame([
            'https://catalog.example.test',
            'https://catalog.example.test/products',
            'https://catalog.example.test/categories/'.$category->slug,
        ], $this->locations($response->getContent()));
        $response->assertSee('wood&amp;metal', false);
    }

    public function test_robots_is_dynamic_and_leaves_search_pages_crawlable(): void
    {
        // Static files are served before Laravel by the production web server.
        $this->assertFileDoesNotExist(public_path('robots.txt'));

        $response = $this->get('https://catalog.example.test/robots.txt');
        $response->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('Sitemap: https://catalog.example.test/sitemap.xml', false)
            ->assertDontSee('<html', false);

        preg_match_all('/^Disallow:\s*(.*)$/m', $response->getContent(), $matches);
        $this->assertSame(['/admin'], array_map('trim', $matches[1]));
        $this->assertStringContainsString("User-agent: *\n", $response->getContent());
    }

    private function product(Category $category, string $sku, string $status): Product
    {
        return Product::query()->create([
            'category_id' => $category->id,
            'name' => $sku,
            'sku' => $sku,
            'status' => $status,
        ]);
    }

    private function locations(string $content): array
    {
        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml, 'Sitemap must be valid XML.');
        $this->assertSame('urlset', $xml->getName());
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getDocNamespaces()['']);
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return array_map(fn ($loc): string => (string) $loc, $xml->xpath('/s:urlset/s:url/s:loc'));
    }
}
