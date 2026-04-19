<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    index,
    create,
    edit,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/PostController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Post {
    id: number;
    title: string;
    slug: string;
    status: 'draft' | 'published' | 'scheduled';
    published_at: string | null;
    updated_at: string;
    author: { id: number; name: string } | null;
    tags: { id: number; name: string; color: string | null }[];
}

interface Pagination {
    data: Post[];
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
    posts: Pagination;
    filters: { search?: string; status?: string };
    statuses: StatusOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Blog Posts', href: index().url },
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

function confirmDelete(post: Post) {
    if (confirm(`Delete post "${post.title}"? This cannot be undone.`)) {
        router.delete(destroy(post.id).url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Blog Posts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Blog Posts</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ posts.total }} post{{ posts.total === 1 ? '' : 's' }}
                    </p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Add Post
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
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Tags</th>
                            <th class="px-4 py-3 text-left font-medium">Author</th>
                            <th class="px-4 py-3 text-left font-medium">Updated</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="posts.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No posts yet.
                                <Link
                                    :href="create().url"
                                    class="ml-1 text-primary underline"
                                    >Create your first post</Link
                                >
                            </td>
                        </tr>
                        <tr
                            v-for="post in posts.data"
                            :key="post.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">{{ post.title }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(post.status)">
                                    {{ post.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="tag in post.tags"
                                        :key="tag.id"
                                        class="inline-flex items-center rounded px-2 py-0.5 text-xs"
                                        :style="{
                                            backgroundColor: tag.color ?? '#e5e7eb',
                                            color: '#ffffff',
                                        }"
                                    >
                                        {{ tag.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ post.author?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDate(post.updated_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a
                                        :href="`/blog/${post.slug}`"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <ExternalLink class="size-4" />
                                        </Button>
                                    </a>
                                    <Link :href="edit(post.id).url">
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="size-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(post)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="posts.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in posts.links" :key="link.label">
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
