import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PostsIndexPage from '@/pages/admin/Posts/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
    router: { get: vi.fn(), delete: vi.fn() },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button @click="$emit(\'click\')"><slot /></button>',
        props: ['variant', 'size', 'type'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span><slot /></span>',
        props: ['variant'],
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input />',
        props: ['modelValue', 'placeholder'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/PostController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/posts' })),
    create: vi.fn(() => ({ url: '/dashboard/posts/create' })),
    edit: vi.fn((id: number) => ({ url: `/dashboard/posts/${id}/edit` })),
    destroy: vi.fn((id: number) => ({ url: `/dashboard/posts/${id}` })),
}));

const basePagination = {
    data: [
        {
            id: 1,
            title: 'Launch Day',
            slug: 'launch-day',
            status: 'published' as const,
            published_at: '2026-04-01T00:00:00Z',
            updated_at: '2026-04-10T00:00:00Z',
            author: { id: 1, name: 'Admin' },
            tags: [{ id: 7, name: 'news', color: '#0ea5e9' }],
        },
    ],
    current_page: 1,
    last_page: 1,
    total: 1,
    links: [],
};

const statuses = [
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
];

describe('admin/Posts/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(PostsIndexPage, {
            props: { posts: basePagination, filters: {}, statuses },
        });
    });

    it('renders post heading', () => {
        expect(wrapper.text()).toContain('Blog Posts');
    });

    it('renders a row per post', () => {
        expect(wrapper.findAll('tbody tr')).toHaveLength(1);
    });

    it('displays post title and tag', () => {
        expect(wrapper.text()).toContain('Launch Day');
        expect(wrapper.text()).toContain('news');
    });

    it('shows empty state when no posts exist', () => {
        const emptyWrapper = mount(PostsIndexPage, {
            props: {
                posts: { ...basePagination, data: [], total: 0 },
                filters: {},
                statuses,
            },
        });
        expect(emptyWrapper.text()).toContain('No posts yet');
    });
});
