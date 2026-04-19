<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::query()
            ->with(['author:id,name', 'featuredImage', 'tags:id,name,color'])
            ->when(request('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Posts/Index', [
            'posts' => $posts,
            'filters' => request()->only(['search', 'status']),
            'statuses' => collect(PublishStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Post::class);

        return Inertia::render('admin/Posts/Create', [
            'statuses' => collect(PublishStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'tags' => Tag::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $data = $request->validated();
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);
        $data['author_id'] = $request->user()->id;
        if (($data['status'] ?? null) === PublishStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $post = Post::query()->create($data);
        $post->tags()->sync($tagIds);

        return redirect()->route('admin.posts.index')->with('success', 'Post created.');
    }

    public function edit(Post $post): Response
    {
        $this->authorize('update', $post);

        return Inertia::render('admin/Posts/Edit', [
            'post' => $post->load(['featuredImage', 'tags:id']),
            'statuses' => collect(PublishStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'tags' => Tag::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $data = $request->validated();
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);
        if (($data['status'] ?? null) === PublishStatus::Published->value && empty($data['published_at']) && $post->published_at === null) {
            $data['published_at'] = now();
        }

        $post->update($data);
        $post->tags()->sync($tagIds);

        return redirect()->route('admin.posts.index')->with('success', 'Post updated.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted.');
    }
}
