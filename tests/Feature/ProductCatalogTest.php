<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_updates_persist_featured_input(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $category = Category::query()->create([
            'name' => 'Trang sức',
            'slug' => 'trang-suc',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Nhẫn bạc',
            'sku' => 'RING-001',
            'price' => 100,
            'quantity' => 5,
            'status' => 'active',
        ]);

        $response = $this->put(route('products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Nhẫn bạc nổi bật',
            'sku' => $product->sku,
            'price' => 120,
            'tax' => 0,
            'discount' => 0,
            'quantity' => 5,
            'minimum_quantity' => 0,
            'unit' => 'pcs',
            'status' => 'active',
            'is_featured' => true,
        ]);

        $response->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nhẫn bạc nổi bật',
            'is_featured' => true,
        ]);
        $this->assertTrue($product->fresh()->is_featured);

        $response = $this->put(route('products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Nhẫn bạc nổi bật',
            'sku' => $product->sku,
            'price' => 120,
            'tax' => 0,
            'discount' => 0,
            'quantity' => 5,
            'minimum_quantity' => 0,
            'unit' => 'pcs',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_featured' => false,
        ]);
        $this->assertFalse($product->fresh()->is_featured);
    }

    public function test_home_exposes_active_categories_and_visible_newest_products(): void
    {
        $activeCategory = Category::query()->create([
            'name' => 'Trang sức',
            'slug' => 'trang-suc-home',
            'image' => 'categories/trang-suc-home.jpg',
            'status' => 'active',
        ]);
        $inactiveCategory = Category::query()->create([
            'name' => 'Bộ sưu tập ẩn',
            'slug' => 'bo-suu-tap-an-home',
            'status' => 'inactive',
        ]);
        $featured = Product::query()->create([
            'category_id' => $activeCategory->id,
            'name' => 'Sản phẩm nổi bật',
            'sku' => 'HOME-001',
            'price' => 100,
            'quantity' => 1,
            'status' => 'active',
            'is_featured' => true,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        $newest = Product::query()->create([
            'category_id' => $activeCategory->id,
            'name' => 'Sản phẩm mới',
            'sku' => 'HOME-002',
            'price' => 100,
            'quantity' => 1,
            'status' => 'active',
            'is_featured' => false,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Product::query()->create([
            'category_id' => $activeCategory->id,
            'name' => 'Sản phẩm không hoạt động',
            'sku' => 'HOME-003',
            'price' => 100,
            'quantity' => 1,
            'status' => 'inactive',
            'is_featured' => true,
        ]);
        Product::query()->create([
            'category_id' => $inactiveCategory->id,
            'name' => 'Sản phẩm thuộc danh mục ẩn',
            'sku' => 'HOME-004',
            'price' => 100,
            'quantity' => 1,
            'status' => 'active',
            'is_featured' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('storage/categories/trang-suc-home.jpg', false)
            ->assertViewHas('categories', function ($categories) use ($activeCategory): bool {
                return $categories->modelKeys() === [$activeCategory->id]
                    && $categories->first()->products_count == 2;
            })
            ->assertViewHas('newestProducts', function ($products) use ($newest, $featured): bool {
                return $products->modelKeys() === [$newest->id, $featured->id]
                    && $products->every(fn (Product $product): bool => $product->relationLoaded('category'));
            });
    }

    public function test_home_exposes_categories_with_visible_products(): void
    {
        $category = Category::query()->create([
            'name' => 'Trang sức',
            'slug' => 'trang-suc-featured-only',
            'status' => 'active',
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm nổi bật',
            'sku' => 'HOME-005',
            'price' => 100,
            'quantity' => 1,
            'status' => 'active',
            'is_featured' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertViewHas('categories', fn ($categories) => $categories->modelKeys() === [$category->id]
                && $categories->first()->products_count === 1);
    }

    public function test_public_product_detail_displays_active_product_with_category(): void
    {
        $category = Category::query()->create([
            'name' => 'Trang sức',
            'slug' => 'trang-suc-detail',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Nhẫn bạc',
            'sku' => 'DETAIL-001',
            'price' => 100,
            'quantity' => 1,
            'status' => 'active',
        ]);

        $response = $this->get(route('storefront.products.show', $product));

        $response->assertOk()
            ->assertViewIs('products.detail')
            ->assertViewHas('product', function (Product $viewProduct) use ($product, $category): bool {
                return $viewProduct->is($product)
                    && $viewProduct->relationLoaded('category')
                    && $viewProduct->category->is($category);
            });
    }

    public function test_public_product_detail_returns_not_found_for_inactive_product(): void
    {
        $category = Category::query()->create([
            'name' => 'Trang sức',
            'slug' => 'trang-suc-inactive-detail',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Nhẫn bạc ẩn',
            'sku' => 'DETAIL-002',
            'price' => 100,
            'quantity' => 1,
            'status' => 'inactive',
        ]);

        $response = $this->get(route('storefront.products.show', $product));

        $response->assertNotFound();
    }

    public function test_public_category_lists_only_its_active_products_with_category_loaded(): void
    {
        $category = Category::query()->create([
            'name' => 'Trang sức',
            'slug' => 'trang-suc-category',
            'status' => 'active',
        ]);
        $otherCategory = Category::query()->create([
            'name' => 'Phụ kiện',
            'slug' => 'phu-kien-category',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mẫu đang bán',
            'sku' => 'CATEGORY-001',
            'status' => 'active',
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mẫu đã ẩn',
            'sku' => 'CATEGORY-002',
            'status' => 'inactive',
        ]);
        Product::query()->create([
            'category_id' => $otherCategory->id,
            'name' => 'Mẫu danh mục khác',
            'sku' => 'CATEGORY-003',
            'status' => 'active',
        ]);

        $response = $this->get(route('storefront.categories.show', ['category' => $category->slug]));

        $response->assertOk()
            ->assertViewIs('categories.show')
            ->assertViewHas('category', fn (Category $viewCategory): bool => $viewCategory->is($category))
            ->assertViewHas('products', function ($products) use ($product, $category): bool {
                return $products->total() === 1
                    && $products->modelKeys() === [$product->id]
                    && $products->first()->relationLoaded('category')
                    && $products->first()->category->is($category);
            });
    }

    public function test_public_category_filters_active_products_by_price_bounds(): void
    {
        $category = Category::query()->create([
            'name' => 'Trang sức',
            'slug' => 'trang-suc-price-filter',
            'status' => 'active',
        ]);
        $otherCategory = Category::query()->create([
            'name' => 'Phụ kiện',
            'slug' => 'phu-kien-price-filter',
            'status' => 'active',
        ]);
        $matchingProduct = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mẫu trong khoảng giá',
            'sku' => 'CATEGORY-PRICE-001',
            'price' => 25,
            'status' => 'active',
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mẫu dưới khoảng giá',
            'sku' => 'CATEGORY-PRICE-002',
            'price' => 10,
            'status' => 'active',
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mẫu trên khoảng giá',
            'sku' => 'CATEGORY-PRICE-003',
            'price' => 40,
            'status' => 'active',
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mẫu ẩn trong khoảng giá',
            'sku' => 'CATEGORY-PRICE-004',
            'price' => 25,
            'status' => 'inactive',
        ]);
        Product::query()->create([
            'category_id' => $otherCategory->id,
            'name' => 'Mẫu danh mục khác trong khoảng giá',
            'sku' => 'CATEGORY-PRICE-005',
            'price' => 25,
            'status' => 'active',
        ]);

        $response = $this->get(route('storefront.categories.show', [
            'category' => $category->slug,
            'min_price' => 20,
            'max_price' => 30,
        ]));

        $response->assertOk()
            ->assertViewHas('products', function ($products) use ($matchingProduct): bool {
                return $products->total() === 1
                    && $products->modelKeys() === [$matchingProduct->id];
            });
    }

    public function test_public_category_supports_each_product_sort_order(): void
    {
        $category = Category::query()->create([
            'name' => 'Trang sức',
            'slug' => 'trang-suc-sort-filter',
            'status' => 'active',
        ]);
        $oldest = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mẫu đắt nhất',
            'sku' => 'CATEGORY-SORT-001',
            'price' => 30,
            'status' => 'active',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
        $middle = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mẫu giá giữa',
            'sku' => 'CATEGORY-SORT-002',
            'price' => 10,
            'status' => 'active',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        $newest = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Mẫu rẻ nhất',
            'sku' => 'CATEGORY-SORT-003',
            'price' => 20,
            'status' => 'active',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $oldest->forceFill([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ])->saveQuietly();
        $middle->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();
        $newest->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->saveQuietly();

        foreach ([
            'newest' => [$newest, $middle, $oldest],
            'price_asc' => [$middle, $newest, $oldest],
            'price_desc' => [$oldest, $newest, $middle],
        ] as $sort => $expectedProducts) {
            $response = $this->get(route('storefront.categories.show', [
                'category' => $category->slug,
                'sort' => $sort,
            ]));

            $response->assertOk()
                ->assertViewHas('products', function ($products) use ($expectedProducts): bool {
                    return $products->modelKeys() === collect($expectedProducts)->pluck('id')->all();
                });
        }
    }

    public function test_public_category_rejects_invalid_filter_input(): void
    {
        $category = Category::query()->create([
            'name' => 'Trang sức',
            'slug' => 'trang-suc-invalid-filter',
            'status' => 'active',
        ]);
        $url = route('storefront.categories.show', ['category' => $category->slug]);

        $this->get($url . '?min_price=-1')
            ->assertRedirect()
            ->assertSessionHasErrors('min_price');
        $this->get($url . '?max_price=9&min_price=10')
            ->assertRedirect()
            ->assertSessionHasErrors('max_price');
        $this->get($url . '?sort=oldest')
            ->assertRedirect()
            ->assertSessionHasErrors('sort');
    }

    public function test_public_category_returns_not_found_for_inactive_and_unknown_categories(): void
    {
        $inactiveCategory = Category::query()->create([
            'name' => 'Danh mục ẩn',
            'slug' => 'danh-muc-an-category',
            'status' => 'inactive',
        ]);

        $this->get(route('storefront.categories.show', ['category' => $inactiveCategory->slug]))
            ->assertNotFound();
        $this->get(route('storefront.categories.show', ['category' => 'khong-ton-tai-category']))
            ->assertNotFound();
    }

    public function test_public_category_paginates_twelve_products_and_preserves_query_strings(): void
    {
        $category = Category::query()->create([
            'name' => 'Bộ sưu tập',
            'slug' => 'bo-suu-tap-category',
            'status' => 'active',
        ]);

        foreach (range(1, 13) as $number) {
            Product::query()->create([
                'category_id' => $category->id,
                'name' => "Mẫu {$number}",
                'sku' => "CATEGORY-PAGE-{$number}",
                'price' => $number,
                'status' => 'active',
                'created_at' => now()->subMinutes($number),
                'updated_at' => now()->subMinutes($number),
            ]);
        }

        $response = $this->get(route('storefront.categories.show', $category) . '?min_price=0&max_price=100&sort=price_desc&page=2&view=grid');

        $response->assertOk()
            ->assertViewHas('products', function ($products): bool {
                return $products->perPage() === 12
                    && $products->currentPage() === 2
                    && $products->total() === 13
                    && $products->count() === 1
                    && $products->url(2) !== ''
                    && str_contains($products->url(2), 'min_price=0')
                    && str_contains($products->url(2), 'max_price=100')
                    && str_contains($products->url(2), 'sort=price_desc')
                    && str_contains($products->url(2), 'view=grid');
            });
    }
}
