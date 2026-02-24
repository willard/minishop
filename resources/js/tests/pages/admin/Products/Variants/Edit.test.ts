import EditVariantPage from '@/pages/admin/Products/Variants/Edit.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a :href="href"><slot /></a>', props: ['href'] },
    useForm: vi.fn((initialData: Record<string, unknown>) => ({
        ...initialData,
        processing: false,
        errors: {},
        post: vi.fn(),
        put: vi.fn(),
    })),
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { name: 'AppLayout', template: '<div><slot /></div>', props: ['breadcrumbs'] },
}));

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', template: '<button><slot /></button>', props: ['variant', 'size', 'type', 'disabled'] },
}));

vi.mock('@/components/ui/input', () => ({
    Input: { name: 'Input', template: '<input />', props: ['id', 'modelValue', 'type', 'min', 'placeholder', 'class'] },
}));

vi.mock('@/components/ui/label', () => ({
    Label: { name: 'Label', template: '<label><slot /></label>', props: ['for'] },
}));

vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: { name: 'Checkbox', template: '<input type="checkbox" />', props: ['id', 'checked'] },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/products' })),
    show: vi.fn((product: { slug: string }) => ({ url: `/dashboard/products/${product.slug}` })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductVariantController', () => ({
    update: vi.fn((args: { product: { slug: string }; variant: { id: number } }) => ({
        url: `/dashboard/products/${args.product.slug}/variants/${args.variant.id}`,
    })),
}));

const baseProduct = {
    id: 1,
    name: 'Blue T-Shirt',
    slug: 'blue-t-shirt',
};

const baseOptionTypes = [
    {
        id: 1,
        name: 'Size',
        values: [
            { id: 10, value: 'S', position: 0 },
            { id: 11, value: 'M', position: 1 },
        ],
    },
    {
        id: 2,
        name: 'Color',
        values: [
            { id: 20, value: 'Red', position: 0 },
            { id: 21, value: 'Blue', position: 1 },
        ],
    },
];

const baseVariant = {
    id: 1,
    sku: 'TSH-M-BLU',
    price: 1999,
    stock_quantity: 25,
    is_active: true,
    option_values: [
        { id: 11, value: 'M', option: { id: 1, name: 'Size' } },
        { id: 21, value: 'Blue', option: { id: 2, name: 'Color' } },
    ],
};

describe('admin/Products/Variants/Edit', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(EditVariantPage, {
            props: { product: baseProduct, variant: baseVariant, optionTypes: baseOptionTypes },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the product name in the header', () => {
        expect(wrapper.text()).toContain('Blue T-Shirt');
    });

    it('displays the Edit Variant heading', () => {
        expect(wrapper.text()).toContain('Edit Variant');
    });

    it('displays Save Changes button', () => {
        expect(wrapper.text()).toContain('Save Changes');
    });

    it('renders a select element per option type', () => {
        const selects = wrapper.findAll('select');
        expect(selects).toHaveLength(2);
    });

    it('renders the Size option type label', () => {
        expect(wrapper.text()).toContain('Size');
    });

    it('renders the Color option type label', () => {
        expect(wrapper.text()).toContain('Color');
    });

    it('renders the stock quantity field label', () => {
        expect(wrapper.text()).toContain('Stock Quantity');
    });

    it('renders the SKU field label', () => {
        expect(wrapper.text()).toContain('SKU');
    });

    it('renders the price override field label', () => {
        expect(wrapper.text()).toContain('Price Override');
    });
});
