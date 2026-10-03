<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        return response()->view('seo.sitemap', [
            'categories' => Category::query()->where('status', 'active')->orderBy('id')->lazyById(),
            'products' => Product::query()->visible()->orderBy('id')->lazyById(),
        ], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        // Served dynamically: a public/robots.txt file would bypass this route.
        return response()->view('seo.robots', [], 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
