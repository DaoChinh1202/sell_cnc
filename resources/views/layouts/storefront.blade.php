<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Lumière — trang sức tinh xảo cho những khoảnh khắc đáng nhớ.">
    <title>@yield('title', 'Lumière Jewelry — Vẻ đẹp lưu dấu')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/sass/storefront.scss', 'resources/js/storefront.js'])
</head>
<body class="storefront">
    @yield('content')
</body>
</html>
