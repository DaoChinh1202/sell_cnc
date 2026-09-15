<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

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
        Category::create($data);

        return to_route('categories.index')->with('success', 'Đã thêm danh mục.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);
        $category->update($data);

        return to_route('categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return to_route('categories.index')->with('error', 'Không thể xóa danh mục đang có sản phẩm.');
        }

        $category->delete();

        return to_route('categories.index')->with('success', 'Đã xóa danh mục.');
    }

    /**
     * @return array{name: string, slug: string, description: string|null, status: string}
     */
    private function validatedData(Request $request, ?Category $category = null): array
    {
        $request->merge([
            'slug' => $request->filled('slug') ? Str::slug($request->input('slug')) : Str::slug($request->input('name')),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'.($category ? ','.$category->id : '')],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
