<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImagePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_requires_an_authenticated_admin(): void
    {
        $this->postJson(route('products.image-preview'))->assertUnauthorized();
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->postJson(route('products.image-preview'))->assertForbidden();
    }

    public function test_preview_matches_saved_image_for_each_watermark_option_without_persisting_preview(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        foreach ([true, false] as $watermark) {
            $file = UploadedFile::fake()->image('preview.png', 640, 480);
            $before = Storage::disk('public')->allFiles();
            $response = $this->post(route('products.image-preview'), [
                'image' => $file, 'apply_watermark' => $watermark ? '1' : '0',
            ], ['Accept' => 'application/json'])->assertOk()
                ->assertHeader('Content-Type', 'image/webp')->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertSame($before, Storage::disk('public')->allFiles());
            $this->assertDatabaseCount('products', 0);
            $bytes = $response->getContent();
            $image = imagecreatefromstring($bytes);
            $this->assertSame(640, imagesx($image));
            $this->assertSame(480, imagesy($image));
            imagedestroy($image);
            $service = new ProductImageService;
            $path = $watermark ? $service->storeWatermarked($file) : $service->storeWithoutWatermark($file);
            $this->assertSame(Storage::disk('public')->get($path), $bytes, 'Preview must use the same render pipeline as saving.');
            $previews[] = $bytes;
        }
        $this->assertNotSame($previews[0], $previews[1]);
    }

    public function test_repeated_unused_previews_leave_no_files_and_preserve_existing_images(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('products/existing.webp', 'existing product image');
        $this->actingAs(User::factory()->admin()->create());

        foreach (range(1, 6) as $attempt) {
            $this->post(route('products.image-preview'), [
                'image' => UploadedFile::fake()->image('unused-'.$attempt.'.png', 320, 240),
                'apply_watermark' => $attempt % 2,
            ], ['Accept' => 'application/json'])->assertOk();
        }

        $this->assertSame(['products/existing.webp'], Storage::disk('public')->allFiles());
        $this->assertSame('existing product image', Storage::disk('public')->get('products/existing.webp'));
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('products', 0);
    }

    public function test_preview_validates_file_and_watermark_without_requiring_product_fields(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $this->postJson(route('products.image-preview'), [])->assertUnprocessable()->assertJsonValidationErrors(['image', 'apply_watermark']);
        foreach ([
            UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml'),
            UploadedFile::fake()->image('small.png', 50, 50),
            UploadedFile::fake()->image('large.png', 200, 200)->size(5121),
        ] as $file) {
            $this->post(route('products.image-preview'), ['image' => $file, 'apply_watermark' => '1'], ['Accept' => 'application/json'])
                ->assertUnprocessable()->assertJsonValidationErrors('image');
        }
        $this->post(route('products.image-preview'), [
            'image' => UploadedFile::fake()->image('valid.png', 200, 200), 'apply_watermark' => 'invalid',
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('apply_watermark');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_processing_failure_is_reported_without_exposing_details_or_saving_files(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        config(['images.font' => '/missing/private-font.ttf']);
        $this->post(route('products.image-preview'), [
            'image' => UploadedFile::fake()->image('valid.png', 200, 200), 'apply_watermark' => '1',
        ], ['Accept' => 'application/json'])->assertStatus(500)
            ->assertJsonStructure(['message'])->assertDontSee('/missing/private-font.ttf');
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('products', 0);
    }
}
