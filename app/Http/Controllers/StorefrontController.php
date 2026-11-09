<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $newestProducts = Product::query()->visible()->with('category')
            ->latest()->orderByDesc('id')->limit(4)->get();

        return view('home', compact('categories', 'newestProducts'));
    }

    public function show(string $product): View
    {
        $product = Product::query()->with('category')->whereKey($product)
            ->where('status', 'active')->firstOrFail();
        $candidates = Product::query()->visible()->with('category')
            ->where('id', '!=', $product->id)->latest()->orderByDesc('id');
        $relatedLimit = 8;
        $relatedProducts = collect();

        if (! empty($product->tags)) {
            $relatedProducts = (clone $candidates)->where(function ($query) use ($product): void {
                foreach ($product->tags as $tag) {
                    $query->orWhereJsonContains('tags', $tag);
                }
            })->limit($relatedLimit)->get();
        }

        if ($relatedProducts->count() < $relatedLimit) {
            $sameCategory = (clone $candidates)->where('category_id', $product->category_id)
                ->whereNotIn('id', $relatedProducts->pluck('id'))
                ->limit($relatedLimit - $relatedProducts->count())->get();
            $relatedProducts = $relatedProducts->concat($sameCategory);
        }

        return view('products.detail', compact('product', 'relatedProducts'));
    }

    public function search(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:60'],
        ]);
        $search = trim($data['q'] ?? '');
        $tag = Str::lower(Str::squish($data['tag'] ?? ''));
        $query = Product::query()->visible()->with('category');

        if ($search !== '') {
            // Treat SQL wildcard characters as literal user input.
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(fn ($query) => $query
                ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("sku LIKE ? ESCAPE '!'", [$pattern]));
        }

        if ($tag !== '') {
            $query->whereJsonContains('tags', $tag);
        }

        $products = $query->latest()->orderByDesc('id')->paginate(16)->withQueryString();

        return view('products.search', compact('products', 'search', 'tag'));
    }
}
