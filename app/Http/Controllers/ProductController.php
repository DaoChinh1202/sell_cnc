<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $products = Product::query()
            ->with('category')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('inventory', [
            'navActive' => 'inventory',
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'navActive' => 'products.create',
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ProductImageService $productImages): RedirectResponse
    {
        $data = $this->validatedData($request);

        try {
            $data['image'] = $productImages->storeWatermarked($request->file('image'));
            Product::query()->create($data);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'image' => 'Không thể xử lý ảnh. Vui lòng thử lại bằng ảnh JPEG, PNG hoặc WebP khác.',
            ]);
        }

        return to_route('inventory')->with('success', 'Đã thêm sản phẩm. Ảnh được lưu dưới dạng WebP có watermark.');
    }

    public function show(Product $product): View
    {
        return view('products.show', [
            'navActive' => 'inventory',
            'product' => $product->load('category'),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'navActive' => 'inventory',
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product, ProductImageService $productImages): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $newImage = null;
        $previousImage = $product->image;

        try {
            if ($request->hasFile('image')) {
                $newImage = $productImages->storeWatermarked($request->file('image'));
                $data['image'] = $newImage;
            }

            $product->update($data);
        } catch (Throwable $exception) {
            if ($newImage !== null) {
                Storage::disk('public')->delete($newImage);
            }

            report($exception);

            return back()->withInput()->withErrors([
                'image' => 'Không thể xử lý ảnh. Vui lòng thử lại bằng ảnh JPEG, PNG hoặc WebP khác.',
            ]);
        }

        if ($newImage !== null) {
            Storage::disk('public')->delete($previousImage);
        }

        return to_route('products.show', $product)->with('success', 'Đã cập nhật sản phẩm.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive,draft'],
            'is_featured' => ['sometimes', 'boolean'],
            'image' => [
                $product === null ? 'required' : 'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:min_width=100,min_height=100,max_width=6000,max_height=6000',
            ],
        ]);

        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }
}
