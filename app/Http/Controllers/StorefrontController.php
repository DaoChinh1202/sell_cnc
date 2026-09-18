<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->where('status', 'active')
            ->withCount(['products' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $categorySections = $categories->where('products_count', '>', 0)->take(8)->values();
        $categorySections->load(['products' => fn ($query) => $query
            ->where('status', 'active')->with('category')->latest()->orderByDesc('id')->limit(8)]);

        $newestProducts = Product::query()->visible()->with('category')
            ->latest()->orderByDesc('id')->limit(4)->get();

        return view('home', compact('categories', 'categorySections', 'newestProducts'));
    }

    public function search(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['q'] ?? '');
        $query = Product::query()->visible()->with('category');

        if ($search !== '') {
            // Treat SQL wildcard characters as literal user input.
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(fn ($query) => $query
                ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("sku LIKE ? ESCAPE '!'", [$pattern]));
        }

        $products = $query->latest()->orderByDesc('id')->paginate(16)->withQueryString();

        return view('products.search', compact('products', 'search'));
    }
}
