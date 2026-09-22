<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->latest()
            ->paginate(10);

        return view('categories.index', [
            'navActive' => 'categories',
            'categories' => $categories,
            'totalCategories' => Category::count(),
            'activeCategories' => Category::where('status', 'active')->count(),
            'inactiveCategories' => Category::where('status', 'inactive')->count(),
            'totalProducts' => Category::withCount('products')->get()->sum('products_count'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $image = $request->file('image');
        $imagePath = $image->hashName('categories');

        try {
            if ($image->storeAs('categories', basename($imagePath), 'public') === false) {
                throw new RuntimeException('Unable to store category image.');
            }

            $data['image'] = $imagePath;

            if (! (new Category($data))->saveOrFail()) {
                throw new RuntimeException('Unable to create category.');
            }
        } catch (Throwable $exception) {
            $this->deleteImage($imagePath);
            report($exception);

            return back()->withInput()->withErrors([
                'image' => 'Không thể lưu danh mục hoặc ảnh. Vui lòng thử lại.',
            ]);
        }

        return to_route('categories.index')->with('success', 'Đã thêm danh mục.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);
        $newImage = null;
        $previousImage = $category->image;

        try {
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $newImage = $image->hashName('categories');

                if ($image->storeAs('categories', basename($newImage), 'public') === false) {
                    throw new RuntimeException('Unable to store category image.');
                }

                $data['image'] = $newImage;
            }

            if (! $category->fill($data)->saveOrFail()) {
                throw new RuntimeException('Unable to update category.');
            }
        } catch (Throwable $exception) {
            $this->deleteImage($newImage);
            report($exception);

            return back()->withInput()->withErrors([
                'image' => 'Không thể lưu danh mục hoặc ảnh. Vui lòng thử lại.',
            ]);
        }

        if ($newImage !== null) {
            $this->deleteImage($previousImage);
        }

        return to_route('categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return to_route('categories.index')->with('error', 'Không thể xóa danh mục đang có sản phẩm.');
        }

        try {
            if (! $category->deleteOrFail()) {
                throw new RuntimeException('Unable to delete category.');
            }
        } catch (Throwable $exception) {
            report($exception);

            return to_route('categories.index')->with('error', 'Không thể xóa danh mục. Vui lòng thử lại.');
        }

        $this->deleteImage($category->image);

        return to_route('categories.index')->with('success', 'Đã xóa danh mục.');
    }

    private function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            if (! Storage::disk('public')->delete($path)) {
                throw new RuntimeException('Unable to delete category image: '.$path);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return array{name: string, slug: string, description: string|null, status: string}
     */
    private function validatedData(Request $request, ?Category $category = null): array
    {
        $request->merge([
            'slug' => $request->filled('slug') ? Str::slug($request->input('slug')) : Str::slug($request->input('name')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'.($category ? ','.$category->id : '')],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'image' => [
                $category === null ? 'required' : 'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:min_width=100,min_height=100,max_width=6000,max_height=6000',
            ],
        ]);

        unset($data['image']);

        return $data;
    }
}
