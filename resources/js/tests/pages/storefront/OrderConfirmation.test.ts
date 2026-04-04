import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import OrderConfirmationPage from '@/pages/storefront/OrderConfirmation.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
}));

vi.mock('@/layouts/StorefrontLayout.vue', () => ({
    default: {
        name: 'StorefrontLayout',
        template: '<div><slot /></div>',
    },
}));

vi.mock('@/composables/usePrice', () => ({
    usePrice: vi.fn(() => ({
        formatPrice: (cents: number) => `$${(cents / 100).toFixed(2)}`,
    })),
}));

vi.mock('@/actions/App/Http/Controllers/Storefront/ProductController', () => ({
    index: vi.fn(() => ({ url: '/products' })),
}));

const baseOrder = {
    id: 1,
    order_number: 'ORD-0001',
    status: 'processing',
    subtotal: 10000,
    discount_amount: 0,
    shipping_amount: 1500,
    tax_amount: 1300,
    tax_zone_name: 'Ontario',
    tax_breakdown: [{ name: 'HST', name_fr: 'TVH', rate: 13, amount_cents: 1300 }],
    total_amount: 12800,
    shipping_name: 'Jane Doe',
    shipping_address_line1: '123 Main St',
    shipping_address_line2: null,
    shipping_city: 'Toronto',
    shipping_state: 'ON',
    shipping_postcode: 'M5H 2N2',
    shipping_country: 'CA',
    items: [
        {
            id: 1,
            product_name: 'Test Product',
            product_sku: 'SKU-001',
            unit_price: 10000,
            quantity: 1,
            subtotal: 10000,
        },
    ],
    customer: {
        user: { name: 'Jane Doe', email: 'jane@example.com' },
    },
};

describe('storefront/OrderConfirmation', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(OrderConfirmationPage, { props: { order: baseOrder } });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the order number', () => {
        expect(wrapper.text()).toContain('ORD-0001');
    });

    it('displays the customer first name', () => {
        expect(wrapper.text()).toContain('Jane');
    });

    it('shows tax breakdown rows when tax_breakdown is present', () => {
        expect(wrapper.text()).toContain('HST (13%)');
        expect(wrapper.text()).toContain('$13.00');
    });

    it('does not show static "Tax (12%)" label', () => {
        expect(wrapper.text()).not.toContain('Tax (12%)');
    });

    it('falls back to generic Tax label when tax_breakdown is null', () => {
        const noBreakdownWrapper = mount(OrderConfirmationPage, {
            props: {
                order: { ...baseOrder, tax_breakdown: null },
            },
        });
        expect(noBreakdownWrapper.text()).toContain('Tax');
        expect(noBreakdownWrapper.text()).not.toContain('HST');
    });

    it('falls back to generic Tax label when tax_breakdown is empty', () => {
        const emptyBreakdownWrapper = mount(OrderConfirmationPage, {
            props: {
                order: { ...baseOrder, tax_breakdown: [] },
            },
        });
        expect(emptyBreakdownWrapper.text()).toContain('Tax');
        expect(emptyBreakdownWrapper.text()).not.toContain('HST');
    });

    it('displays multiple tax breakdown lines', () => {
        const multiTaxWrapper = mount(OrderConfirmationPage, {
            props: {
                order: {
                    ...baseOrder,
                    tax_breakdown: [
                        { name: 'GST', name_fr: 'TPS', rate: 5, amount_cents: 500 },
                        { name: 'QST', name_fr: 'TVQ', rate: 9.975, amount_cents: 997 },
                    ],
                },
            },
        });
        expect(multiTaxWrapper.text()).toContain('GST (5%)');
        expect(multiTaxWrapper.text()).toContain('QST (9.975%)');
    });

    it('displays shipping address', () => {
        expect(wrapper.text()).toContain('123 Main St');
        expect(wrapper.text()).toContain('Toronto');
    });

    it('shows the Continue Shopping link', () => {
        expect(wrapper.text()).toContain('Continue Shopping');
    });
});
