<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        $tagSlug = request('tag');

        $posts = Post::query()
            ->published()
            ->with(['featuredImage', 'tags:id,name,slug,color', 'author:id,name'])
            ->when($tagSlug, function ($q, $slug) {
                $q->whereHas('tags', fn ($t) => $t->where('slug', $slug));
            })
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $tags = Tag::query()
            ->where('is_active', true)
            ->whereHas('posts', fn ($q) => $q->published())
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color']);

        return Inertia::render('storefront/Blog/Index', [
            'posts' => $posts,
            'tags' => $tags,
            'activeTag' => $tagSlug,
            'categories' => $this->storefrontCategories(),
        ]);
    }

    public function show(Post $post): Response
    {
        abort_unless($post->status->isLive(), 404);

        $post->load(['featuredImage', 'tags:id,name,slug,color', 'author:id,name']);

        $related = Post::query()
            ->published()
            ->whereKeyNot($post->id)
            ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $post->tags->pluck('id')))
            ->with(['featuredImage', 'tags:id,name,slug,color'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('storefront/Blog/Show', [
            'post' => $post,
            'related' => $related,
            'categories' => $this->storefrontCategories(),
        ]);
    }

    /**
     * @return Collection<int, Category>
     */
    private function storefrontCategories()
    {
        return Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
