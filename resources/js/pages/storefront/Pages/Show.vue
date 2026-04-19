<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { StorefrontCategory } from '@/types/storefront';

interface PageData {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    template: 'default' | 'full_width' | 'landing';
    meta_title: string | null;
    meta_description: string | null;
    featured_image: { id: number; url: string; alt_text: string | null } | null;
    published_at: string | null;
}

const props = defineProps<{
    page: PageData;
    categories: StorefrontCategory[];
}>();

const headTitle = props.page.meta_title ?? props.page.title;
const headDescription = props.page.meta_description ?? props.page.excerpt ?? '';
</script>

<template>
    <Head>
        <title>{{ headTitle }}</title>
        <meta v-if="headDescription" name="description" :content="headDescription" />
    </Head>

    <StorefrontLayout :categories="categories">
        <article
            :class="[
                'mx-auto w-full px-6 py-12',
                page.template === 'full_width'
                    ? 'max-w-6xl'
                    : page.template === 'landing'
                      ? 'max-w-5xl'
                      : 'max-w-3xl',
            ]"
        >
            <header class="mb-10">
                <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                    {{ page.title }}
                </h1>
                <p v-if="page.excerpt" class="mt-4 text-lg text-muted-foreground">
                    {{ page.excerpt }}
                </p>
            </header>

            <figure v-if="page.featured_image" class="mb-10">
                <img
                    :src="page.featured_image.url"
                    :alt="page.featured_image.alt_text ?? page.title"
                    class="w-full rounded-lg"
                />
            </figure>

            <div
                class="prose prose-neutral max-w-none dark:prose-invert prose-headings:tracking-tight prose-a:text-primary"
                v-html="page.body"
            />
        </article>
    </StorefrontLayout>
</template>
