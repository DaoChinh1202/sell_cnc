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
        return $this->storeImage($uploadedFile, true);
    }

    public function storeWithoutWatermark(UploadedFile $uploadedFile): string
    {
        return $this->storeImage($uploadedFile, false);
    }

    private function storeImage(UploadedFile $uploadedFile, bool $watermark): string
    {
        return $this->processImage($uploadedFile, $watermark, function (\GdImage $image): string {
            $path = 'products/'.Str::uuid().'.webp';
            Storage::disk('public')->makeDirectory('products');
            if (! imagewebp($image, Storage::disk('public')->path($path), config('images.quality'))) {
                throw new RuntimeException('Không thể lưu ảnh sản phẩm.');
            }

            return $path;
        });
    }

    public function preview(UploadedFile $uploadedFile, bool $watermark): string
    {
        return $this->processImage($uploadedFile, $watermark, function (\GdImage $image): string {
            $stream = fopen('php://temp', 'w+b');
            if ($stream === false) {
                throw new RuntimeException('Không thể tạo ảnh xem trước.');
            }

            try {
                if (! imagewebp($image, $stream, config('images.quality'))) {
                    throw new RuntimeException('Không thể tạo ảnh xem trước.');
                }
                rewind($stream);
                $bytes = stream_get_contents($stream);
                if ($bytes === false || $bytes === '') {
                    throw new RuntimeException('Không thể đọc ảnh xem trước.');
                }

                return $bytes;
            } finally {
                fclose($stream);
            }
        });
    }

    private function processImage(UploadedFile $uploadedFile, bool $watermark, callable $output): string
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
            if ($watermark) {
                $this->applyWatermark($image);
            }

            $this->applyLogo($image);

            return $output($image);
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
        $scale = min(1.0, $maxWidth / $width, $maxHeight / $height);

        if ($scale === 1.0) {
            return $image;
        }

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
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

    private function applyLogo(\GdImage $image): void
    {
        $path = public_path('assets/images/khomau3d-logo.webp');
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Không tìm thấy logo để chèn vào ảnh sản phẩm.');
        }

        $logo = imagecreatefromwebp($path);
        if ($logo === false) {
            throw new RuntimeException('Không thể đọc logo để chèn vào ảnh sản phẩm.');
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);
            $margin = max(1, (int) round(min($width, $height) * 0.02));
            // Limit both dimensions so the badge also fits panoramic and portrait uploads.
            $scale = min($width * 0.20 / imagesx($logo), $height * 0.25 / imagesy($logo));
            $logoWidth = max(1, (int) round(imagesx($logo) * $scale));
            $logoHeight = max(1, (int) round(imagesy($logo) * $scale));
            imagealphablending($image, true);
            imagesavealpha($image, true);

            if (! imagecopyresampled($image, $logo, max(0, $width - $logoWidth - $margin), max(0, $height - $logoHeight - $margin), 0, 0, $logoWidth, $logoHeight, imagesx($logo), imagesy($logo))) {
                throw new RuntimeException('Không thể chèn logo vào ảnh sản phẩm.');
            }
        } finally {
            imagedestroy($logo);
        }
    }

    private function applyWatermark(\GdImage $image): void
    {
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $font = config('images.font');

        if (! is_string($font) || ! is_file($font) || ! is_readable($font)) {
            throw new RuntimeException('Không tìm thấy font để tạo watermark.');
        }

        $lines = config('images.watermark_lines');
        if (! is_array($lines) || count($lines) !== 3) {
            throw new RuntimeException('Cần cấu hình đủ ba dòng watermark.');
        }

        $fontSize = 48.0;
        $padding = 8;
        $gap = 26;
        $bounds = [];
        foreach ($lines as $text) {
            if (! is_string($text) || trim($text) === '') {
                throw new RuntimeException('Nội dung watermark không được để trống.');
            }
            $bounds[] = $this->textBounds($fontSize, 0, $font, $text);
        }

        // Render the whole block before rotating, so all three lines share a baseline direction.
        $layerWidth = max(array_column($bounds, 'width')) + 2 * $padding;
        $layerHeight = array_sum(array_column($bounds, 'height')) + 2 * $gap + 2 * $padding;
        $layer = imagecreatetruecolor($layerWidth, $layerHeight);
        if ($layer === false) {
            throw new RuntimeException('Không thể tạo lớp watermark.');
        }

        $rotated = null;
        try {
            imagealphablending($layer, false);
            imagesavealpha($layer, true);
            $transparent = imagecolorallocatealpha($layer, 0, 0, 0, 127);
            imagefill($layer, 0, 0, $transparent);
            imagealphablending($layer, true);
            $outline = imagecolorallocate($layer, 255, 255, 255);
            $red = imagecolorallocate($layer, 230, 0, 0);
            $top = $padding;

            foreach (array_values($lines) as $index => $text) {
                $box = $bounds[$index];
                $x = (int) round(($layerWidth - $box['width']) / 2 - $box['min_x']);
                $y = $top - $box['min_y'];
                $this->drawText($layer, $fontSize, 0, $x, $y, $outline, $red, $font, $text);
                $top += $box['height'] + $gap;
            }

            // Negative rotation slopes down from left to right, matching the reference image.
            $rotated = imagerotate($layer, -22, $transparent);
            if ($rotated === false) {
                throw new RuntimeException('Không thể xoay watermark.');
            }
            imagesavealpha($rotated, true);
            imagealphablending($rotated, false);
            // GD rotation can round fully transparent pixels to alpha 126, tinting the background.
            $clear = imagecolorallocatealpha($rotated, 0, 0, 0, 127);
            for ($row = 0; $row < imagesy($rotated); $row++) {
                for ($column = 0; $column < imagesx($rotated); $column++) {
                    if (((imagecolorat($rotated, $column, $row) >> 24) & 0x7F) >= 126) {
                        imagesetpixel($rotated, $column, $row, $clear);
                    }
                }
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $scale = min($width * 0.94 / imagesx($rotated), $height * 0.82 / imagesy($rotated));
            $targetWidth = max(1, (int) floor(imagesx($rotated) * $scale));
            $targetHeight = max(1, (int) floor(imagesy($rotated) * $scale));
            $x = (int) floor(($width - $targetWidth) / 2);
            $y = (int) floor(($height - $targetHeight) / 2);

            if (! imagecopyresampled($image, $rotated, $x, $y, 0, 0, $targetWidth, $targetHeight, imagesx($rotated), imagesy($rotated))) {
                throw new RuntimeException('Không thể ghép watermark vào ảnh.');
            }
        } finally {
            imagedestroy($layer);
            if ($rotated instanceof \GdImage) {
                imagedestroy($rotated);
            }
        }

    }

    private function drawText(\GdImage $image, float $fontSize, int $angle, int $x, int $y, int $outline, int $textColor, string $font, string $text): void
    {
        for ($offsetX = -3; $offsetX <= 3; $offsetX++) {
            for ($offsetY = -3; $offsetY <= 3; $offsetY++) {
                if (imagettftext($image, $fontSize, $angle, $x + $offsetX, $y + $offsetY, $outline, $font, $text) === false) {
                    throw new RuntimeException('Không thể tạo watermark.');
                }
            }
        }

        if (imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $font, $text) === false) {
            throw new RuntimeException('Không thể tạo watermark.');
        }
    }

    /** @return array{min_x: int, min_y: int, width: int, height: int} */
    private function textBounds(float $fontSize, int $angle, string $font, string $text): array
    {
        $box = imagettfbbox($fontSize, $angle, $font, $text);

        if ($box === false) {
            throw new RuntimeException('Không thể đo kích thước watermark.');
        }

        $xs = [$box[0], $box[2], $box[4], $box[6]];
        $ys = [$box[1], $box[3], $box[5], $box[7]];

        return [
            'min_x' => min($xs),
            'min_y' => min($ys),
            'width' => max(1, max($xs) - min($xs)),
            'height' => max(1, max($ys) - min($ys)),
        ];
    }
}
