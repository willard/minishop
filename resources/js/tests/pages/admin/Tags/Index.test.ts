import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import IndexPage from '@/pages/admin/Tags/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
    router: { delete: vi.fn() },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span><slot /></span>',
        props: ['variant'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button><slot /></button>',
        props: ['variant', 'size', 'type', 'disabled'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/TagController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/tags' })),
    create: vi.fn(() => ({ url: '/dashboard/tags/create' })),
    edit: vi.fn((tag: { id: number }) => ({
        url: `/dashboard/tags/${tag.id}/edit`,
    })),
    destroy: vi.fn((tag: { id: number }) => ({
        url: `/dashboard/tags/${tag.id}`,
    })),
}));

const basePagination = {
    data: [
        {
            id: 1,
            name: 'Sale',
            slug: 'sale',
            color: '#FF5733',
            is_active: true,
        },
        {
            id: 2,
            name: 'New Arrival',
            slug: 'new-arrival',
            color: null,
            is_active: false,
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 2,
    links: [],
};

describe('admin/Tags/Index', () => {
    it('renders without errors', () => {
        const wrapper = mount(IndexPage, {
            props: { tags: basePagination },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('displays tag names in the table', () => {
        const wrapper = mount(IndexPage, {
            props: { tags: basePagination },
        });
        expect(wrapper.text()).toContain('Sale');
        expect(wrapper.text()).toContain('New Arrival');
    });

    it('shows total count', () => {
        const wrapper = mount(IndexPage, {
            props: { tags: basePagination },
        });
        expect(wrapper.text()).toContain('2 total tags');
    });

    it('shows color swatch for tags with color', () => {
        const wrapper = mount(IndexPage, {
            props: { tags: basePagination },
        });
        const swatch = wrapper.find('span[style*="background-color"]');
        expect(swatch.exists()).toBe(true);
    });

    it('shows active badge for active tags', () => {
        const wrapper = mount(IndexPage, {
            props: { tags: basePagination },
        });
        expect(wrapper.text()).toContain('Active');
        expect(wrapper.text()).toContain('Inactive');
    });

    it('shows empty state when no tags', () => {
        const wrapper = mount(IndexPage, {
            props: {
                tags: { ...basePagination, data: [], total: 0 },
            },
        });
        expect(wrapper.text()).toContain('No tags yet');
    });
});
