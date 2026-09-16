<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProductImageService
{
    public function storeWatermarked(UploadedFile $uploadedFile): string
    {
        $sourcePath = $uploadedFile->getRealPath();

        if ($sourcePath === false) {
            throw new RuntimeException('Không thể đọc ảnh đã tải lên.');
        }

        $image = match ($uploadedFile->getMimeType()) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => throw new RuntimeException('Định dạng ảnh không được hỗ trợ.'),
        };

        if ($image === false) {
            throw new RuntimeException('Không thể xử lý ảnh đã tải lên.');
        }

        try {
            $image = $this->resize($image);
            $this->applyWatermark($image);

            $path = 'products/'.Str::uuid().'.webp';
            Storage::disk('public')->makeDirectory('products');
            $saved = imagewebp($image, Storage::disk('public')->path($path), config('images.quality'));

            if (! $saved) {
                throw new RuntimeException('Không thể lưu ảnh đã watermark.');
            }

            return $path;
        } finally {
            imagedestroy($image);
        }
    }

    private function resize(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $maxWidth = config('images.max_width');
        $maxHeight = config('images.max_height');
        $scale = min(1, $maxWidth / $width, $maxHeight / $height);

        if ($scale === 1.0) {
            return $image;
        }

        $newWidth = (int) round($width * $scale);
        $newHeight = (int) round($height * $scale);
        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if ($resized === false) {
            throw new RuntimeException('Không thể thay đổi kích thước ảnh.');
        }

        try {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));

            if (! imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
                throw new RuntimeException('Không thể thay đổi kích thước ảnh.');
            }
        } catch (\Throwable $exception) {
            imagedestroy($resized);
            throw $exception;
        }

        imagedestroy($image);

        return $resized;
    }

    private function applyWatermark(\GdImage $image): void
    {
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $font = config('images.font');

        if (! is_file($font)) {
            throw new RuntimeException('Không tìm thấy font để tạo watermark.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $fontSize = max(18, min(72, (int) round(min($width, $height) / 14)));
        $text = config('images.watermark_text');
        $box = imagettfbbox($fontSize, 0, $font, $text);

        if ($box === false) {
            throw new RuntimeException('Không thể tạo watermark.');
        }

        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $gapRatio = max(0, min(0.25, (float) config('images.watermark_text_gap', 0.02)));
        $gap = max(1, (int) round(min($width, $height) * $gapRatio));
        $logoPlacement = $this->applyLogoWatermark($image, $textHeight, $gap);

        if ($logoPlacement !== null) {
            $textTop = $logoPlacement['y'] + $logoPlacement['height'] + $gap;
            $y = (int) round($textTop - $box[7]);
        } else {
            $y = (int) (($height + $textHeight) / 2);
        }

        $positionX = max(0, min(1, (float) config('images.watermark_position_x', 0.5)));
        $x = (int) round($width * $positionX - $textWidth / 2);
        $x = max(0, min($width - $textWidth, $x));
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 65);
        $textColor = imagecolorallocatealpha($image, 255, 255, 255, 58);

        for ($offsetX = -2; $offsetX <= 2; $offsetX++) {
            for ($offsetY = -2; $offsetY <= 2; $offsetY++) {
                if ($offsetX !== 0 || $offsetY !== 0) {
                    imagettftext($image, $fontSize, 0, $x + $offsetX, $y + $offsetY, $shadow, $font, $text);
                }
            }
        }

        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $font, $text);
    }

    /** @return array{x: int, y: int, width: int, height: int}|null */
    private function applyLogoWatermark(\GdImage $image, int $textHeight, int $gap): ?array
    {
        $logoPath = config('images.watermark_logo_path');

        if (! is_string($logoPath) || ! is_file($logoPath) || ! is_readable($logoPath)) {
            throw new RuntimeException('Không tìm thấy hoặc không thể đọc logo watermark.');
        }

        $opacity = max(0, min(100, (float) config('images.watermark_logo_opacity', 18)));

        if ($opacity === 0.0) {
            return null;
        }

        $logo = @imagecreatefrompng($logoPath);

        if ($logo === false) {
            throw new RuntimeException('Không thể đọc logo watermark.');
        }

        $scaledLogo = null;

        try {
            $logoWidth = imagesx($logo);
            $logoHeight = imagesy($logo);

            if ($logoWidth < 1 || $logoHeight < 1) {
                throw new RuntimeException('Logo watermark không hợp lệ.');
            }

            $coverCanvas = (bool) config('images.watermark_logo_cover', true);

            if ($coverCanvas) {
                // Deliberately use the destination dimensions so every product image
                // receives an identically sized logo overlay relative to its canvas.
                $targetWidth = imagesx($image);
                $targetHeight = imagesy($image);
            } else {
                $size = max(0.01, min(1.0, (float) config('images.watermark_logo_size', 0.55)));
                $maxDimension = min(imagesx($image), imagesy($image));
                $maxLogoHeight = max(1, imagesy($image) - $gap - $textHeight);
                $targetDimension = min(
                    max(1, (int) round($maxDimension * $size)),
                    $maxLogoHeight,
                );
                $scale = min($targetDimension / $logoWidth, $targetDimension / $logoHeight);
                $targetWidth = max(1, (int) round($logoWidth * $scale));
                $targetHeight = max(1, (int) round($logoHeight * $scale));
            }

            $scaledLogo = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($scaledLogo === false) {
                throw new RuntimeException('Không thể tạo logo watermark.');
            }

            imagealphablending($logo, false);
            imagesavealpha($logo, true);
            imagealphablending($scaledLogo, false);
            imagesavealpha($scaledLogo, true);
            imagefill($scaledLogo, 0, 0, imagecolorallocatealpha($scaledLogo, 0, 0, 0, 127));

            if (! imagecopyresampled(
                $scaledLogo,
                $logo,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $logoWidth,
                $logoHeight,
            )) {
                throw new RuntimeException('Không thể thu nhỏ logo watermark.');
            }

            // Apply opacity to each pixel so the logo's own alpha remains intact.
            for ($x = 0; $x < $targetWidth; $x++) {
                for ($y = 0; $y < $targetHeight; $y++) {
                    $colors = imagecolorsforindex($scaledLogo, imagecolorat($scaledLogo, $x, $y));
                    $alpha = 127 - (int) round((127 - $colors['alpha']) * $opacity / 100);
                    $color = imagecolorallocatealpha(
                        $scaledLogo,
                        $colors['red'],
                        $colors['green'],
                        $colors['blue'],
                        max(0, min(127, $alpha)),
                    );
                    imagesetpixel($scaledLogo, $x, $y, $color);
                }
            }

            if ($coverCanvas) {
                $x = 0;
                $y = 0;
            } else {
                $positionX = max(0, min(1, (float) config('images.watermark_position_x', 0.5)));
                $positionY = max(0, min(1, (float) config('images.watermark_position_y', 0.5)));
                $groupHeight = $targetHeight + $gap + $textHeight;
                $groupTop = (int) round(imagesy($image) * $positionY - $groupHeight / 2);
                $groupTop = max(0, min(imagesy($image) - $groupHeight, $groupTop));
                $x = (int) round(imagesx($image) * $positionX - $targetWidth / 2);
                $x = max(0, min(imagesx($image) - $targetWidth, $x));
                $y = $groupTop;
            }
            imagealphablending($image, true);
            imagecopy($image, $scaledLogo, $x, $y, 0, 0, $targetWidth, $targetHeight);

            return [
                'x' => $x,
                'y' => $y,
                'width' => $targetWidth,
                'height' => $targetHeight,
            ];
        } finally {
            if ($scaledLogo instanceof \GdImage) {
                imagedestroy($scaledLogo);
            }

            imagedestroy($logo);
        }
    }
}
