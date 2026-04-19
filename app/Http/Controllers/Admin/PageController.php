<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Page::class);

        $pages = Page::query()
            ->with('author:id,name')
            ->when(request('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Pages/Index', [
            'pages' => $pages,
            'filters' => request()->only(['search', 'status']),
            'statuses' => collect(PublishStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Page::class);

        return Inertia::render('admin/Pages/Create', [
            'statuses' => collect(PublishStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'templates' => collect(PageTemplate::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $this->authorize('create', Page::class);

        $data = $request->validated();
        $data['author_id'] = $request->user()->id;
        if (($data['status'] ?? null) === PublishStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        Page::query()->create($data);

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page): Response
    {
        $this->authorize('update', $page);

        return Inertia::render('admin/Pages/Edit', [
            'page' => $page->load('featuredImage'),
            'statuses' => collect(PublishStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'templates' => collect(PageTemplate::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $this->authorize('update', $page);

        $data = $request->validated();
        if (($data['status'] ?? null) === PublishStatus::Published->value && empty($data['published_at']) && $page->published_at === null) {
            $data['published_at'] = now();
        }

        $page->update($data);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }
}
