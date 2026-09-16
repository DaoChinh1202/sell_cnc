<?php

namespace Tests\Unit;

use App\Services\ProductImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageServiceTest extends TestCase
{
    public function test_it_stores_a_webp_with_the_logo_watermark_enabled(): void
    {
        Storage::fake('public');
        config([
            'images.watermark_logo_path' => public_path('assets/images/logo-duy-hoang-gold-brown.png'),
            'images.watermark_logo_opacity' => 18,
            'images.watermark_logo_size' => 0.55,
            'images.watermark_text_gap' => 0.02,
        ]);

        $path = (new ProductImageService)->storeWatermarked(
            UploadedFile::fake()->image('product.jpg', 320, 240),
        );

        $this->assertTrue(Storage::disk('public')->exists($path));
        $image = imagecreatefromwebp(Storage::disk('public')->path($path));

        $this->assertNotFalse($image);
        $this->assertSame(320, imagesx($image));
        $this->assertSame(240, imagesy($image));

        imagedestroy($image);
    }
}
