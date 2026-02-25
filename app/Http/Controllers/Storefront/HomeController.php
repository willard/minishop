<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->with(['categories', 'images'])
            ->latest()
            ->limit(8)
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('storefront/Home', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
        ]);
    }
}
