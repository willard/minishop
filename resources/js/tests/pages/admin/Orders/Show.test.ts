import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ShowPage from '@/pages/admin/Orders/Show.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
    Form: {
        name: 'Form',
        template: '<form><slot :errors="{}" :processing="false" /></form>',
        props: ['action', 'method'],
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
        template: '<span><slot /></span>',
        props: ['variant', 'class'],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: {
        name: 'Label',
        template: '<label><slot /></label>',
        props: ['for'],
    },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: {
        name: 'InputError',
        template: '<span />',
        props: ['message'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/OrderController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/orders' })),
    invoice: vi.fn((order: { order_number: string }) => ({
        url: `/dashboard/orders/${order.order_number}/invoice`,
    })),
    update: Object.assign(
        vi.fn((order: { order_number: string }) => ({
            url: `/dashboard/orders/${order.order_number}`,
        })),
        {
            form: vi.fn((order: { order_number: string }) => ({
                action: `/dashboard/orders/${order.order_number}`,
                method: 'put',
            })),
        },
    ),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ReturnController', () => ({
    show: vi.fn((returnNumber: string) => ({
        url: `/dashboard/returns/${returnNumber}`,
    })),
    create: vi.fn(() => ({ url: '/dashboard/returns/create' })),
}));

const baseOrder = {
    id: 1,
    order_number: 'ORD-000001',
    status: 'delivered',
    subtotal: 5000,
    discount_amount: 0,
    shipping_amount: 500,
    tax_amount: 250,
    total_amount: 5750,
    shipping_name: 'Jane Doe',
    shipping_address_line1: '123 Main St',
    shipping_address_line2: null,
    shipping_city: 'Springfield',
    shipping_state: 'IL',
    shipping_postcode: '62701',
    shipping_country: 'US',
    notes: null,
    created_at: '2026-03-17T10:00:00.000Z',
    customer: {
        id: 1,
        phone: null,
        user: { id: 2, name: 'Jane Doe', email: 'jane@example.com' },
    },
    items: [
        {
            id: 1,
            product_id: 1,
            product_name: 'Widget Pro',
            product_sku: 'WGT-001',
            unit_price: 2500,
            quantity: 2,
            subtotal: 5000,
        },
    ],
    returns: [],
};

const baseStatuses = [
    { value: 'pending', label: 'Pending' },
    { value: 'delivered', label: 'Delivered' },
];

function mountPage(overrides: Record<string, unknown> = {}) {
    return mount(ShowPage, {
        props: {
            order: baseOrder,
            statuses: baseStatuses,
            ...overrides,
        },
    });
}

describe('Orders/Show', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the order number as a heading', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('ORD-000001');
    });

    it('renders the customer name', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Jane Doe');
    });

    it('renders the customer email', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('jane@example.com');
    });

    it('renders the shipping address', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('123 Main St');
        expect(wrapper.text()).toContain('Springfield');
    });

    it('renders order items in the table', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Widget Pro');
        expect(wrapper.text()).toContain('WGT-001');
    });

    it('shows a New Return button', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('New Return');
    });

    it('shows empty state when there are no returns', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('No returns for this order.');
    });

    it('renders existing returns when present', () => {
        const wrapper = mountPage({
            order: {
                ...baseOrder,
                returns: [
                    {
                        id: 1,
                        return_number: 'RMA-000001',
                        status: 'requested',
                        status_label: 'Requested',
                        reason_label: 'Defective / Damaged',
                        refund_amount: 0,
                        restocked: false,
                        created_at: '2026-03-17T10:00:00.000Z',
                        items: [{ id: 1, quantity: 1, subtotal: 2500, order_item: { product_name: 'Widget Pro' } }],
                    },
                ],
            },
        });
        expect(wrapper.text()).toContain('RMA-000001');
        expect(wrapper.text()).toContain('Defective / Damaged');
        expect(wrapper.text()).toContain('Requested');
    });

    it('shows the refund amount for a refunded return', () => {
        const wrapper = mountPage({
            order: {
                ...baseOrder,
                returns: [
                    {
                        id: 1,
                        return_number: 'RMA-000001',
                        status: 'refunded',
                        status_label: 'Refunded',
                        reason_label: 'Defective / Damaged',
                        refund_amount: 2500,
                        restocked: true,
                        created_at: '2026-03-17T10:00:00.000Z',
                        items: [],
                    },
                ],
            },
        });
        expect(wrapper.text()).toContain('25.00');
    });

    it('renders the status update form', () => {
        const wrapper = mountPage();
        expect(wrapper.find('select#status').exists()).toBe(true);
    });
});
