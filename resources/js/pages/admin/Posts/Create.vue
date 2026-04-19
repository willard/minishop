<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Admin/PostController';
import InputError from '@/components/InputError.vue';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Option {
    value: string;
    label: string;
}

interface TagOption {
    id: number;
    name: string;
    color: string | null;
}

defineProps<{
    statuses: Option[];
    tags: TagOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Blog Posts', href: index().url },
    { title: 'Add Post', href: create().url },
];

const form = useForm({
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    status: 'draft',
    published_at: '',
    meta_title: '',
    meta_description: '',
    featured_image_id: '' as string | number,
    tag_ids: [] as number[],
});

function toggleTag(id: number) {
    if (form.tag_ids.includes(id)) {
        form.tag_ids = form.tag_ids.filter((t) => t !== id);
    } else {
        form.tag_ids = [...form.tag_ids, id];
    }
}

function submit() {
    form.post(store().url);
}
</script>

<template>
    <Head title="Add Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-4xl flex-col gap-6 p-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Add Post</h1>
                    <p class="text-sm text-muted-foreground">
                        Write a new blog post
                    </p>
                </div>
            </div>

            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="title">Title <span class="text-destructive">*</span></Label>
                    <Input id="title" v-model="form.title" required />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">Slug (auto-generated if blank)</Label>
                    <Input id="slug" v-model="form.slug" />
                    <InputError :message="form.errors.slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="excerpt"
                        >Excerpt <span class="text-destructive">*</span></Label
                    >
                    <textarea
                        id="excerpt"
                        v-model="form.excerpt"
                        rows="3"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        required
                    />
                    <InputError :message="form.errors.excerpt" />
                </div>

                <div class="grid gap-2">
                    <Label>Body <span class="text-destructive">*</span></Label>
                    <TiptapEditor v-model="form.body" />
                    <InputError :message="form.errors.body" />
                </div>

                <div class="grid gap-2">
                    <Label>Tags</Label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tag in tags"
                            :key="tag.id"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs transition-colors"
                            :class="
                                form.tag_ids.includes(tag.id)
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-sidebar-border text-muted-foreground hover:border-primary'
                            "
                            @click="toggleTag(tag.id)"
                        >
                            {{ tag.name }}
                        </button>
                    </div>
                    <InputError :message="form.errors.tag_ids" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option
                                v-for="s in statuses"
                                :key="s.value"
                                :value="s.value"
                            >
                                {{ s.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="published_at">Publish date</Label>
                        <Input
                            id="published_at"
                            v-model="form.published_at"
                            type="datetime-local"
                        />
                        <InputError :message="form.errors.published_at" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="featured_image_id">Featured image ID (from Media Library)</Label>
                    <Input
                        id="featured_image_id"
                        v-model="form.featured_image_id"
                        type="number"
                        min="1"
                        class="max-w-xs"
                    />
                    <InputError :message="form.errors.featured_image_id" />
                </div>

                <div class="border-t border-sidebar-border pt-6">
                    <h2 class="mb-4 text-lg font-semibold">SEO</h2>
                    <div class="flex flex-col gap-4">
                        <div class="grid gap-2">
                            <Label for="meta_title">Meta title</Label>
                            <Input id="meta_title" v-model="form.meta_title" />
                            <InputError :message="form.errors.meta_title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="meta_description">Meta description</Label>
                            <textarea
                                id="meta_description"
                                v-model="form.meta_description"
                                rows="2"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <InputError :message="form.errors.meta_description" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating…' : 'Create Post' }}
                    </Button>
                    <Link :href="index().url">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
