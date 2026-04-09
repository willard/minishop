<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import {
    index,
    create,
    edit,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/TagController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Tag {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    is_active: boolean;
}

interface Pagination {
    data: Tag[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    tags: Pagination;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tags', href: index().url },
];

function confirmDelete(tag: Tag): void {
    if (confirm(`Delete "${tag.name}"? This cannot be undone.`)) {
        router.delete(destroy(tag).url);
    }
}
</script>

<template>
    <Head title="Tags" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Tags</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ tags.total }} total tags
                    </p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Add Tag
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border"
            >
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Name
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Slug
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Color
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Status
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="tags.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No tags yet.
                                <Link
                                    :href="create().url"
                                    class="ml-1 text-primary underline"
                                    >Add your first tag</Link
                                >
                            </td>
                        </tr>
                        <tr
                            v-for="tag in tags.data"
                            :key="tag.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ tag.name }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ tag.slug }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="tag.color"
                                    class="inline-block size-5 rounded-full border border-sidebar-border"
                                    :style="{ backgroundColor: tag.color }"
                                />
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        tag.is_active ? 'default' : 'secondary'
                                    "
                                >
                                    {{ tag.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="edit(tag).url">
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="size-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(tag)"
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
            <div
                v-if="tags.last_page > 1"
                class="flex justify-center gap-1"
            >
                <template v-for="link in tags.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border border-sidebar-border px-3 py-1.5 text-sm transition-colors hover:bg-muted/50"
                        :class="{
                            'border-primary bg-primary text-primary-foreground':
                                link.active,
                        }"
                        ><span v-html="link.label"
                    /></Link>
                    <span
                        v-else
                        class="rounded border border-sidebar-border px-3 py-1.5 text-sm text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
