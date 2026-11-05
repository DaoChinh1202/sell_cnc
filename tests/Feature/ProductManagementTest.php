<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'price' => 1000000,
            'description' => 'Mẫu thiết kế CNC.',
            'free_download_url' => 'https://drive.google.com/file/d/example/view?usp=sharing',
            'status' => 'active',
            'is_featured' => '1',
            'image' => $image,
        ])->assertRedirect(route('inventory'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Mẫu hoa sen',
            'image' => 'products/watermarked.webp',
            'free_download_url' => 'https://drive.google.com/file/d/example/view?usp=sharing',
            'is_featured' => true,
            'brand' => null,
            'quantity' => 0,
            'minimum_quantity' => 0,
            'tax' => 0,
            'discount' => 0,
            'unit' => 'pcs',
        ]);
        $this->assertMatchesRegularExpression('/^CNC-[A-Z0-9]{10}$/', Product::sole()->sku);
    }

    public function test_create_ignores_submitted_sku_and_generates_a_new_code_when_candidate_exists(): void
    {
        $product = $this->legacyProduct();
        $product->update(['sku' => 'CNC-AAAAAAAAAA']);
        $this->mock(ProductImageService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('storeWatermarked')->once()->andReturn('products/new.webp');
        });
        $payload = [
            'category_id' => $product->category_id,
            'name' => 'Mẫu khác',
            'sku' => $product->sku,
            'price' => 1000000,
            'status' => 'active',
            'image' => UploadedFile::fake()->image('product.png', 320, 240),
        ];
        $candidates = ['aaaaaaaaaa', 'bbbbbbbbbb'];
        Str::createRandomStringsUsing(function (int $length) use (&$candidates): string {
            return $length === 10 ? array_shift($candidates) : str_repeat('x', $length);
        });

        try {
            $this->post(route('products.store'), $payload)
                ->assertRedirect(route('inventory'))->assertSessionHasNoErrors();
        } finally {
            Str::createRandomStringsNormally();
        }

        $this->assertDatabaseHas('products', ['name' => 'Mẫu khác', 'sku' => 'CNC-BBBBBBBBBB', 'free_download_url' => null]);
        $this->assertSame('CNC-AAAAAAAAAA', $product->fresh()->sku);
        $this->assertDatabaseCount('products', 2);
    }

    public function test_product_can_be_created_without_watermark(): void
    {
        config(['images.font' => '/missing-font.ttf']);
        $this->partialMock(ProductImageService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('storeWatermarked');
        });

        $this->post(route('products.store'), [
            'category_id' => $this->category()->id,
            'name' => 'Ảnh không watermark',
            'price' => 100000,
            'status' => 'active',
            'apply_watermark' => '0',
            'image' => UploadedFile::fake()->image('product.png', 320, 240),
        ])->assertRedirect(route('inventory'))->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Đã thêm sản phẩm. Ảnh được lưu dưới dạng WebP không chèn watermark.');

        $product = Product::sole();
        Storage::disk('public')->assertExists($product->image);
        $this->assertSame('image/webp', mime_content_type(Storage::disk('public')->path($product->image)));
    }

    public function test_replacing_an_image_without_watermark_removes_the_previous_image(): void
    {
        $product = $this->legacyProduct();
        Storage::disk('public')->put($product->image, 'old image');
        config(['images.font' => '/missing-font.ttf']);
        $this->partialMock(ProductImageService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('storeWatermarked');
        });

        $this->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'status' => 'active',
            'apply_watermark' => '0',
            'image' => UploadedFile::fake()->image('replacement.jpg', 320, 240),
        ])->assertRedirect(route('products.show', $product))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($product->image);
        Storage::disk('public')->assertExists($product->fresh()->image);
    }

    public function test_watermark_option_is_validated_and_preserved_after_validation_errors(): void
    {
        $this->category();
        $this->from(route('products.create'))->post(route('products.store'), [
            'apply_watermark' => 'invalid',
        ])->assertSessionHasErrors('apply_watermark');

        $this->from(route('products.create'))->post(route('products.store'), [
            'apply_watermark' => '0',
        ])->assertSessionHasErrors('image');

        $this->get(route('products.create'))->assertOk()
            ->assertSee('id="productWatermark"', false)
            ->assertDontSee('value="1" checked aria-describedby="productWatermarkHelp"', false);
    }

    public function test_create_save_failure_reports_a_product_error_and_deletes_the_uploaded_image(): void
    {
        $category = $this->category();
        Product::creating(function (): void {
            throw new \RuntimeException('Simulated product save failure.');
        });

        try {
            $this->from(route('products.create'))->post(route('products.store'), [
                'category_id' => $category->id,
                'name' => 'Mẫu thử lỗi lưu',
                'price' => 100000,
                'status' => 'active',
                'apply_watermark' => '0',
                'image' => UploadedFile::fake()->image('product.png', 320, 240),
            ])->assertRedirect(route('products.create'))
                ->assertSessionHasErrors('product')->assertSessionDoesntHaveErrors('image');
        } finally {
            Product::flushEventListeners();
        }

        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->get(route('products.create'))->assertOk()
            ->assertSee('Không thể lưu sản phẩm. Vui lòng thử lại hoặc liên hệ quản trị viên.');
    }

    public function test_update_save_failure_keeps_the_previous_image_and_deletes_the_replacement(): void
    {
        $product = $this->legacyProduct();
        Storage::disk('public')->put($product->image, 'previous image');
        Product::updating(function (): void {
            throw new \RuntimeException('Simulated product save failure.');
        });

        try {
            $this->from(route('products.edit', $product))->put(route('products.update', $product), [
                'category_id' => $product->category_id,
                'name' => 'Tên mới chưa lưu',
                'sku' => $product->sku,
                'price' => $product->price,
                'status' => 'active',
                'apply_watermark' => '0',
                'image' => UploadedFile::fake()->image('replacement.png', 320, 240),
            ])->assertRedirect(route('products.edit', $product))
                ->assertSessionHasErrors('product')->assertSessionDoesntHaveErrors('image');
        } finally {
            Product::flushEventListeners();
        }

        $this->assertSame($product->image, $product->fresh()->image);
        $this->assertSame([$product->image], Storage::disk('public')->allFiles());
        $this->get(route('products.edit', $product))->assertOk()
            ->assertSee('Không thể lưu sản phẩm. Vui lòng thử lại hoặc liên hệ quản trị viên.');
    }

    public function test_create_form_explains_automatic_sku_without_an_editable_sku_field(): void
    {
        $this->category();
        $this->get(route('products.create'))->assertOk()
            ->assertSee('Tự động tạo khi lưu sản phẩm')
            ->assertDontSee('name="sku"', false);
    }

    public function test_update_still_rejects_duplicate_sku(): void
    {
        $product = $this->legacyProduct();
        $other = $product->replicate();
        $other->sku = 'OTHER-001';
        $other->save();
        $this->from(route('products.edit', $product))->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'sku' => $other->sku,
            'price' => $product->price,
            'status' => 'active',
        ])->assertRedirect(route('products.edit', $product))->assertSessionHasErrors('sku');
        $message = session('errors')->first('sku');
        $this->get(route('products.edit', $product))->assertOk()->assertSee($message);
        $this->assertSame($product->sku, $product->fresh()->sku);
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
            'apply_watermark' => '0',
        ])->assertRedirect(route('products.show', $product))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Mẫu hoa sen đã sửa',
            'sku' => $product->sku,
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
                ->assertSee('name="apply_watermark"', false)
                ->assertSee('name="is_featured"', false)
                ->assertSee('name="free_download_url"', false);
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

    public function test_free_download_link_can_be_updated_displayed_and_cleared(): void
    {
        $product = $this->legacyProduct();
        $payload = [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'status' => 'active',
        ];
        $url = 'https://drive.google.com/file/d/free-example/view?usp=sharing&download=1';

        $this->get(route('storefront.products.show', $product))->assertOk()->assertDontSee('Tải miễn phí');
        $this->put(route('products.update', $product), $payload + ['free_download_url' => $url])
            ->assertSessionHasNoErrors()->assertRedirect(route('products.show', $product));
        $this->assertSame($url, $product->fresh()->free_download_url);
        $this->get(route('products.edit', $product))->assertOk()->assertSee('value="'.e($url).'"', false);
        $this->get(route('storefront.products.show', $product))->assertOk()
            ->assertSee('Tải miễn phí')->assertSee('href="'.e($url).'"', false)
            ->assertSee('rel="noopener noreferrer"', false);

        $this->put(route('products.update', $product), $payload + ['free_download_url' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->free_download_url);
        $this->get(route('storefront.products.show', $product))->assertOk()->assertDontSee('Tải miễn phí');
    }

    public function test_invalid_free_download_urls_are_rejected_on_create_and_update(): void
    {
        $product = $this->legacyProduct();
        $payload = [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'status' => 'active',
        ];
        foreach (['not-a-url', 'javascript:alert(1)', 'data:text/html,test', 'ftp://example.com/file', 'https://example.com/'.str_repeat('a', 2048)] as $url) {
            $this->post(route('products.store'), $payload + [
                'free_download_url' => $url,
                'image' => UploadedFile::fake()->image('product.png', 320, 240),
            ])->assertSessionHasErrors('free_download_url');
            $this->put(route('products.update', $product), $payload + ['free_download_url' => $url])
                ->assertSessionHasErrors('free_download_url');
        }
        $this->assertNull($product->fresh()->free_download_url);
        $this->assertDatabaseCount('products', 1);
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
