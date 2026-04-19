<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { StorefrontCategory } from '@/types/storefront';

interface Author {
    id: number;
    name: string;
}

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
    published_at: string | null;
    featured_image: { url: string; alt_text: string | null } | null;
    author: Author | null;
    tags: Tag[];
}

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    posts: Pagination<PostItem>;
    tags: Tag[];
    activeTag: string | null;
    categories: StorefrontCategory[];
}>();

function filterByTag(slug: string | null) {
    router.get(
        '/blog',
        slug ? { tag: slug } : {},
        { preserveScroll: true, preserveState: true },
    );
}

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
    <Head title="Blog" />

    <StorefrontLayout :categories="categories">
        <section class="mx-auto max-w-6xl px-6 py-12">
            <header class="mb-10">
                <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                    Blog
                </h1>
                <p class="mt-3 text-lg text-muted-foreground">
                    News, stories, and guides from our team.
                </p>
            </header>

            <div v-if="tags.length" class="mb-10 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm transition-colors"
                    :class="
                        !activeTag
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-sidebar-border text-muted-foreground hover:border-primary'
                    "
                    @click="filterByTag(null)"
                >
                    All
                </button>
                <button
                    v-for="tag in tags"
                    :key="tag.id"
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-sm transition-colors"
                    :class="
                        activeTag === tag.slug
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-sidebar-border text-muted-foreground hover:border-primary'
                    "
                    @click="filterByTag(tag.slug)"
                >
                    {{ tag.name }}
                </button>
            </div>

            <div
                v-if="posts.data.length === 0"
                class="rounded-lg border border-dashed border-sidebar-border p-16 text-center text-muted-foreground"
            >
                No posts published yet.
            </div>

            <div v-else class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="post in posts.data"
                    :key="post.id"
                    :href="`/blog/${post.slug}`"
                    class="group flex flex-col overflow-hidden rounded-lg border border-sidebar-border bg-background transition-shadow hover:shadow-md"
                >
                    <div
                        v-if="post.featured_image"
                        class="aspect-video overflow-hidden bg-muted"
                    >
                        <img
                            :src="post.featured_image.url"
                            :alt="post.featured_image.alt_text ?? post.title"
                            class="h-full w-full object-cover transition-transform group-hover:scale-105"
                            loading="lazy"
                        />
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-5">
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
                        <h2
                            class="text-xl font-semibold tracking-tight group-hover:text-primary"
                        >
                            {{ post.title }}
                        </h2>
                        <p class="line-clamp-3 text-sm text-muted-foreground">
                            {{ post.excerpt }}
                        </p>
                        <div
                            class="mt-auto flex items-center justify-between text-xs text-muted-foreground"
                        >
                            <span>{{ post.author?.name ?? '' }}</span>
                            <time>{{ formatDate(post.published_at) }}</time>
                        </div>
                    </div>
                </Link>
            </div>

            <div
                v-if="posts.last_page > 1"
                class="mt-12 flex justify-center gap-1"
            >
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
        </section>
    </StorefrontLayout>
</template>
