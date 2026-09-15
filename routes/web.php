<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
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
    Route::get('/inventory', [ProductController::class, 'index'])->name('inventory');
    Route::get('/create-product', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::view('/reports', 'reports', ['navActive' => 'reports'])->name('reports');
    Route::view('/docs', 'docs', ['navActive' => 'docs'])->name('docs');
    Route::view('/signin', 'auth.signin')->name('signin');
    Route::view('/signup', 'auth.signup')->name('signup');
    Route::get('/404', fn () => response()->view('errors.404', ['navActive' => 'errors.404'], 404))->name('errors.404');
});

Route::fallback(fn () => response()->view('errors.404', ['navActive' => null], 404));
