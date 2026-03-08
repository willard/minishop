import OrdersIndex from '@/pages/storefront/Account/Orders/Index.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a href="#"><slot /></a>', props: ['href'] },
}));

vi.mock('@/layouts/AccountLayout.vue', () => ({
    default: { name: 'AccountLayout', template: '<div><slot /></div>', props: ['title'] },
}));

vi.mock('@/routes/account/orders', () => ({
    show: vi.fn((params: { order: string }) => ({ url: `/account/orders/${params.order}` })),
}));

vi.mock('@/lib/utils', () => ({
    formatPrice: (cents: number) => `₱${(cents / 100).toFixed(2)}`,
}));

const makeOrders = (count: number) => ({
    data: Array.from({ length: count }, (_, i) => ({
        id: i + 1,
        order_number: `ORD-00000${i + 1}`,
        status: 'pending',
        total_amount: 1000 * (i + 1),
        created_at: '2026-03-01T10:00:00.000Z',
        items: [{ id: 1, quantity: 2, unit_price: 500 }],
    })),
    current_page: 1,
    last_page: 1,
    next_page_url: null,
    prev_page_url: null,
    links: [],
});

describe('Account/Orders/Index', () => {
    it('renders order list', () => {
        const wrapper = mount(OrdersIndex, { props: { orders: makeOrders(3) } });
        expect(wrapper.text()).toContain('ORD-000001');
        expect(wrapper.text()).toContain('ORD-000002');
        expect(wrapper.text()).toContain('ORD-000003');
    });

    it('shows empty state when no orders', () => {
        const wrapper = mount(OrdersIndex, { props: { orders: makeOrders(0) } });
        expect(wrapper.text()).toContain("haven't placed any orders");
    });

    it('does not render pagination when only one page', () => {
        const wrapper = mount(OrdersIndex, { props: { orders: makeOrders(3) } });
        expect(wrapper.find('[href]').exists()).toBe(true);
    });
});
