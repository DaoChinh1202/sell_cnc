<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'detail-test'], ['name' => 'Mẫu CNC', 'status' => 'active']);

        return Product::create($attributes + [
            'category_id' => $category->id, 'name' => 'Mẫu thử',
            'sku' => 'DETAIL-'.Product::count(), 'price' => 100, 'status' => 'active',
        ]);
    }

    private function metadata(): array
    {
        return [
            'file_format' => '.jdp, .stl',
            'compatible_software' => 'JDPAINT',
            'file_size' => '25 MB',
            'design_dimensions' => '1200 × 600 mm',
            'download_note' => 'Link tải bản dùng thử, liên hệ để nhận bản đầy đủ.',
        ];
    }

    public function test_admin_can_create_edit_and_clear_optional_metadata(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = $this->product();
        $payload = $product->only(['category_id', 'name', 'price', 'status']);
        $payload['name'] = 'Mẫu thông tin file';
        $this->mock(ProductImageService::class)->shouldReceive('storeWatermarked')->once()->andReturn('products/detail.webp');
        $this->post(route('products.store'), $payload + $this->metadata() + [
            'image' => UploadedFile::fake()->image('detail.png', 320, 240),
        ])->assertSessionHasNoErrors()->assertRedirect(route('inventory'));
        $created = Product::where('name', $payload['name'])->sole();
        $this->assertSame($this->metadata(), $created->only(array_keys($this->metadata())));
        foreach ([route('products.edit', $created), route('products.show', $created), route('storefront.products.show', $created)] as $url) {
            $response = $this->get($url)->assertOk();
            foreach ($this->metadata() as $value) {
                $response->assertSee($value);
            }
        }
        foreach ([route('products.create'), route('products.edit', $created)] as $url) {
            $response = $this->get($url)->assertOk();
            foreach (array_keys($this->metadata()) as $field) {
                $response->assertSee('name="'.$field.'"', false);
            }
        }
        $payload['sku'] = $created->sku;
        $this->put(route('products.update', $created), $payload + ['file_size' => '30 MB'])->assertSessionHasNoErrors();
        $this->assertSame('30 MB', $created->fresh()->file_size);
        $this->assertSame('JDPAINT', $created->fresh()->compatible_software);
        $this->put(route('products.update', $created), $payload + array_fill_keys(array_keys($this->metadata()), ''))->assertSessionHasNoErrors();
        $this->assertSame(array_fill_keys(array_keys($this->metadata()), null), $created->fresh()->only(array_keys($this->metadata())));
    }

    public function test_invalid_metadata_is_rejected_on_create_and_update(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = $this->product();
        $payload = $product->only(['category_id', 'name', 'sku', 'price', 'status']);
        $invalid = ['file_format' => str_repeat('a', 101), 'file_size' => ['invalid'],
            'compatible_software' => str_repeat('a', 256), 'design_dimensions' => str_repeat('a', 256),
            'download_note' => str_repeat('a', 501)];
        $this->put(route('products.update', $product), $payload + $invalid)->assertSessionHasErrors(array_keys($invalid));
        $this->post(route('products.store'), $payload + $invalid + ['image' => UploadedFile::fake()->image('detail.png', 320, 240)])
            ->assertSessionHasErrors(array_keys($invalid));
        $this->assertDatabaseCount('products', 1);
    }

    public function test_detail_actions_escape_content_and_hide_empty_sections(): void
    {
        $product = $this->product();
        $this->get(route('storefront.products.show', $product))->assertOk()
            ->assertSee('Liên hệ nhận mẫu')->assertSee('tel:0869252228', false)
            ->assertDontSee('Tải miễn phí')->assertDontSee('product-detail__specifications', false)
            ->assertDontSee('related-products-title', false);
        $product->update($this->metadata() + ['free_download_url' => 'https://drive.google.com/file/d/example/view',
            'description' => "Dòng đầu\n<script>alert(1)</script>", 'image' => 'products/example.webp']);
        $this->get(route('storefront.products.show', $product))->assertOk()
            ->assertSee('Tải miễn phí')->assertDontSee('Liên hệ nhận mẫu')
            ->assertSee('data-copy-sku="'.$product->sku.'"', false)
            ->assertSee('id="product-image-dialog"', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_related_products_prioritize_shared_tags_and_exclude_hidden_and_current_products(): void
    {
        $current = $this->product(['tags' => ['hoa lá tây']]);
        $otherCategory = Category::create(['name' => 'Khác', 'slug' => 'detail-other', 'status' => 'active']);
        $shared = $this->product(['category_id' => $otherCategory->id, 'tags' => ['hoa lá tây'], 'name' => 'Mẫu cùng tag']);
        $same = $this->product(['tags' => ['hoa lá tây'], 'name' => 'Mẫu cùng tag và danh mục']);
        $fallback = $this->product(['name' => 'Mẫu cùng danh mục']);
        $this->product(['category_id' => $otherCategory->id, 'name' => 'Mẫu không liên quan']);
        $this->product(['tags' => ['hoa lá tây'], 'status' => 'inactive', 'name' => 'Mẫu ẩn']);
        $hiddenCategory = Category::create(['name' => 'Ẩn', 'slug' => 'detail-hidden', 'status' => 'inactive']);
        $this->product(['category_id' => $hiddenCategory->id, 'tags' => ['hoa lá tây']]);

        $response = $this->get(route('storefront.products.show', $current))->assertOk()
            ->assertViewHas('relatedProducts', fn ($products) => $products->pluck('id')->all() === [$same->id, $shared->id, $fallback->id])
            ->assertSee('Mẫu liên quan')->assertDontSee('Mẫu không liên quan')->assertDontSee('Mẫu ẩn');
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $response->getContent(), $match);
        $schema = collect(json_decode($match[1], true, flags: JSON_THROW_ON_ERROR)['@graph'])->firstWhere('@type', 'Product');
        $this->assertSame($current->sku, $schema['sku']);
    }

    public function test_related_products_are_limited_to_eight_and_fall_back_without_tags(): void
    {
        $current = $this->product();
        foreach (range(1, 14) as $i) {
            $this->product();
        }
        $this->get(route('storefront.products.show', $current))->assertOk()
            ->assertViewHas('relatedProducts', fn ($products) => $products->count() === 8 && ! $products->contains('id', $current->id))
            ->assertSee('class="cnc-grid"', false)
            ->assertDontSee('data-related-', false);
    }

    public function test_related_grid_renders_all_eight_and_fills_tag_matches_without_duplicates(): void
    {
        $current = $this->product(['tags' => ['carousel']]);
        $shared = collect();
        foreach (range(1, 5) as $i) {
            $shared->push($this->product(['name' => 'Shared carousel '.$i, 'tags' => ['carousel']]));
        }
        $fallback = collect();
        foreach (range(1, 10) as $i) {
            $fallback->push($this->product(['name' => 'Fallback carousel '.$i]));
        }
        $expected = $shared->reverse()->concat($fallback->reverse()->take(3))->pluck('id')->all();
        $response = $this->get(route('storefront.products.show', $current))->assertOk()
            ->assertViewHas('relatedProducts', fn ($products) => $products->pluck('id')->all() === $expected);
        foreach ($shared->concat($fallback->reverse()->take(3)) as $product) {
            $response->assertSee($product->name);
        }
    }
}
