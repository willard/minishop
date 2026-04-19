<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    index,
    store,
    update,
    destroy,
    reorder,
} from '@/actions/App/Http/Controllers/Admin/MenuController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface MenuItem {
    id: number;
    menu_location: string;
    label: string;
    url: string;
    target: string;
    sort_order: number;
    parent_id: number | null;
}

interface MenuGroup {
    label: string;
    items: MenuItem[];
}

interface LocationOption {
    value: string;
    label: string;
}

const props = defineProps<{
    menus: Record<string, MenuGroup>;
    locations: LocationOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Menus', href: index().url },
];

const activeLocation = ref(props.locations[0]?.value ?? '');

const newItemForm = useForm({
    menu_location: activeLocation.value,
    label: '',
    url: '',
    target: '_self',
    sort_order: 0,
});

const editingId = ref<number | null>(null);
const editForm = useForm({
    label: '',
    url: '',
    target: '_self',
    sort_order: 0,
});

function switchLocation(value: string) {
    activeLocation.value = value;
    newItemForm.menu_location = value;
}

function addItem() {
    const group = props.menus[activeLocation.value];
    newItemForm.sort_order = (group?.items.length ?? 0) + 1;
    newItemForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => newItemForm.reset('label', 'url'),
    });
}

function startEdit(item: MenuItem) {
    editingId.value = item.id;
    editForm.label = item.label;
    editForm.url = item.url;
    editForm.target = item.target;
    editForm.sort_order = item.sort_order;
}

function saveEdit(item: MenuItem) {
    editForm.put(update(item.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function removeItem(item: MenuItem) {
    if (confirm(`Delete "${item.label}"?`)) {
        router.delete(destroy(item.id).url, { preserveScroll: true });
    }
}

function move(items: MenuItem[], index: number, direction: -1 | 1) {
    const target = index + direction;
    if (target < 0 || target >= items.length) return;
    const reordered = items.map((item) => ({ id: item.id, sort_order: item.sort_order }));
    const tmp = reordered[index].sort_order;
    reordered[index].sort_order = reordered[target].sort_order;
    reordered[target].sort_order = tmp;
    router.post(
        reorder().url,
        { items: reordered },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Menus" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold">Navigation Menus</h1>
                <p class="text-sm text-muted-foreground">
                    Edit the links in your storefront header and footer.
                </p>
            </div>

            <!-- Tabs -->
            <div class="flex flex-wrap gap-2 border-b border-sidebar-border">
                <button
                    v-for="loc in locations"
                    :key="loc.value"
                    type="button"
                    class="border-b-2 px-4 py-2 text-sm transition-colors"
                    :class="
                        activeLocation === loc.value
                            ? 'border-primary font-medium text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="switchLocation(loc.value)"
                >
                    {{ loc.label }}
                </button>
            </div>

            <!-- Items list -->
            <div
                class="flex flex-col gap-2 rounded-lg border border-sidebar-border p-4"
            >
                <div
                    v-if="!menus[activeLocation] || menus[activeLocation].items.length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    No items in this menu yet.
                </div>
                <div
                    v-for="(item, idx) in menus[activeLocation]?.items ?? []"
                    :key="item.id"
                    class="flex flex-col gap-2 rounded-md border border-sidebar-border bg-background p-3"
                >
                    <template v-if="editingId === item.id">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-1">
                                <Label>Label</Label>
                                <Input v-model="editForm.label" />
                                <InputError :message="editForm.errors.label" />
                            </div>
                            <div class="grid gap-1">
                                <Label>URL</Label>
                                <Input v-model="editForm.url" />
                                <InputError :message="editForm.errors.url" />
                            </div>
                            <div class="grid gap-1">
                                <Label>Target</Label>
                                <select
                                    v-model="editForm.target"
                                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="_self">Same window</option>
                                    <option value="_blank">New window</option>
                                </select>
                            </div>
                            <div class="grid gap-1">
                                <Label>Sort order</Label>
                                <Input v-model="editForm.sort_order" type="number" />
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                size="sm"
                                :disabled="editForm.processing"
                                @click="saveEdit(item)"
                            >
                                Save
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="editingId = null"
                            >
                                <X class="size-4" />
                            </Button>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-3">
                            <div class="flex flex-col">
                                <button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground disabled:opacity-30"
                                    :disabled="idx === 0"
                                    @click="move(menus[activeLocation].items, idx, -1)"
                                >
                                    <ChevronUp class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground disabled:opacity-30"
                                    :disabled="
                                        idx === (menus[activeLocation]?.items.length ?? 0) - 1
                                    "
                                    @click="move(menus[activeLocation].items, idx, 1)"
                                >
                                    <ChevronDown class="size-4" />
                                </button>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium">{{ item.label }}</div>
                                <div class="font-mono text-xs text-muted-foreground">
                                    {{ item.url }}
                                    <span v-if="item.target === '_blank'" class="ml-2">
                                        ↗ new window
                                    </span>
                                </div>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="startEdit(item)"
                            >
                                <Pencil class="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                @click="removeItem(item)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Add form -->
            <form
                class="flex flex-col gap-3 rounded-lg border border-sidebar-border bg-muted/20 p-4"
                @submit.prevent="addItem"
            >
                <h2 class="font-semibold">Add menu item</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="grid gap-1">
                        <Label for="new-label">Label</Label>
                        <Input
                            id="new-label"
                            v-model="newItemForm.label"
                            placeholder="e.g. About"
                            required
                        />
                        <InputError :message="newItemForm.errors.label" />
                    </div>
                    <div class="grid gap-1">
                        <Label for="new-url">URL</Label>
                        <Input
                            id="new-url"
                            v-model="newItemForm.url"
                            placeholder="/pages/about or https://..."
                            required
                        />
                        <InputError :message="newItemForm.errors.url" />
                    </div>
                    <div class="grid gap-1">
                        <Label for="new-target">Target</Label>
                        <select
                            id="new-target"
                            v-model="newItemForm.target"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="_self">Same window</option>
                            <option value="_blank">New window</option>
                        </select>
                    </div>
                </div>
                <div>
                    <Button type="submit" :disabled="newItemForm.processing">
                        <Plus class="mr-2 size-4" />
                        Add item
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
