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

    private function applyWatermark(\GdImage $image): void
    {
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $font = config('images.font');

        if (! is_string($font) || ! is_file($font) || ! is_readable($font)) {
            throw new RuntimeException('Không tìm thấy font để tạo watermark.');
        }

        $text = trim((string) config('images.watermark_text', '0869252228'));

        if ($text === '') {
            throw new RuntimeException('Chưa cấu hình số điện thoại watermark.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $angle = 30;
        $fontSize = 72.0;
        $bounds = $this->textBounds($fontSize, $angle, $font, $text);
        // Each tilted line fits in its own band, including the one-pixel outline.
        $availableWidth = max(1, $width * 0.874 - 2);
        $availableHeight = max(1, $height * 0.276 - 2);
        $fontSize *= min($availableWidth / $bounds['width'], $availableHeight / $bounds['height']);
        $fontSize = max(1.0, floor($fontSize * 2) / 2);

        do {
            $bounds = $this->textBounds($fontSize, $angle, $font, $text);
            if ($bounds['width'] <= $availableWidth && $bounds['height'] <= $availableHeight) {
                break;
            }
            $fontSize -= 0.5;
        } while ($fontSize >= 1);

        if ($fontSize < 1) {
            throw new RuntimeException('Ảnh quá nhỏ để chèn đủ watermark.');
        }

        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 90);
        $textColor = imagecolorallocatealpha($image, 255, 255, 255, 58);

        // GD's positive angle rises from left to right. Place three copies vertically.
        foreach ([0.2, 0.5, 0.8] as $positionY) {
            $x = (int) round(($width - $bounds['width']) / 2 - $bounds['min_x']);
            $y = (int) round($height * $positionY - $bounds['height'] / 2 - $bounds['min_y']);

            $this->drawText($image, $fontSize, $angle, $x, $y, $shadow, $textColor, $font, $text);
        }

    }

    private function drawText(\GdImage $image, float $fontSize, int $angle, int $x, int $y, int $shadow, int $textColor, string $font, string $text): void
    {
        foreach ([[-1, -1], [1, -1], [-1, 1], [1, 1]] as [$offsetX, $offsetY]) {
            if (imagettftext($image, $fontSize, $angle, $x + $offsetX, $y + $offsetY, $shadow, $font, $text) === false) {
                throw new RuntimeException('Không thể tạo watermark.');
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
