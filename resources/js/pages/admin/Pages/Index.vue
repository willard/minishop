<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    index,
    create,
    edit,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/PageController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Page {
    id: number;
    title: string;
    slug: string;
    status: 'draft' | 'published' | 'scheduled';
    published_at: string | null;
    updated_at: string;
    author: { id: number; name: string } | null;
}

interface Pagination {
    data: Page[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    pages: Pagination;
    filters: { search?: string; status?: string };
    statuses: StatusOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pages', href: index().url },
];

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function statusVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'published') return 'default';
    if (status === 'scheduled') return 'outline';
    return 'secondary';
}

function formatDate(date: string | null): string {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function confirmDelete(page: Page) {
    if (confirm(`Delete page "${page.title}"? This cannot be undone.`)) {
        router.delete(destroy(page.id).url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Pages" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Pages</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ pages.total }} page{{ pages.total === 1 ? '' : 's' }}
                    </p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Add Page
                    </Button>
                </Link>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div class="grid flex-1 gap-1">
                    <Input
                        v-model="search"
                        placeholder="Search by title"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <div class="grid gap-1">
                    <select
                        v-model="statusFilter"
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All statuses</option>
                        <option
                            v-for="s in statuses"
                            :key="s.value"
                            :value="s.value"
                        >
                            {{ s.label }}
                        </option>
                    </select>
                </div>
                <Button @click="applyFilters">Apply</Button>
            </div>

            <div class="overflow-hidden rounded-lg border border-sidebar-border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Title</th>
                            <th class="px-4 py-3 text-left font-medium">Slug</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Author</th>
                            <th class="px-4 py-3 text-left font-medium">Updated</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="pages.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No pages yet.
                                <Link
                                    :href="create().url"
                                    class="ml-1 text-primary underline"
                                    >Create your first page</Link
                                >
                            </td>
                        </tr>
                        <tr
                            v-for="page in pages.data"
                            :key="page.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">{{ page.title }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                /pages/{{ page.slug }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(page.status)">
                                    {{ page.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ page.author?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDate(page.updated_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a
                                        :href="`/pages/${page.slug}`"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <ExternalLink class="size-4" />
                                        </Button>
                                    </a>
                                    <Link :href="edit(page.id).url">
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="size-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(page)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pages.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in pages.links" :key="link.label">
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
