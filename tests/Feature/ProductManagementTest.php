<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_product_can_be_created_without_removed_fields_and_still_uses_watermark_service(): void
    {
        $category = $this->category();
        $image = UploadedFile::fake()->image('product.png', 320, 240);
        $this->mock(ProductImageService::class, function (MockInterface $mock) use ($image): void {
            $mock->shouldReceive('storeWatermarked')->once()->with($image)->andReturn('products/watermarked.webp');
        });

        $this->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Mẫu hoa sen',
            'sku' => 'SEN-001',
            'price' => 1000000,
            'description' => 'Mẫu thiết kế CNC.',
            'status' => 'active',
            'is_featured' => '1',
            'image' => $image,
        ])->assertRedirect(route('inventory'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'sku' => 'SEN-001',
            'name' => 'Mẫu hoa sen',
            'image' => 'products/watermarked.webp',
            'is_featured' => true,
            'brand' => null,
            'quantity' => 0,
            'minimum_quantity' => 0,
            'tax' => 0,
            'discount' => 0,
            'unit' => 'pcs',
        ]);
    }

    public function test_create_still_validates_and_displays_duplicate_sku_errors(): void
    {
        $product = $this->legacyProduct();
        $this->from(route('products.create'))->post(route('products.store'), [
            'category_id' => $product->category_id,
            'name' => 'Mẫu khác',
            'sku' => $product->sku,
            'price' => 1000000,
            'status' => 'active',
            'image' => UploadedFile::fake()->image('product.png', 320, 240),
        ])->assertRedirect(route('products.create'))->assertSessionHasErrors('sku');

        $message = session('errors')->first('sku');
        $this->get(route('products.create'))->assertOk()->assertSee($message);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_update_ignores_removed_fields_and_preserves_legacy_data_and_existing_image(): void
    {
        $product = $this->legacyProduct();
        Storage::disk('public')->put($product->image, 'existing watermarked image');
        $this->mock(ProductImageService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('storeWatermarked');
        });

        $this->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => 'Mẫu hoa sen đã sửa',
            'sku' => $product->sku,
            'price' => 1200000,
            'status' => 'active',
            'brand' => 'Must not be saved',
            'quantity' => -5,
            'minimum_quantity' => -5,
            'tax' => 999,
            'discount' => 999,
            'unit' => 'Must not be saved',
        ])->assertRedirect(route('products.show', $product))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Mẫu hoa sen đã sửa',
            'price' => 1200000,
            'brand' => 'Legacy Brand',
            'quantity' => 10,
            'minimum_quantity' => 2,
            'tax' => 5,
            'discount' => 25,
            'unit' => 'legacy-unit',
            'image' => $product->image,
        ]);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_admin_pages_no_longer_display_removed_product_fields(): void
    {
        $product = $this->legacyProduct();

        foreach ([route('products.create'), route('products.edit', $product)] as $url) {
            $response = $this->get($url)->assertOk();
            foreach (['brand', 'quantity', 'minimum_quantity', 'tax', 'discount', 'unit'] as $field) {
                $response->assertDontSee('name="'.$field.'"', false);
            }
            $response->assertSee('name="price"', false)
                ->assertSee('name="image"', false)
                ->assertSee('name="is_featured"', false);
        }

        foreach ([route('inventory'), route('products.show', $product)] as $url) {
            $response = $this->get($url)->assertOk()->assertSee($product->name);
            foreach (['Thương hiệu', 'Đơn vị', 'Số lượng', 'Thuế', 'Giảm giá', 'Legacy Brand', 'legacy-unit'] as $text) {
                $response->assertDontSee($text);
            }
        }
    }

    public function test_public_product_uses_listed_price_without_legacy_discount_or_stock_information(): void
    {
        $product = $this->legacyProduct();

        $this->get(route('storefront.products.show', $product))->assertOk()
            ->assertSee('1.000.000đ')
            ->assertDontSee('750.000đ')
            ->assertDontSee('product-detail__discount', false)
            ->assertDontSee('product-detail__stock', false)
            ->assertDontSee('Còn hàng')
            ->assertDontSee('Tạm hết hàng');
    }

    private function category(): Category
    {
        return Category::query()->create([
            'name' => 'Hoa văn CNC',
            'slug' => 'hoa-van-cnc',
            'status' => 'active',
        ]);
    }

    private function legacyProduct(): Product
    {
        return Product::query()->create([
            'category_id' => $this->category()->id,
            'name' => 'Mẫu hoa sen',
            'sku' => 'SEN-001',
            'price' => 1000000,
            'status' => 'active',
            'brand' => 'Legacy Brand',
            'quantity' => 10,
            'minimum_quantity' => 2,
            'tax' => 5,
            'discount' => 25,
            'unit' => 'legacy-unit',
            'image' => 'products/existing.webp',
        ]);
    }
}
