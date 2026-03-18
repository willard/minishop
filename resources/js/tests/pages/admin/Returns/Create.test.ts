import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CreatePage from '@/pages/admin/Returns/Create.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
    router: { get: vi.fn() },
    useForm: vi.fn(() => ({
        order_id: null,
        reason: '',
        notes: '',
        admin_notes: '',
        items: [],
        errors: {},
        processing: false,
        post: vi.fn(),
    })),
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
        props: ['modelValue', 'placeholder', 'type', 'required'],
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

vi.mock('@/composables/usePrice', () => ({
    usePrice: vi.fn(() => ({
        formatPrice: (cents: number) => `${(cents / 100).toFixed(2)}`,
    })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ReturnController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/returns' })),
    store: vi.fn(() => ({ url: '/dashboard/returns' })),
    create: vi.fn((options?: { mergeQuery?: Record<string, unknown> }) => ({
        url: '/dashboard/returns/create' + (options?.mergeQuery ? '?' + new URLSearchParams(options.mergeQuery as Record<string, string>).toString() : ''),
    })),
}));

const baseReasons = [
    { value: 'defective', label: 'Defective / Damaged' },
    { value: 'wrong_item', label: 'Wrong Item Received' },
    { value: 'change_of_mind', label: 'Change of Mind' },
];

const baseOrder = {
    id: 1,
    order_number: 'ORD-000001',
    total_amount: 5000,
    items: [
        {
            id: 1,
            product_name: 'Widget Pro',
            product_sku: 'WGT-001',
            unit_price: 2500,
            quantity: 2,
            variant_id: null,
            variant: null,
        },
        {
            id: 2,
            product_name: 'Gadget Plus',
            product_sku: 'GDG-002',
            unit_price: 1500,
            quantity: 1,
            variant_id: null,
            variant: null,
        },
    ],
};

function mountPage(overrides: Record<string, unknown> = {}) {
    return mount(CreatePage, {
        props: {
            order: baseOrder,
            reasons: baseReasons,
            ...overrides,
        },
    });
}

describe('Returns/Create', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the page heading', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('New Return');
    });

    it('shows the order number when an order is pre-loaded', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('ORD-000001');
    });

    it('renders all order items in the table', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Widget Pro');
        expect(wrapper.text()).toContain('Gadget Plus');
    });

    it('shows item SKUs', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('WGT-001');
        expect(wrapper.text()).toContain('GDG-002');
    });

    it('renders all reason options in the select', () => {
        const wrapper = mountPage();
        const options = wrapper.findAll('option').map((o) => o.text());
        expect(options).toContain('Defective / Damaged');
        expect(options).toContain('Wrong Item Received');
    });

    it('shows the order number search input when no order is provided', () => {
        const wrapper = mountPage({ order: null });
        expect(wrapper.find('input#order_number').exists()).toBe(true);
    });

    it('shows a Find button when no order is provided', () => {
        const wrapper = mountPage({ order: null });
        expect(wrapper.text()).toContain('Find');
    });

    it('does not show the order number search input when an order is pre-loaded', () => {
        const wrapper = mountPage();
        expect(wrapper.find('input#order_number').exists()).toBe(false);
    });

    it('renders the submit button when an order is pre-loaded', () => {
        const wrapper = mountPage();
        expect(wrapper.text()).toContain('Create Return');
    });

    it('does not render the submit button when no order is provided', () => {
        const wrapper = mountPage({ order: null });
        expect(wrapper.text()).not.toContain('Create Return');
    });

    it('renders customer notes textarea', () => {
        const wrapper = mountPage();
        expect(wrapper.find('textarea#notes').exists()).toBe(true);
    });

    it('renders admin notes textarea', () => {
        const wrapper = mountPage();
        expect(wrapper.find('textarea#admin_notes').exists()).toBe(true);
    });
});
