import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ShowPage from '@/pages/admin/Returns/Show.vue';

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
    router: { post: vi.fn() },
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

vi.mock('@/actions/App/Http/Controllers/Admin/ReturnController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/returns' })),
    approve: vi.fn((returnNumber: string) => ({
        url: `/dashboard/returns/${returnNumber}/approve`,
    })),
    reject: vi.fn((returnNumber: string) => ({
        url: `/dashboard/returns/${returnNumber}/reject`,
    })),
    receive: vi.fn((returnNumber: string) => ({
        url: `/dashboard/returns/${returnNumber}/receive`,
    })),
    refund: vi.fn((returnNumber: string) => ({
        url: `/dashboard/returns/${returnNumber}/refund`,
    })),
    update: Object.assign(
        vi.fn((returnNumber: string) => ({
            url: `/dashboard/returns/${returnNumber}`,
        })),
        {
            form: vi.fn((returnNumber: string) => ({
                action: `/dashboard/returns/${returnNumber}`,
                method: 'put',
            })),
        },
    ),
}));

const baseReturn = {
    id: 1,
    return_number: 'RMA-000001',
    order_id: 1,
    status: 'requested',
    status_label: 'Requested',
    reason: 'defective',
    reason_label: 'Defective / Damaged',
    notes: 'Item arrived cracked.',
    admin_notes: null,
    refund_amount: 0,
    stripe_refund_id: null,
    restocked: false,
    refunded_at: null,
    created_at: '2026-03-17T10:00:00.000Z',
    allowed_transitions: ['approved', 'rejected'],
    order: {
        id: 1,
        order_number: 'ORD-000001',
        total_amount: 5000,
        status: 'delivered',
        status_label: 'Delivered',
    },
    items: [
        {
            id: 1,
            order_item_id: 1,
            quantity: 1,
            unit_price: 2500,
            subtotal: 2500,
            order_item: {
                id: 1,
                product_name: 'Widget Pro',
                product_sku: 'WGT-001',
                quantity: 2,
                unit_price: 2500,
            },
        },
    ],
};

const baseStatuses = [
    { value: 'requested', label: 'Requested' },
    { value: 'approved', label: 'Approved' },
    { value: 'refunded', label: 'Refunded' },
];

const baseReasons = [
    { value: 'defective', label: 'Defective / Damaged' },
];

function mountPage(overrides: Record<string, unknown> = {}) {
    return mount(ShowPage, {
        props: {
            orderReturn: baseReturn,
            statuses: baseStatuses,
            reasons: baseReasons,
            ...overrides,
        },
    });
}

describe('Returns/Show', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the return number as a heading', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('RMA-000001');
    });

    it('renders the status badge', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Requested');
    });

    it('renders the order number', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('ORD-000001');
    });

    it('renders the reason label', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Defective / Damaged');
    });

    it('renders customer notes', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Item arrived cracked.');
    });

    it('renders return items in the table', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Widget Pro');
        expect(wrapper.text()).toContain('WGT-001');
    });

    it('shows the Approve button when transition is allowed', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Approve Return');
    });

    it('shows the Reject button when transition is allowed', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Reject Return');
    });

    it('does not show Approve/Reject for a refunded return', () => {
        const wrapper = mountPage({
            orderReturn: {
                ...baseReturn,
                status: 'refunded',
                status_label: 'Refunded',
                allowed_transitions: [],
            },
        });
        expect(wrapper.text()).not.toContain('Approve Return');
        expect(wrapper.text()).not.toContain('Reject Return');
    });

    it('shows the Receive button when approved', () => {
        const wrapper = mountPage({
            orderReturn: {
                ...baseReturn,
                status: 'approved',
                status_label: 'Approved',
                allowed_transitions: ['received'],
            },
        });
        expect(wrapper.text()).toContain('Mark as Received');
    });

    it('shows the Issue Stripe Refund button when received', () => {
        const wrapper = mountPage({
            orderReturn: {
                ...baseReturn,
                status: 'received',
                status_label: 'Received',
                restocked: true,
                allowed_transitions: ['refunded'],
            },
        });
        expect(wrapper.text()).toContain('Issue Stripe Refund');
    });

    it('shows no actions available message when transitions are empty', () => {
        const wrapper = mountPage({
            orderReturn: {
                ...baseReturn,
                status: 'rejected',
                status_label: 'Rejected',
                allowed_transitions: [],
            },
        });
        expect(wrapper.text()).toContain('No further actions available');
    });

    it('shows stripe refund ID when present', () => {
        const wrapper = mountPage({
            orderReturn: {
                ...baseReturn,
                status: 'refunded',
                stripe_refund_id: 're_abc123',
                refund_amount: 2500,
                refunded_at: '2026-03-17T12:00:00.000Z',
                allowed_transitions: [],
            },
        });
        expect(wrapper.text()).toContain('re_abc123');
    });

    it('renders the admin notes textarea', () => {
        const wrapper = mountPage();
        expect(wrapper.find('textarea#admin_notes').exists()).toBe(true);
    });
});
