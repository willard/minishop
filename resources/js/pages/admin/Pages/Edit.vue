<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import {
    index,
    edit,
    update,
} from '@/actions/App/Http/Controllers/Admin/PageController';
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

interface PageItem {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    status: string;
    published_at: string | null;
    template: string;
    meta_title: string | null;
    meta_description: string | null;
    featured_image_id: number | null;
}

const props = defineProps<{
    page: PageItem;
    statuses: Option[];
    templates: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pages', href: index().url },
    { title: 'Edit', href: edit(props.page.id).url },
];

const form = useForm({
    title: props.page.title,
    slug: props.page.slug,
    excerpt: props.page.excerpt ?? '',
    body: props.page.body ?? '',
    status: props.page.status,
    published_at: props.page.published_at
        ? props.page.published_at.slice(0, 16)
        : '',
    template: props.page.template,
    meta_title: props.page.meta_title ?? '',
    meta_description: props.page.meta_description ?? '',
    featured_image_id: props.page.featured_image_id ?? ('' as number | string),
});

function submit() {
    form.put(update(props.page.id).url);
}
</script>

<template>
    <Head :title="`Edit: ${page.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-4xl flex-col gap-6 p-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Edit Page</h1>
                    <p class="text-sm text-muted-foreground">{{ page.title }}</p>
                </div>
            </div>

            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="title">Title <span class="text-destructive">*</span></Label>
                    <Input id="title" v-model="form.title" required />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">Slug</Label>
                    <Input id="slug" v-model="form.slug" />
                    <InputError :message="form.errors.slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="excerpt">Excerpt</Label>
                    <textarea
                        id="excerpt"
                        v-model="form.excerpt"
                        rows="2"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <InputError :message="form.errors.excerpt" />
                </div>

                <div class="grid gap-2">
                    <Label>Body <span class="text-destructive">*</span></Label>
                    <TiptapEditor v-model="form.body" />
                    <InputError :message="form.errors.body" />
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
                        <Label for="template">Template</Label>
                        <select
                            id="template"
                            v-model="form.template"
                            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option
                                v-for="t in templates"
                                :key="t.value"
                                :value="t.value"
                            >
                                {{ t.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.template" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="published_at">Publish date</Label>
                    <Input
                        id="published_at"
                        v-model="form.published_at"
                        type="datetime-local"
                        class="max-w-xs"
                    />
                    <InputError :message="form.errors.published_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="featured_image_id">Featured image ID</Label>
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
                        {{ form.processing ? 'Saving…' : 'Save changes' }}
                    </Button>
                    <Link :href="index().url">
                        <Button type="button" variant="ghost">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
