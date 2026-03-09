import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import IndexPage from '@/pages/admin/Customers/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
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
        template: '<button><slot /></button>',
        props: ['variant', 'size', 'type', 'disabled'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span class="badge"><slot /></span>',
        props: ['variant'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/CustomerController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/customers' })),
    show: vi.fn((customer: { id: number }) => ({
        url: `/dashboard/customers/${customer.id}`,
    })),
}));

const baseCustomers = {
    data: [
        {
            id: 1,
            phone: '+63 912 345 6789',
            is_active: true,
            orders_count: 3,
            user: { id: 10, name: 'Alice Santos', email: 'alice@example.com' },
            created_at: '2026-01-15T10:00:00.000Z',
        },
        {
            id: 2,
            phone: null,
            is_active: false,
            orders_count: 0,
            user: { id: 11, name: 'Bob Reyes', email: 'bob@example.com' },
            created_at: '2026-02-01T10:00:00.000Z',
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 2,
    links: [],
};

describe('admin/Customers/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(IndexPage, {
            props: { customers: baseCustomers },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the total customers count', () => {
        expect(wrapper.text()).toContain('2 total customers');
    });

    it('displays customer names', () => {
        expect(wrapper.text()).toContain('Alice Santos');
        expect(wrapper.text()).toContain('Bob Reyes');
    });

    it('displays customer emails', () => {
        expect(wrapper.text()).toContain('alice@example.com');
        expect(wrapper.text()).toContain('bob@example.com');
    });

    it('displays order counts', () => {
        expect(wrapper.text()).toContain('3');
    });

    it('displays status badges', () => {
        const badges = wrapper.findAll('.badge');
        const badgeTexts = badges.map((b) => b.text().trim().toLowerCase());
        expect(badgeTexts).toContain('active');
        expect(badgeTexts).toContain('inactive');
    });

    it('shows empty state when no customers', () => {
        const emptyWrapper = mount(IndexPage, {
            props: { customers: { ...baseCustomers, data: [], total: 0 } },
        });
        expect(emptyWrapper.text()).toContain('No customers yet');
    });
});
