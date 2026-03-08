import OrdersShow from '@/pages/storefront/Account/Orders/Show.vue';
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
    index: vi.fn(() => ({ url: '/account/orders' })),
}));

vi.mock('@/lib/utils', () => ({
    formatPrice: (cents: number) => `₱${(cents / 100).toFixed(2)}`,
}));

const baseOrder = {
    id: 1,
    order_number: 'ORD-000042',
    status: 'delivered',
    payment_status: 'paid',
    payment_gateway: 'stripe',
    subtotal: 8500,
    discount_amount: 0,
    shipping_amount: 200,
    tax_amount: 0,
    total_amount: 8700,
    shipping_name: 'Jane Doe',
    shipping_address_line1: '123 Main St',
    shipping_address_line2: null,
    shipping_city: 'Manila',
    shipping_state: 'Metro Manila',
    shipping_postcode: '1000',
    shipping_country: 'PH',
    created_at: '2026-02-20T10:00:00.000Z',
    shipping_method: { name: 'Standard' },
    items: [
        {
            id: 10,
            quantity: 2,
            unit_price: 3000,
            subtotal: 6000,
            product: { name: 'Linen Tote', slug: 'linen-tote' },
            variant: { sku: 'LT-001' },
        },
        {
            id: 11,
            quantity: 1,
            unit_price: 2500,
            subtotal: 2500,
            product: { name: 'Canvas Pouch', slug: 'canvas-pouch' },
            variant: null,
        },
    ],
};

describe('Account/Orders/Show', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(OrdersShow, { props: { order: baseOrder } });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the order number', () => {
        expect(wrapper.text()).toContain('ORD-000042');
    });

    it('displays the order status', () => {
        expect(wrapper.text()).toContain('delivered');
    });

    it('displays each line item product name', () => {
        expect(wrapper.text()).toContain('Linen Tote');
        expect(wrapper.text()).toContain('Canvas Pouch');
    });

    it('displays line item quantities and prices', () => {
        expect(wrapper.text()).toContain('Qty: 2');
        expect(wrapper.text()).toContain('₱30.00');
    });

    it('displays the order total', () => {
        expect(wrapper.text()).toContain('₱87.00');
    });

    it('displays the shipping address name', () => {
        expect(wrapper.text()).toContain('Jane Doe');
    });

    it('displays the shipping address details', () => {
        expect(wrapper.text()).toContain('123 Main St');
        expect(wrapper.text()).toContain('Manila');
    });

    it('shows "Product removed" for items with a null product', () => {
        const orderWithRemovedProduct = {
            ...baseOrder,
            items: [{ id: 20, quantity: 1, unit_price: 1000, subtotal: 1000, product: null, variant: null }],
        };
        const w = mount(OrdersShow, { props: { order: orderWithRemovedProduct } });
        expect(w.text()).toContain('Product removed');
    });
});
