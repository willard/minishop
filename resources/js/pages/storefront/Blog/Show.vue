<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { StorefrontCategory } from '@/types/storefront';

interface Tag {
    id: number;
    name: string;
    slug: string;
    color: string | null;
}

interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    body: string;
    published_at: string | null;
    meta_title: string | null;
    meta_description: string | null;
    featured_image: { url: string; alt_text: string | null } | null;
    author: { id: number; name: string } | null;
    tags: Tag[];
}

const props = defineProps<{
    post: PostItem;
    related: PostItem[];
    categories: StorefrontCategory[];
}>();

const headTitle = props.post.meta_title ?? props.post.title;
const headDescription = props.post.meta_description ?? props.post.excerpt;

function formatDate(date: string | null): string {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <Head>
        <title>{{ headTitle }}</title>
        <meta v-if="headDescription" name="description" :content="headDescription" />
    </Head>

    <StorefrontLayout :categories="categories">
        <article class="mx-auto max-w-3xl px-6 py-12">
            <Link
                href="/blog"
                class="mb-8 inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
                Back to blog
            </Link>

            <header class="mb-10">
                <div class="mb-4 flex flex-wrap gap-1">
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
                <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                    {{ post.title }}
                </h1>
                <p class="mt-4 text-lg text-muted-foreground">
                    {{ post.excerpt }}
                </p>
                <div class="mt-4 flex items-center gap-3 text-sm text-muted-foreground">
                    <span v-if="post.author">{{ post.author.name }}</span>
                    <span v-if="post.author && post.published_at">·</span>
                    <time v-if="post.published_at">
                        {{ formatDate(post.published_at) }}
                    </time>
                </div>
            </header>

            <figure v-if="post.featured_image" class="mb-10">
                <img
                    :src="post.featured_image.url"
                    :alt="post.featured_image.alt_text ?? post.title"
                    class="w-full rounded-lg"
                />
            </figure>

            <div
                class="prose prose-neutral max-w-none dark:prose-invert prose-headings:tracking-tight prose-a:text-primary"
                v-html="post.body"
            />

            <section
                v-if="related.length"
                class="mt-16 border-t border-sidebar-border pt-12"
            >
                <h2 class="mb-6 text-2xl font-semibold">Related posts</h2>
                <div class="grid gap-6 sm:grid-cols-3">
                    <Link
                        v-for="r in related"
                        :key="r.id"
                        :href="`/blog/${r.slug}`"
                        class="group flex flex-col gap-2 overflow-hidden rounded-lg border border-sidebar-border"
                    >
                        <div
                            v-if="r.featured_image"
                            class="aspect-video overflow-hidden bg-muted"
                        >
                            <img
                                :src="r.featured_image.url"
                                :alt="r.featured_image.alt_text ?? r.title"
                                class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                loading="lazy"
                            />
                        </div>
                        <div class="px-3 pb-3">
                            <h3
                                class="text-sm font-semibold group-hover:text-primary"
                            >
                                {{ r.title }}
                            </h3>
                        </div>
                    </Link>
                </div>
            </section>
        </article>
    </StorefrontLayout>
</template>
