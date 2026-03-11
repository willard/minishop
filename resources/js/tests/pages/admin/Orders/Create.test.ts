import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@inertiajs/vue3';
import CreateOrderPage from '@/pages/admin/Orders/Create.vue';

const mockPost = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
    useForm: vi.fn((initial: object) => ({
        ...initial,
        errors: {},
        processing: false,
        transform: vi.fn().mockReturnThis(),
        post: mockPost,
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
        template: '<button :type="type" :disabled="disabled"><slot /></button>',
        props: ['variant', 'size', 'type', 'disabled'],
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input />',
        props: ['id', 'modelValue', 'type', 'min', 'step', 'maxlength', 'placeholder', 'class'],
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
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/OrderController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/orders' })),
    create: vi.fn(() => ({ url: '/dashboard/orders/create' })),
    store: vi.fn(() => ({ url: '/dashboard/orders' })),
}));

const baseCustomers = [
    { id: 1, name: 'Jane Doe', email: 'jane@example.com' },
    { id: 2, name: 'John Smith', email: 'john@example.com' },
];

const baseProducts = [
    {
        id: 1,
        name: 'Widget A',
        sku: 'WA-001',
        price: 1500,
        stock_quantity: 10,
        variants: [],
    },
    {
        id: 2,
        name: 'T-Shirt',
        sku: 'TS-BASE',
        price: 1800,
        stock_quantity: 5,
        variants: [
            { id: 10, sku: 'TS-RED-L', price: 1800, stock_quantity: 8, label: 'Red / Large' },
            { id: 11, sku: 'TS-BLUE-M', price: 1800, stock_quantity: 3, label: 'Blue / Medium' },
        ],
    },
];

const baseShippingMethods = [
    { id: 1, name: 'Standard', price: 500, is_free: false },
    { id: 2, name: 'Free Shipping', price: 0, is_free: true },
];

const baseStatuses = [
    { value: 'pending', label: 'Pending' },
    { value: 'processing', label: 'Processing' },
];

const defaultProps = {
    customers: baseCustomers,
    products: baseProducts,
    shippingMethods: baseShippingMethods,
    statuses: baseStatuses,
    taxRate: 12,
};

describe('admin/Orders/Create', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        mockPost.mockClear();
        wrapper = mount(CreateOrderPage, { props: defaultProps });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the New Order heading', () => {
        expect(wrapper.text()).toContain('New Order');
    });

    it('renders customer options from props', () => {
        const options = wrapper.findAll('option');
        const texts = options.map((o) => o.text());
        expect(texts.some((t) => t.includes('Jane Doe'))).toBe(true);
        expect(texts.some((t) => t.includes('John Smith'))).toBe(true);
    });

    it('renders product options for item rows', () => {
        const options = wrapper.findAll('option');
        const texts = options.map((o) => o.text());
        expect(texts.some((t) => t.includes('Widget A'))).toBe(true);
        expect(texts.some((t) => t.includes('T-Shirt'))).toBe(true);
    });

    it('renders shipping method options', () => {
        const options = wrapper.findAll('option');
        const texts = options.map((o) => o.text());
        expect(texts.some((t) => t.includes('Standard'))).toBe(true);
        expect(texts.some((t) => t.includes('Free Shipping'))).toBe(true);
    });

    it('renders status options from props', () => {
        const options = wrapper.findAll('option');
        const texts = options.map((o) => o.text());
        expect(texts.some((t) => t.includes('Pending'))).toBe(true);
        expect(texts.some((t) => t.includes('Processing'))).toBe(true);
    });

    it('does not show a variant selector when a product without variants is pre-selected', () => {
        const wrapperNoVariants = mount(CreateOrderPage, {
            props: {
                ...defaultProps,
                products: [{ id: 1, name: 'Widget A', sku: 'WA-001', price: 1500, stock_quantity: 10, variants: [] }],
            },
        });
        expect(wrapperNoVariants.text()).not.toContain('Variant');
    });

    it('renders variant options when a product with variants is selected', () => {
        vi.mocked(useForm).mockReturnValueOnce({
            customer_id: '',
            status: 'pending',
            items: [{ product_id: 2, variant_id: '', quantity: 1, unit_price: '' }],
            shipping_name: '', shipping_address_line1: '', shipping_address_line2: '',
            shipping_city: '', shipping_state: '', shipping_postcode: '',
            shipping_country: 'PH', shipping_method_id: '', coupon_code: '', notes: '',
            errors: {}, processing: false,
            transform: vi.fn().mockReturnThis(),
            post: mockPost,
        } as ReturnType<typeof useForm>);

        const wrapperVariants = mount(CreateOrderPage, { props: defaultProps });
        const texts = wrapperVariants.findAll('option').map((o) => o.text());
        expect(texts.some((t) => t.includes('Red / Large'))).toBe(true);
        expect(texts.some((t) => t.includes('Blue / Medium'))).toBe(true);
    });

    it('renders the Add Item button', () => {
        const buttons = wrapper.findAll('button');
        const texts = buttons.map((b) => b.text().trim());
        expect(texts.some((t) => t.includes('Add Item'))).toBe(true);
    });

    it('renders the Create Order submit button', () => {
        const buttons = wrapper.findAll('button[type="submit"]');
        expect(buttons.length).toBeGreaterThan(0);
        expect(buttons[0].text()).toContain('Create Order');
    });

    it('renders shipping address section labels', () => {
        expect(wrapper.text()).toContain('Full Name');
        expect(wrapper.text()).toContain('Address Line 1');
        expect(wrapper.text()).toContain('City');
        expect(wrapper.text()).toContain('Postcode');
    });

    it('renders the Order Summary section', () => {
        expect(wrapper.text()).toContain('Order Summary');
        expect(wrapper.text()).toContain('Subtotal');
        expect(wrapper.text()).toContain('Shipping');
        expect(wrapper.text()).toContain('Total');
    });

    it('shows tax rate when taxRate > 0', () => {
        expect(wrapper.text()).toContain('Tax (12%)');
    });

    it('hides tax row when taxRate is 0', () => {
        const noTaxWrapper = mount(CreateOrderPage, {
            props: { ...defaultProps, taxRate: 0 },
        });
        expect(noTaxWrapper.text()).not.toContain('Tax (');
    });

    it('renders coupon code field label', () => {
        expect(wrapper.text()).toContain('Coupon Code');
    });

    it('renders notes field label', () => {
        expect(wrapper.text()).toContain('Notes');
    });
});
