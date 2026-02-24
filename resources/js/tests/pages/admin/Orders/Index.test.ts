import IndexPage from '@/pages/admin/Orders/Index.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a href="#"><slot /></a>', props: ['href'] },
    router: { delete: vi.fn() },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { name: 'AppLayout', template: '<div><slot /></div>', props: ['breadcrumbs'] },
}));

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', template: '<button><slot /></button>', props: ['variant', 'size', 'type', 'disabled'] },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: { name: 'Badge', template: '<span class="badge"><slot /></span>', props: ['variant'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/OrderController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/orders' })),
    show: vi.fn((order: { order_number: string }) => ({ url: `/dashboard/orders/${order.order_number}` })),
    destroy: vi.fn((order: { order_number: string }) => ({ url: `/dashboard/orders/${order.order_number}` })),
}));

const baseOrders = {
    data: [
        {
            id: 1,
            order_number: 'ORD-000001',
            status: 'pending',
            total_amount: 5000,
            customer: {
                id: 1,
                user: { id: 2, name: 'Jane Doe', email: 'jane@example.com' },
            },
            items_count: 2,
            created_at: '2026-02-23T10:00:00.000Z',
        },
        {
            id: 2,
            order_number: 'ORD-000002',
            status: 'delivered',
            total_amount: 12000,
            customer: {
                id: 2,
                user: { id: 3, name: 'John Smith', email: 'john@example.com' },
            },
            items_count: 1,
            created_at: '2026-02-22T10:00:00.000Z',
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 2,
    links: [],
};

describe('admin/Orders/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(IndexPage, {
            props: { orders: baseOrders },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the total orders count', () => {
        expect(wrapper.text()).toContain('2 total orders');
    });

    it('displays order numbers', () => {
        expect(wrapper.text()).toContain('ORD-000001');
        expect(wrapper.text()).toContain('ORD-000002');
    });

    it('displays customer names', () => {
        expect(wrapper.text()).toContain('Jane Doe');
        expect(wrapper.text()).toContain('John Smith');
    });

    it('displays formatted totals', () => {
        expect(wrapper.text()).toContain('50.00');
        expect(wrapper.text()).toContain('120.00');
    });

    it('displays status badges', () => {
        const badges = wrapper.findAll('.badge');
        const badgeTexts = badges.map((b) => b.text().trim().toLowerCase());
        expect(badgeTexts).toContain('pending');
        expect(badgeTexts).toContain('delivered');
    });

    it('shows empty state when no orders', () => {
        const emptyWrapper = mount(IndexPage, {
            props: { orders: { ...baseOrders, data: [], total: 0 } },
        });
        expect(emptyWrapper.text()).toContain('No orders yet');
    });
});
