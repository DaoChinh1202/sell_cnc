<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

/*
|--------------------------------------------------------------------------
| Storefront — trang khách hàng
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    $categories = Category::query()
        ->where('status', 'active')
        ->withCount([
            'products' => fn ($query) => $query->where('status', 'active'),
        ])
        ->get();

    $featuredProducts = Product::query()
        ->with('category')
        ->where('status', 'active')
        ->where('is_featured', true)
        ->whereHas('category', fn ($query) => $query->where('status', 'active'))
        ->latest()
        ->get();

    $newestProducts = Product::query()
        ->with('category')
        ->where('status', 'active')
        ->where('is_featured', false)
        ->whereHas('category', fn ($query) => $query->where('status', 'active'))
        ->latest()
        ->take(4)
        ->get();

    return view('home', [
        'categories' => $categories,
        'featuredProducts' => $featuredProducts,
        'newestProducts' => $newestProducts,
        // Keep the existing storefront section supplied until it consumes the
        // dedicated featured and newest collections directly.
        'products' => $featuredProducts,
    ]);
})->name('home');

Route::get('/products/{product}', function (string $product) {
    $product = Product::query()
        ->with('category')
        ->whereKey($product)
        ->where('status', 'active')
        ->firstOrFail();

    return view('products.detail', ['product' => $product]);
})->name('storefront.products.show');

Route::get('/categories/{category:slug}', function (Request $request, string $category) {
    $category = Category::query()
        ->where('slug', $category)
        ->where('status', 'active')
        ->firstOrFail();

    $filters = $request->validate([
        'min_price' => ['nullable', 'numeric', 'min:0'],
        'max_price' => [
            'nullable',
            'numeric',
            Rule::when($request->filled('min_price'), ['gte:min_price']),
        ],
        'sort' => ['nullable', 'in:newest,price_asc,price_desc'],
    ]);

    $productsQuery = Product::query()
        ->with('category')
        ->where('category_id', $category->id)
        ->where('status', 'active');

    if (($filters['min_price'] ?? null) !== null) {
        $productsQuery->where('price', '>=', $filters['min_price']);
    }

    if (($filters['max_price'] ?? null) !== null) {
        $productsQuery->where('price', '<=', $filters['max_price']);
    }

    match ($filters['sort'] ?? 'newest') {
        'price_asc' => $productsQuery->orderBy('price')->orderByDesc('created_at'),
        'price_desc' => $productsQuery->orderByDesc('price')->orderByDesc('created_at'),
        default => $productsQuery->latest(),
    };

    $products = $productsQuery->paginate(12)->withQueryString();

    return view('categories.show', [
        'category' => $category,
        'products' => $products,
    ]);
})->name('storefront.categories.show');

/*
|--------------------------------------------------------------------------
| Admin — đăng nhập bằng username, chỉ tài khoản có quyền quản trị
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::view('/signin', 'auth.signin')->name('signin');
        Route::post('/signin', [AdminAuthController::class, 'store'])->name('signin.store');
    });
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->middleware('auth')->name('logout');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
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
    Route::get('/404', fn () => response()->view('errors.404', ['navActive' => 'errors.404'], 404))->name('errors.404');
});

Route::fallback(fn () => response()->view('errors.404', ['navActive' => null], 404));
