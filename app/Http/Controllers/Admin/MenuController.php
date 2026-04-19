<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MenuLocation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderMenuItemsRequest;
use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', MenuItem::class);

        $items = MenuItem::query()
            ->orderBy('menu_location')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $grouped = collect(MenuLocation::cases())->mapWithKeys(fn (MenuLocation $loc) => [
            $loc->value => [
                'label' => $loc->label(),
                'items' => $items->where('menu_location', $loc)->values(),
            ],
        ]);

        return Inertia::render('admin/Menus/Index', [
            'menus' => $grouped,
            'locations' => collect(MenuLocation::cases())->map(fn ($l) => ['value' => $l->value, 'label' => $l->label()]),
        ]);
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        $this->authorize('update', MenuItem::class);

        MenuItem::query()->create($request->validated());

        return redirect()->route('admin.menus.index')->with('success', 'Menu item added.');
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menu): RedirectResponse
    {
        $this->authorize('update', MenuItem::class);

        $menu->update($request->validated());

        return redirect()->route('admin.menus.index')->with('success', 'Menu item updated.');
    }

    public function destroy(MenuItem $menu): RedirectResponse
    {
        $this->authorize('update', MenuItem::class);

        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'Menu item removed.');
    }

    public function reorder(ReorderMenuItemsRequest $request): RedirectResponse
    {
        $this->authorize('update', MenuItem::class);

        foreach ($request->validated('items') as $entry) {
            MenuItem::query()->where('id', $entry['id'])->update(['sort_order' => $entry['sort_order']]);
        }

        return redirect()->route('admin.menus.index')->with('success', 'Order updated.');
    }
}
