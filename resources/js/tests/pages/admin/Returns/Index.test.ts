import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import IndexPage from '@/pages/admin/Returns/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
    router: { get: vi.fn() },
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

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input />',
        props: ['modelValue', 'placeholder', 'class'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span><slot /></span>',
        props: ['variant', 'class'],
    },
}));

vi.mock('@/composables/usePrice', () => ({
    usePrice: vi.fn(() => ({
        formatPrice: (cents: number) => `$${(cents / 100).toFixed(2)}`,
    })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ReturnController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/returns' })),
    create: vi.fn(() => ({ url: '/dashboard/returns/create' })),
    show: vi.fn((returnNumber: string) => ({
        url: `/dashboard/returns/${returnNumber}`,
    })),
}));

const baseReturns = {
    data: [
        {
            id: 1,
            return_number: 'RMA-000001',
            status: 'requested',
            status_label: 'Requested',
            reason_label: 'Defective / Damaged',
            refund_amount: 0,
            restocked: false,
            created_at: '2026-03-17T10:00:00.000Z',
            order: { id: 1, order_number: 'ORD-000001' },
        },
        {
            id: 2,
            return_number: 'RMA-000002',
            status: 'refunded',
            status_label: 'Refunded',
            reason_label: 'Wrong Item Received',
            refund_amount: 2500,
            restocked: true,
            created_at: '2026-03-16T08:00:00.000Z',
            order: { id: 2, order_number: 'ORD-000002' },
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 2,
    links: [],
};

const baseFilters = {};
const baseStatuses = [
    { value: 'requested', label: 'Requested' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'received', label: 'Received' },
    { value: 'refunded', label: 'Refunded' },
];

function mountPage(overrides = {}) {
    return mount(IndexPage, {
        props: {
            returns: baseReturns,
            filters: baseFilters,
            statuses: baseStatuses,
            ...overrides,
        },
    });
}

describe('Returns/Index', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the page heading', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Returns');
    });

    it('renders each return in the table', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('RMA-000001');
        expect(wrapper.text()).toContain('RMA-000002');
    });

    it('displays the order number for each return', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('ORD-000001');
        expect(wrapper.text()).toContain('ORD-000002');
    });

    it('displays the reason label', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Defective / Damaged');
        expect(wrapper.text()).toContain('Wrong Item Received');
    });

    it('displays the status label', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Requested');
        expect(wrapper.text()).toContain('Refunded');
    });

    it('displays the refund amount when set', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('25.00');
    });

    it('displays a dash when refund amount is zero', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('—');
    });

    it('shows an empty state when there are no returns', () => {
        const wrapper = mountPage({
            returns: { ...baseReturns, data: [], total: 0 },
        });
        expect(wrapper.text()).toContain('No returns found');
    });

    it('renders a New Return button', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('New Return');
    });
});
