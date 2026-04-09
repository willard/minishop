<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Tag::class);

        $tags = Tag::query()
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('admin/Tags/Index', [
            'tags' => $tags,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tag::class);

        return Inertia::render('admin/Tags/Create');
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $this->authorize('create', Tag::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Tag::query()->create($data);

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag created successfully.');
    }

    public function edit(Tag $tag): Response
    {
        $this->authorize('update', $tag);

        return Inertia::render('admin/Tags/Edit', [
            'tag' => $tag,
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $this->authorize('update', $tag);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $tag->update($data);

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag deleted successfully.');
    }
}
