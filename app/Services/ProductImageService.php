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

    /**
     * @param \GdImage $image
     * @return \GdImage
     */
    private function resize($image)
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

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /** @param \GdImage $image */
    private function applyWatermark($image): void
    {
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
        $x = (int) (($width - $textWidth) / 2);
        $y = (int) (($height + $textHeight) / 2);
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
}
