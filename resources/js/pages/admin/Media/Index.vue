<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { File, Trash2, Upload } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    index,
    store,
    update,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/MediaController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface MediaItem {
    id: number;
    disk: string;
    path: string;
    url: string;
    original_name: string;
    mime_type: string;
    size: number;
    alt_text: string | null;
    created_at: string;
    uploader: { id: number; name: string } | null;
}

interface Pagination {
    data: MediaItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    media: Pagination;
    filters: { search?: string; type?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Media', href: index().url },
];

const search = ref(props.filters.search ?? '');
const typeFilter = ref(props.filters.type ?? '');

const uploadForm = useForm({
    file: null as File | null,
    alt_text: '',
});

const editingId = ref<number | null>(null);
const editAlt = ref('');

function applyFilters() {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function onFileChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) uploadForm.file = file;
}

function submitUpload() {
    if (!uploadForm.file) return;
    uploadForm.post(store().url, {
        forceFormData: true,
        onSuccess: () => uploadForm.reset(),
    });
}

function startEdit(item: MediaItem) {
    editingId.value = item.id;
    editAlt.value = item.alt_text ?? '';
}

function saveEdit(item: MediaItem) {
    router.put(
        update(item.id).url,
        { alt_text: editAlt.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null;
            },
        },
    );
}

function confirmDelete(item: MediaItem) {
    if (confirm(`Delete "${item.original_name}"? This cannot be undone.`)) {
        router.delete(destroy(item.id).url, { preserveScroll: true });
    }
}

function formatSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function isImage(mime: string): boolean {
    return mime.startsWith('image/');
}
</script>

<template>
    <Head title="Media Library" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Media Library</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ media.total }} file{{ media.total === 1 ? '' : 's' }}
                    </p>
                </div>
            </div>

            <!-- Upload -->
            <form
                @submit.prevent="submitUpload"
                class="flex flex-col gap-3 rounded-lg border border-sidebar-border bg-muted/20 p-4"
            >
                <div class="grid gap-2">
                    <Label for="file">Upload file</Label>
                    <Input
                        id="file"
                        type="file"
                        accept="image/*,application/pdf"
                        @change="onFileChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        Max 5 MB. JPG, PNG, WEBP, GIF, SVG, or PDF.
                    </p>
                    <InputError :message="uploadForm.errors.file" />
                </div>
                <div class="grid gap-2">
                    <Label for="alt_text">Alt text (optional)</Label>
                    <Input
                        id="alt_text"
                        v-model="uploadForm.alt_text"
                        placeholder="Describe the image for accessibility"
                    />
                </div>
                <div>
                    <Button
                        type="submit"
                        :disabled="!uploadForm.file || uploadForm.processing"
                    >
                        <Upload class="mr-2 size-4" />
                        {{ uploadForm.processing ? 'Uploading...' : 'Upload' }}
                    </Button>
                </div>
            </form>

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid flex-1 gap-1">
                    <Label for="search">Search</Label>
                    <Input
                        id="search"
                        v-model="search"
                        placeholder="Search filename"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <div class="grid gap-1">
                    <Label for="type">Type</Label>
                    <select
                        id="type"
                        v-model="typeFilter"
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All</option>
                        <option value="image">Images</option>
                        <option value="document">Documents</option>
                    </select>
                </div>
                <Button type="button" @click="applyFilters">Apply</Button>
            </div>

            <!-- Grid -->
            <div
                v-if="media.data.length === 0"
                class="rounded-lg border border-dashed border-sidebar-border p-12 text-center text-muted-foreground"
            >
                No media uploaded yet.
            </div>
            <div
                v-else
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
            >
                <div
                    v-for="item in media.data"
                    :key="item.id"
                    class="flex flex-col gap-2 rounded-lg border border-sidebar-border bg-background p-2"
                >
                    <div
                        class="relative aspect-square overflow-hidden rounded-md bg-muted"
                    >
                        <img
                            v-if="isImage(item.mime_type)"
                            :src="item.url"
                            :alt="item.alt_text ?? item.original_name"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-muted-foreground"
                        >
                            <File class="size-10" />
                        </div>
                    </div>
                    <div class="truncate text-xs font-medium" :title="item.original_name">
                        {{ item.original_name }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ formatSize(item.size) }}
                    </div>
                    <template v-if="editingId === item.id">
                        <Input
                            v-model="editAlt"
                            placeholder="Alt text"
                            class="h-8 text-xs"
                        />
                        <div class="flex gap-1">
                            <Button
                                type="button"
                                size="sm"
                                class="h-7 flex-1 text-xs"
                                @click="saveEdit(item)"
                            >
                                Save
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-7 text-xs"
                                @click="editingId = null"
                            >
                                Cancel
                            </Button>
                        </div>
                    </template>
                    <template v-else>
                        <button
                            type="button"
                            class="truncate text-left text-xs text-muted-foreground hover:text-foreground"
                            @click="startEdit(item)"
                        >
                            {{ item.alt_text || 'Add alt text…' }}
                        </button>
                        <div class="flex items-center justify-between">
                            <span class="truncate text-[10px] text-muted-foreground">
                                {{ item.uploader?.name ?? '—' }}
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="size-7 p-0 text-destructive hover:text-destructive"
                                @click="confirmDelete(item)"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="media.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in media.links" :key="link.label">
                    <a
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border border-sidebar-border px-3 py-1.5 text-sm transition-colors hover:bg-muted/50"
                        :class="{
                            'border-primary bg-primary text-primary-foreground':
                                link.active,
                        }"
                        v-html="link.label"
                    />
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
