<?php

return [
    'watermark_text' => env('IMAGE_WATERMARK_TEXT', 'its me'),
    'quality' => (int) env('PRODUCT_IMAGE_QUALITY', 85),
    'max_width' => (int) env('PRODUCT_IMAGE_MAX_WIDTH', 2000),
    'max_height' => (int) env('PRODUCT_IMAGE_MAX_HEIGHT', 2000),
    'font' => env('IMAGE_WATERMARK_FONT', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'),
    'watermark_logo_path' => env('IMAGE_WATERMARK_LOGO', public_path('assets/images/duy-hoang-cnc-logo-watermark.png')),
    'watermark_logo_opacity' => (int) env('IMAGE_WATERMARK_LOGO_OPACITY', 40),
    'watermark_logo_size' => (float) env('IMAGE_WATERMARK_LOGO_SIZE', 0.9),
    'watermark_text_gap' => (float) env('IMAGE_WATERMARK_TEXT_GAP', 0.2),
    'watermark_position_x' => (float) env('IMAGE_WATERMARK_POSITION_X', 0.5),
    'watermark_position_y' => (float) env('IMAGE_WATERMARK_POSITION_Y', 0.5),
];
