<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSearchTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, string $status = 'active'): Category
    {
        return Category::create(['name' => $name, 'slug' => $name, 'status' => $status]);
    }

    private function product(Category $category, string $sku, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Hoa văn '.$sku,
            'sku' => $sku,
            'status' => 'active',
        ], $attributes));
    }

    public function test_search_matches_names_and_codes_and_hides_unpublished_products(): void
    {
        $category = $this->category('hoa-van');
        $visible = $this->product($category, 'CNC-001');
        $this->product($category, 'CNC-002', ['status' => 'inactive']);
        $this->product($this->category('an', 'inactive'), 'CNC-003');
        $this->product($category, 'OTHER', ['name' => 'Mẫu ghế']);

        foreach (['Hoa văn', 'CNC-001'] as $term) {
            $this->get(route('storefront.products.index', ['q' => $term]))->assertOk()
                ->assertViewHas('products', fn ($products) => $products->modelKeys() === [$visible->id]);
        }
        $this->get(route('storefront.products.index', ['q' => 'không tồn tại']))
            ->assertOk()->assertSee('Chưa tìm thấy mẫu phù hợp');
    }

    public function test_search_treats_wildcards_literally_and_preserves_query_in_pagination(): void
    {
        $category = $this->category('hoa');
        $special = $this->product($category, 'CNC_%!');
        foreach (range(1, 17) as $index) {
            $this->product($category, 'CNC-'.$index);
        }
        $this->get(route('storefront.products.index', ['q' => '_%!']))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->modelKeys() === [$special->id]);
        $this->get(route('storefront.products.index', ['q' => 'CNC-', 'page' => 2]))->assertOk()
            ->assertSee('class="storefront-pagination__list"', false)
            ->assertSee('Hiển thị 17–17 trong 17 mẫu')
            ->assertSee('aria-current="page" aria-label="Trang 2"', false)
            ->assertSee('aria-disabled="true" aria-label="Trang sau"', false)
            ->assertSee('q=CNC-&amp;page=1', false)
            ->assertViewHas('products', fn ($products) => $products->count() === 1
                && $products->total() === 17 && str_contains($products->url(1), 'q=CNC-'));
        $this->get(route('storefront.products.index'))->assertOk()
            ->assertSee('Hiển thị 1–16 trong 18 mẫu')
            ->assertSee('aria-disabled="true" aria-label="Trang trước"', false)
            ->assertSee('rel="next" aria-label="Trang sau"', false)
            ->assertViewHas('products', fn ($products) => $products->total() === 18);
        $this->get(route('storefront.categories.show', $category))->assertOk()
            ->assertSee('class="storefront-pagination__list"', false)
            ->assertSee('Hiển thị 1–12 trong 18 mẫu');
    }

    public function test_home_exposes_active_categories_with_visible_product_counts(): void
    {
        $this->category('empty');
        foreach (['chairs', 'decor'] as $slug) {
            $category = $this->category($slug);
            foreach (range(1, 10) as $index) {
                $this->product($category, $slug.'-'.$index);
            }
            $this->product($category, $slug.'-hidden', ['status' => 'inactive']);
        }
        $this->get('/')->assertOk()->assertDontSee('hero__slides', false)
            ->assertSee('name="q"', false)
            ->assertSeeInOrder(['cnc-intro__art', 'cnc-home-search', 'id="products"'], false)
            ->assertSee('Tìm mẫu cho xưởng của bạn')
            ->assertSee('khomau3d')
            ->assertDontSee('Duy Hoàng')
            ->assertDontSee('DUY HOÀNG')
            ->assertDontSee('cnc-intro__search', false)
            ->assertViewHas('categories', fn ($categories) => $categories->count() === 3
                && $categories->firstWhere('slug', 'chairs')->products_count === 10
                && $categories->firstWhere('slug', 'decor')->products_count === 10
                && $categories->firstWhere('slug', 'empty')->products_count === 0)
            ->assertViewHas('newestProducts', fn ($products) => $products->count() === 4);
    }

    public function test_search_rejects_invalid_input_and_escapes_reflected_text(): void
    {
        foreach ([['q' => ['invalid']], ['q' => str_repeat('a', 101)]] as $query) {
            $this->from('/')->get(route('storefront.products.index', $query))
                ->assertRedirect('/')->assertSessionHasErrors('q');
        }
        $this->get(route('storefront.products.index', ['q' => '<script>alert(1)</script>']))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
