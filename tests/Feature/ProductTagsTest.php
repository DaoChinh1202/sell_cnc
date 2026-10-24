<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductTagsTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'tags-test'], ['name' => 'Mẫu CNC', 'status' => 'active']);

        return Product::create($attributes + [
            'category_id' => $category->id,
            'name' => 'Mẫu thử',
            'sku' => 'TAG-'.Product::count(),
            'price' => 100,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_normalize_update_and_clear_tags(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $existing = $this->product();
        $payload = [
            'category_id' => $existing->category_id,
            'name' => 'Mẫu mới',
            'price' => 100,
            'status' => 'active',
        ];
        $this->mock(ProductImageService::class)->shouldReceive('storeWatermarked')->once()->andReturn('products/test.webp');
        $this->post(route('products.store'), $payload + [
            'tags' => " Hoa lá tây, cửa đẹp\nHOA LÁ TÂY,  ghế   ăn, ,",
            'image' => UploadedFile::fake()->image('test.png', 320, 240),
        ])->assertSessionHasNoErrors()->assertRedirect(route('inventory'));
        $product = Product::where('name', 'Mẫu mới')->sole();
        $this->assertSame(['hoa lá tây', 'cửa đẹp', 'ghế ăn'], $product->tags);
        foreach ([route('products.show', $product), route('products.edit', $product)] as $url) {
            $this->get($url)->assertOk()->assertSee('hoa lá tây');
        }
        $this->get(route('products.create'))->assertOk()->assertSee('name="tags"', false);

        $payload['sku'] = $product->sku;
        $this->put(route('products.update', $product), $payload)->assertSessionHasNoErrors();
        $this->assertSame(['hoa lá tây', 'cửa đẹp', 'ghế ăn'], $product->fresh()->tags);
        $this->put(route('products.update', $product), $payload + ['tags' => 'Đồ thờ'])->assertSessionHasNoErrors();
        $this->assertSame(['đồ thờ'], $product->fresh()->tags);
        $this->put(route('products.update', $product), $payload + ['tags' => ''])->assertSessionHasNoErrors();
        $this->assertSame([], $product->fresh()->tags);
    }

    public function test_tag_input_limits_are_validated(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = $this->product();
        $payload = $product->only(['category_id', 'name', 'sku', 'price', 'status']);
        foreach ([['bad'], str_repeat('á', 61), implode(',', range(1, 21)), str_repeat('a', 2001)] as $tags) {
            $this->put(route('products.update', $product), $payload + ['tags' => $tags])->assertSessionHasErrors('tags');
        }
        $this->assertNull($product->fresh()->tags);
    }

    public function test_public_tags_are_escaped_and_in_product_structured_data(): void
    {
        $product = $this->product(['tags' => ['hoa lá tây', '<script>alert(1)</script>']]);
        $response = $this->get(route('storefront.products.show', $product))->assertOk()
            ->assertSee('Từ khóa')->assertSee('hoa lá tây')
            ->assertSee(e(route('storefront.products.index', ['tag' => 'hoa lá tây'])), false)
            ->assertDontSee('<script>alert(1)</script>', false);
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $response->getContent(), $match);
        $graph = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR)['@graph'];
        $schema = collect($graph)->firstWhere('@type', 'Product');
        $this->assertSame(implode(', ', $product->tags), $schema['keywords']);

        $product->update(['tags' => []]);
        $this->get(route('storefront.products.show', $product))->assertOk()->assertDontSee('product-tags');
    }

    public function test_tag_filter_matches_exact_tags_and_preserves_pagination_and_search(): void
    {
        foreach (range(1, 17) as $i) {
            $this->product(['tags' => ['hoa lá tây'], 'name' => 'Mẫu hoa '.$i]);
        }
        $this->product(['tags' => ['hoa lá tây đẹp']]);
        $this->product(['tags' => ['hoa lá tây'], 'status' => 'inactive']);
        $hidden = $this->product(['tags' => ['hoa lá tây']]);
        $hidden->category()->associate(Category::create(['name' => 'Ẩn', 'slug' => 'hidden-tags', 'status' => 'inactive']));
        $hidden->save();

        $this->get(route('storefront.products.index', ['tag' => 'hoa lá tây', 'q' => 'Mẫu hoa', 'page' => 2]))
            ->assertOk()->assertViewHas('products', fn ($products) => $products->total() === 17 && $products->count() === 1
                && str_contains($products->url(1), 'tag=') && str_contains($products->url(1), 'q='))
            ->assertSee('name="tag"', false)->assertSee('noindex,follow', false);
        $this->get(route('storefront.products.index', ['tag' => 'không tồn tại']))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->total() === 0);
        foreach ([['tag' => ['bad']], ['tag' => str_repeat('a', 61)]] as $query) {
            $this->get(route('storefront.products.index', $query))->assertSessionHasErrors('tag');
        }
    }
}
