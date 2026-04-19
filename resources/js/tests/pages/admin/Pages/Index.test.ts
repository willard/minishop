import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PagesIndexPage from '@/pages/admin/Pages/Index.vue';

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

vi.mock('@/actions/App/Http/Controllers/Admin/PageController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/pages' })),
    create: vi.fn(() => ({ url: '/dashboard/pages/create' })),
    edit: vi.fn((id: number) => ({ url: `/dashboard/pages/${id}/edit` })),
    destroy: vi.fn((id: number) => ({ url: `/dashboard/pages/${id}` })),
}));

const basePagination = {
    data: [
        {
            id: 1,
            title: 'About',
            slug: 'about',
            status: 'published' as const,
            published_at: '2026-04-01T00:00:00Z',
            updated_at: '2026-04-15T00:00:00Z',
            author: { id: 1, name: 'Admin' },
        },
        {
            id: 2,
            title: 'Coming Soon',
            slug: 'coming-soon',
            status: 'draft' as const,
            published_at: null,
            updated_at: '2026-04-10T00:00:00Z',
            author: null,
        },
    ],
    current_page: 1,
    last_page: 1,
    total: 2,
    links: [],
};

const statuses = [
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
    { value: 'scheduled', label: 'Scheduled' },
];

describe('admin/Pages/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(PagesIndexPage, {
            props: { pages: basePagination, filters: {}, statuses },
        });
    });

    it('renders page heading', () => {
        expect(wrapper.text()).toContain('Pages');
    });

    it('renders a row per page', () => {
        expect(wrapper.findAll('tbody tr')).toHaveLength(2);
    });

    it('displays page titles and slugs', () => {
        expect(wrapper.text()).toContain('About');
        expect(wrapper.text()).toContain('/pages/about');
        expect(wrapper.text()).toContain('Coming Soon');
    });

    it('shows empty state when no pages exist', () => {
        const emptyWrapper = mount(PagesIndexPage, {
            props: {
                pages: { ...basePagination, data: [], total: 0 },
                filters: {},
                statuses,
            },
        });
        expect(emptyWrapper.text()).toContain('No pages yet');
    });
});
