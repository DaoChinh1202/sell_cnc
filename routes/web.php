<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront — trang khách hàng
|--------------------------------------------------------------------------
*/
Route::view('/', 'home')->name('home');

/*
|--------------------------------------------------------------------------
| Admin — port tĩnh template InApp (UI tiếng Việt, $navActive cho sidebar)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::view('/', 'dashboard', ['navActive' => 'dashboard'])->name('dashboard');
    Route::view('/inventory', 'inventory', ['navActive' => 'inventory'])->name('inventory');
    Route::view('/create-product', 'products.create', ['navActive' => 'products.create'])->name('products.create');
    Route::get('/products/{product}/edit', function (string $product) {
        $products = [
            'PRD001' => ['name' => 'Gaming Joy Stick', 'category' => 'electronics', 'brand' => 'brand-name', 'price' => '99.99', 'quantity' => 150, 'image' => 'product-1.png'],
            'PRD002' => ['name' => 'Wireless Earphones', 'category' => 'electronics', 'brand' => 'tech-pro', 'price' => '89.99', 'quantity' => 320, 'image' => 'product-2.png'],
            'PRD003' => ['name' => 'Smart Watch Pro', 'category' => 'electronics', 'brand' => 'tech-pro', 'price' => '98.00', 'quantity' => 200, 'image' => 'product-3.png'],
            'PRD004' => ['name' => 'USB-C Fast Charger', 'category' => 'electronics', 'brand' => 'tech-pro', 'price' => '86.00', 'quantity' => 80, 'image' => 'product-4.png'],
            'PRD005' => ['name' => 'Portable Bluetooth Speaker', 'category' => 'electronics', 'brand' => 'tech-pro', 'price' => '32.00', 'quantity' => 110, 'image' => 'product-5.png'],
            'PRD006' => ['name' => 'Magic Keyboard', 'category' => 'electronics', 'brand' => 'tech-pro', 'price' => '49.00', 'quantity' => 10, 'image' => 'product-6.png'],
            'PRD007' => ['name' => 'MacBook Pro 16"', 'category' => 'electronics', 'brand' => 'tech-pro', 'price' => '99.00', 'quantity' => 10, 'image' => 'product-7.png'],
            'PRD008' => ['name' => 'Wireless Earphones', 'category' => 'electronics', 'brand' => 'tech-pro', 'price' => '109.00', 'quantity' => 200, 'image' => 'product-8.png'],
        ];

        abort_unless(isset($products[$product]), 404);

        return view('products.edit', [
            'navActive' => 'inventory',
            'product' => array_merge(['sku' => $product], $products[$product]),
        ]);
    })->name('products.edit');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::view('/reports', 'reports', ['navActive' => 'reports'])->name('reports');
    Route::view('/docs', 'docs', ['navActive' => 'docs'])->name('docs');
    Route::view('/signin', 'auth.signin')->name('signin');
    Route::view('/signup', 'auth.signup')->name('signup');
    Route::get('/404', fn () => response()->view('errors.404', ['navActive' => 'errors.404'], 404))->name('errors.404');
});

Route::fallback(fn () => response()->view('errors.404', ['navActive' => null], 404));
