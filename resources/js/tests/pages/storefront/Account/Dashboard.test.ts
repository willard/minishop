import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AccountDashboard from '@/pages/storefront/Account/Dashboard.vue';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<any>();
    return {
        ...actual,
        Head: { name: 'Head', template: '<div />', props: ['title'] },
        Link: {
            name: 'Link',
            template: '<a href="#"><slot /></a>',
            props: ['href'],
        },
    };
});

vi.mock('@/layouts/AccountLayout.vue', () => ({
    default: {
        name: 'AccountLayout',
        template: '<div><slot /></div>',
        props: ['title'],
    },
}));

vi.mock('@/routes/account/orders', () => ({
    index: vi.fn(() => ({ url: '/account/orders' })),
    show: vi.fn((params: { order: string }) => ({
        url: `/account/orders/${params.order}`,
    })),
}));

vi.mock('@/lib/utils', () => ({
    formatPrice: (cents: number) => `₱${(cents / 100).toFixed(2)}`,
}));

const baseProps = {
    totalOrders: 5,
    recentOrders: [
        {
            id: 1,
            order_number: 'ORD-000001',
            status: 'delivered',
            total_amount: 8500,
            created_at: '2026-02-20T10:00:00.000Z',
            items: [],
        },
        {
            id: 2,
            order_number: 'ORD-000002',
            status: 'pending',
            total_amount: 3200,
            created_at: '2026-03-01T10:00:00.000Z',
            items: [],
        },
    ],
};

describe('Account/Dashboard', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(AccountDashboard, { props: baseProps });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays total orders count', () => {
        expect(wrapper.text()).toContain('5');
    });

    it('renders recent order numbers', () => {
        expect(wrapper.text()).toContain('ORD-000001');
        expect(wrapper.text()).toContain('ORD-000002');
    });

    it('renders order statuses', () => {
        expect(wrapper.text()).toContain('delivered');
        expect(wrapper.text()).toContain('pending');
    });

    it('shows empty state when no orders', () => {
        const empty = mount(AccountDashboard, {
            props: { ...baseProps, recentOrders: [] },
        });
        expect(empty.text()).toContain('No orders yet');
    });

    it('shows "View all orders" link when there are recent orders', () => {
        expect(wrapper.text()).toContain('View all orders');
    });

    it('does not show "View all orders" link when no recent orders', () => {
        const empty = mount(AccountDashboard, {
            props: { ...baseProps, recentOrders: [] },
        });
        expect(empty.text()).not.toContain('View all orders');
    });
});
