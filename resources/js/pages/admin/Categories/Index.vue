<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderPlus, Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { index, create, edit, destroy } from '@/actions/App/Http/Controllers/Admin/CategoryController';

interface Category {
    id: number;
    name: string;
    slug: string;
    sort_order: number;
    is_active: boolean;
    parent: { id: number; name: string } | null;
}

interface Pagination {
    data: Category[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    categories: Pagination;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Categories', href: index().url },
];

function confirmDelete(category: Category): void {
    if (confirm(`Delete "${category.name}"? This cannot be undone.`)) {
        router.delete(destroy(category).url);
    }
}
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Categories</h1>
                    <p class="text-sm text-muted-foreground">{{ categories.total }} total categories</p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <FolderPlus class="mr-2 size-4" />
                        Add Category
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <div class="rounded-lg border border-sidebar-border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Slug</th>
                            <th class="px-4 py-3 text-left font-medium">Parent</th>
                            <th class="px-4 py-3 text-left font-medium">Order</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="categories.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                No categories yet.
                                <Link :href="create().url" class="text-primary underline ml-1">Add your first category</Link>
                            </td>
                        </tr>
                        <tr
                            v-for="category in categories.data"
                            :key="category.id"
                            class="hover:bg-muted/30 transition-colors"
                        >
                            <td class="px-4 py-3 font-medium">{{ category.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground font-mono text-xs">{{ category.slug }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ category.parent?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ category.sort_order }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="category.is_active ? 'default' : 'secondary'">
                                    {{ category.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="edit(category).url">
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="size-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(category)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="categories.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in categories.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded text-sm border border-sidebar-border hover:bg-muted/50 transition-colors"
                        :class="{ 'bg-primary text-primary-foreground border-primary': link.active }"
                    ><span v-html="link.label" /></Link>
                    <span
                        v-else
                        class="px-3 py-1.5 rounded text-sm border border-sidebar-border text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
