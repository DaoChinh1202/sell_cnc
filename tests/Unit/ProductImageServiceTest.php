<?php

namespace Tests\Unit;

use App\Services\ProductImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ProductImageServiceTest extends TestCase
{
    public static function images(): array
    {
        return [
            'landscape jpeg' => ['jpeg', 640, 480, 640, 480],
            'portrait png' => ['png', 480, 800, 480, 800],
            'wide webp' => ['webp', 1400, 300, 1400, 300],
            'small square' => ['png', 64, 64, 64, 64],
            'resized image' => ['jpeg', 3000, 900, 2000, 600],
        ];
    }

    #[DataProvider('images')]
    public function test_it_draws_a_centered_red_and_white_watermark_sloping_down_to_the_right(
        string $format, int $width, int $height, int $outputWidth, int $outputHeight,
    ): void {
        Storage::fake('public');
        config([
            // The former phone-only setting must not override the new text.
            'images.watermark_text' => 'OLD PHONE WATERMARK',
            'images.font' => '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            'images.quality' => 100,
            'images.max_width' => 2000,
            'images.max_height' => 2000,
            // Legacy logo settings must not override the mandatory brand asset.
            'images.watermark_logo_path' => '/missing-logo.png',
            'images.watermark_logo_opacity' => 100,
        ]);
        $path = (new ProductImageService)->storeWatermarked($this->upload($format, $width, $height));
        $this->assertStringEndsWith('.webp', $path);
        $this->assertTrue(Storage::disk('public')->exists($path));
        $image = imagecreatefromwebp(Storage::disk('public')->path($path));

        try {
            $this->assertSame($outputWidth, imagesx($image));
            $this->assertSame($outputHeight, imagesy($image));
            $pixels = [];
            $redPixels = 0;
            $whitePixels = 0;
            $topMarginPixels = 0;
            $outside = 0;
            for ($y = 0; $y < $outputHeight; $y++) {
                for ($x = 0; $x < $outputWidth; $x++) {
                    // Check the existing text independently of the new bottom-right badge.
                    if ($x > $outputWidth * 0.75 && $y > $outputHeight * 0.70) {
                        continue;
                    }
                    $pixel = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                    if ($pixel['red'] < 40) {
                        continue;
                    }
                    if ($y < $outputHeight * 0.04) {
                        $topMarginPixels++;
                    }
                    $pixels[] = [$x, $y];
                    if ($pixel['red'] > 100 && $pixel['red'] > $pixel['green'] * 1.5) {
                        $redPixels++;
                    }
                    if (min($pixel['red'], $pixel['green'], $pixel['blue']) > 160) {
                        $whitePixels++;
                    }
                    if ($x === 0 || $x === $outputWidth - 1 || $y === 0 || $y === $outputHeight - 1) {
                        $outside++;
                    }
                }
            }
            $this->assertSame(0, $outside, 'Watermarks must not be clipped at the image edge.');
            $this->assertSame(0, $topMarginPixels, 'The former corner labels must be absent.');
            $this->assertNotEmpty($pixels);
            $xs = array_column($pixels, 0);
            $ys = array_column($pixels, 1);
            $meanX = array_sum($xs) / count($xs);
            $meanY = array_sum($ys) / count($ys);
            $covariance = 0;
            foreach ($pixels as [$x, $y]) {
                $covariance += ($x - $meanX) * ($y - $meanY);
            }
            $this->assertGreaterThan(0, $covariance, 'Watermark must slope down to the right.');
            if ($outputWidth >= 480) {
                $this->assertGreaterThan(0, $redPixels, 'Text must be red.');
                $this->assertGreaterThan(0, $whitePixels, 'Text must have a white outline.');
            }
        } finally {
            imagedestroy($image);
        }
    }

    public function test_default_watermark_contains_the_requested_three_lines(): void
    {
        $this->assertSame([
            'Duy Hoang',
            'Nhận vẽ thiết kế mẫu quét mẫu JDPAINT-ARTFOM theo yêu cầu.',
            'Liên hệ: 0869252228',
        ], config('images.watermark_lines'));
    }

    public function test_image_without_watermark_is_resized_to_webp_without_adding_text(): void
    {
        Storage::fake('public');
        config([
            'images.font' => '/missing-font.ttf',
            'images.quality' => 100,
            'images.max_width' => 320,
            'images.max_height' => 320,
        ]);
        $path = (new ProductImageService)->storeWithoutWatermark($this->upload('png', 640, 480));
        $image = imagecreatefromwebp(Storage::disk('public')->path($path));

        try {
            $this->assertSame(320, imagesx($image));
            $this->assertSame(240, imagesy($image));
            $logoPixels = 0;
            for ($y = 0; $y < imagesy($image); $y++) {
                for ($x = 0; $x < imagesx($image); $x++) {
                    $pixel = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                    if (max($pixel['red'], $pixel['green'], $pixel['blue']) > 10) {
                        $this->assertGreaterThan(240, $x, 'Only the bottom-right logo may be added when text is disabled.');
                        $this->assertGreaterThan(170, $y);
                        $logoPixels++;
                    }
                }
            }
            $this->assertGreaterThan(0, $logoPixels, 'The logo is mandatory even when text is disabled.');
        } finally {
            imagedestroy($image);
        }
    }

    #[DataProvider('images')]
    public function test_logo_is_present_in_preview_with_either_text_option(
        string $format, int $width, int $height, int $outputWidth, int $outputHeight,
    ): void {
        config(['images.max_width' => 2000, 'images.max_height' => 2000, 'images.quality' => 100]);
        foreach ([false, true] as $watermark) {
            $bytes = (new ProductImageService)->preview($this->upload($format, $width, $height), $watermark);
            $image = imagecreatefromstring($bytes);
            try {
                $whitePixels = 0;
                for ($y = (int) ($outputHeight * 0.80); $y < $outputHeight; $y++) {
                    for ($x = (int) ($outputWidth * 0.80); $x < $outputWidth; $x++) {
                        $pixel = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                        if (min($pixel['red'], $pixel['green'], $pixel['blue']) > 100) {
                            $whitePixels++;
                        }
                    }
                }
                $this->assertGreaterThan(0, $whitePixels, 'Both preview options must contain the bottom-right logo.');
            } finally {
                imagedestroy($image);
            }
        }
    }

    public function test_transparent_png_remains_transparent_outside_the_watermark(): void
    {
        Storage::fake('public');
        $path = (new ProductImageService)->storeWatermarked($this->upload('png', 640, 480, true));
        $image = imagecreatefromwebp(Storage::disk('public')->path($path));
        $this->assertSame(127, imagecolorsforindex($image, imagecolorat($image, 0, 0))['alpha']);
        imagedestroy($image);
    }

    public function test_missing_font_fails_without_saving_an_unwatermarked_image(): void
    {
        Storage::fake('public');
        config(['images.font' => '/missing-font.ttf']);
        try {
            (new ProductImageService)->storeWatermarked($this->upload('png', 320, 240));
            $this->fail('Missing font must fail the upload.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('font', $exception->getMessage());
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    private function upload(string $format, int $width, int $height, bool $transparent = false): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        if ($transparent) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        }
        ob_start();
        match ($format) {
            'jpeg' => imagejpeg($image),
            'png' => imagepng($image),
            'webp' => imagewebp($image),
        };
        $bytes = ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent('product.'.$format, $bytes);
    }
}
