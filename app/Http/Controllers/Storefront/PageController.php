<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __invoke(Page $page): Response
    {
        abort_unless($page->status->isLive(), 404);

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('storefront/Pages/Show', [
            'page' => $page->load('featuredImage'),
            'categories' => $categories,
        ]);
    }
}
