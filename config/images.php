<?php

return [
    'watermark_text' => env('IMAGE_WATERMARK_PHONE', '0869252228'),
    'quality' => (int) env('PRODUCT_IMAGE_QUALITY', 85),
    'max_width' => (int) env('PRODUCT_IMAGE_MAX_WIDTH', 2000),
    'max_height' => (int) env('PRODUCT_IMAGE_MAX_HEIGHT', 2000),
    'font' => env('IMAGE_WATERMARK_FONT', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'),
];
